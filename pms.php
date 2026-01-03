<?php

/**
 * Modern Private Messaging System
 * Güvenli, PDO tabanlı mesajlaşma sistemi
 * 
 * @author   Ufukart
 * @version  1.0
 * @license  MIT
 */

class PrivateMessagingSystem {
    
    private $pdo;
    private $config;
    
    /**
     * Constructor - Veritabanı bağlantısı
     */
    public function __construct(array $config) {
        $this->config = array_merge([
            'host' => 'localhost',
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
            'table_users' => 'users',
            'table_messages' => 'messages',
            'website_email' => 'noreply@example.com'
        ], $config);
        
        $this->connect();
    }
    
    /**
     * PDO ile güvenli veritabanı bağlantısı
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
            
        } catch (PDOException $e) {
            throw new Exception("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Mesaj gönderme
     * 
     * @param int $to Alıcı kullanıcı ID
     * @param string $message Mesaj içeriği
     * @param string $subject Mesaj konusu
     * @param int $respondTo Yanıt verilen mesaj ID (opsiyonel)
     * @return int|bool Yeni mesaj ID veya false
     */
    public function sendMessage($to, $message, $subject, $respondTo = 0) {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Oturum bulunamadı");
        }
        
        $from = $_SESSION['user_id'];
        
        // Validasyon
        if (empty($to) || empty($message) || empty($subject)) {
            return false;
        }
        
        // Kullanıcı kontrolü
        if (!$this->userExists($to)) {
            throw new Exception("Alıcı kullanıcı bulunamadı");
        }
        
        // Mesajı temizle
        $message = $this->sanitizeMessage($message);
        $subject = $this->sanitizeSubject($subject);
        
        try {
            $sql = "INSERT INTO {$this->config['table_messages']} 
                    (user_to, user_from, subject, message, respond, created_at) 
                    VALUES (:to, :from, :subject, :message, :respond, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':to' => $to,
                ':from' => $from,
                ':subject' => $subject,
                ':message' => $message,
                ':respond' => $respondTo
            ]);
            
            $messageId = $this->pdo->lastInsertId();
            
            // Email bildirimi (opsiyonel)
            // $this->emailNotification($to, $from, $subject);
            
            return $messageId;
            
        } catch (PDOException $e) {
            error_log("Mesaj gönderme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Okunmamış mesaj sayısını getir
     * 
     * @return int Okunmamış mesaj sayısı
     */
    public function getUnreadCount() {
        if (!isset($_SESSION['user_id'])) {
            return 0;
        }
        
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->config['table_messages']} 
                WHERE user_to = :user_id 
                AND opened = 0 
                AND receiver_delete = 'n'
                AND respond = 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch()->count ?? 0;
    }
    
    /**
     * Tüm mesajları getir (gelen ve giden)
     * 
     * @param int $page Sayfa numarası
     * @param int $perPage Sayfa başına mesaj sayısı
     * @return array Mesaj listesi
     */
    public function getAllMessages($page = 1, $perPage = 20) {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        
        $userId = $_SESSION['user_id'];
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT m.*, 
                       u_from.first_name as sender_firstname, 
                       u_from.last_name as sender_lastname,
                       u_to.first_name as receiver_firstname,
                       u_to.last_name as receiver_lastname
                FROM {$this->config['table_messages']} m
                LEFT JOIN {$this->config['table_users']} u_from ON m.user_from = u_from.id
                LEFT JOIN {$this->config['table_users']} u_to ON m.user_to = u_to.id
                WHERE (m.user_to = :user_id OR m.user_from = :user_id)
                AND m.respond = 0
                AND (
                    (m.user_from = :user_id AND m.sender_delete = 'n') OR
                    (m.user_to = :user_id AND m.receiver_delete = 'n')
                )
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Tek bir mesajı ve yanıtlarını getir
     * 
     * @param int $messageId Mesaj ID
     * @return array|false Mesaj ve yanıtları
     */
    public function getMessage($messageId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Ana mesajı getir
        $sql = "SELECT m.*, 
                       u_from.first_name as sender_firstname, 
                       u_from.last_name as sender_lastname,
                       u_to.first_name as receiver_firstname,
                       u_to.last_name as receiver_lastname
                FROM {$this->config['table_messages']} m
                LEFT JOIN {$this->config['table_users']} u_from ON m.user_from = u_from.id
                LEFT JOIN {$this->config['table_users']} u_to ON m.user_to = u_to.id
                WHERE m.id = :message_id 
                AND (m.user_to = :user_id OR m.user_from = :user_id)
                AND (
                    (m.user_from = :user_id AND m.sender_delete = 'n') OR
                    (m.user_to = :user_id AND m.receiver_delete = 'n')
                )";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':message_id' => $messageId,
            ':user_id' => $userId
        ]);
        
        $message = $stmt->fetch();
        
        if (!$message) {
            return false;
        }
        
        // Mesajı okundu olarak işaretle
        if ($message->user_to == $userId && $message->opened == 0) {
            $this->markAsRead($messageId);
        }
        
        // Yanıtları getir
        $sql = "SELECT m.*, 
                       u_from.first_name as sender_firstname, 
                       u_from.last_name as sender_lastname
                FROM {$this->config['table_messages']} m
                LEFT JOIN {$this->config['table_users']} u_from ON m.user_from = u_from.id
                WHERE m.respond = :message_id
                AND (
                    (m.user_from = :user_id AND m.sender_delete = 'n') OR
                    (m.user_to = :user_id AND m.receiver_delete = 'n')
                )
                ORDER BY m.created_at ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':message_id' => $messageId,
            ':user_id' => $userId
        ]);
        
        $replies = $stmt->fetchAll();
        
        return [
            'message' => $message,
            'replies' => $replies
        ];
    }
    
    /**
     * Mesajı okundu olarak işaretle
     * 
     * @param int $messageId Mesaj ID
     * @return bool
     */
    public function markAsRead($messageId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $sql = "UPDATE {$this->config['table_messages']} 
                SET opened = 1 
                WHERE id = :message_id 
                AND user_to = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':message_id' => $messageId,
            ':user_id' => $_SESSION['user_id']
        ]);
    }
    
    /**
     * Mesajı sil (soft delete)
     * 
     * @param int $messageId Mesaj ID
     * @return bool
     */
    public function deleteMessage($messageId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Kullanıcının rolünü belirle
        $sql = "SELECT user_from, user_to FROM {$this->config['table_messages']} WHERE id = :message_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':message_id' => $messageId]);
        $message = $stmt->fetch();
        
        if (!$message) {
            return false;
        }
        
        // Gönderen mi alıcı mı?
        $role = ($message->user_from == $userId) ? 'sender_delete' : 'receiver_delete';
        
        $sql = "UPDATE {$this->config['table_messages']} 
                SET {$role} = 'y' 
                WHERE id = :message_id";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':message_id' => $messageId]);
        
        // Her iki taraf da sildiyse kalıcı olarak sil
        $this->checkForPermanentDeletion();
        
        return $result;
    }
    
    /**
     * Tüm konuşmayı sil
     * 
     * @param int $conversationId Ana mesaj ID
     * @return bool
     */
    public function deleteConversation($conversationId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Ana mesajı ve yanıtlarını bul
        $sql = "SELECT id, user_from, user_to FROM {$this->config['table_messages']} 
                WHERE id = :conversation_id OR respond = :conversation_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':conversation_id' => $conversationId]);
        $messages = $stmt->fetchAll();
        
        foreach ($messages as $message) {
            $this->deleteMessage($message->id);
        }
        
        return true;
    }
    
    /**
     * Kalıcı silme kontrolü (her iki taraf da sildiyse)
     */
    private function checkForPermanentDeletion() {
        $sql = "DELETE FROM {$this->config['table_messages']} 
                WHERE sender_delete = 'y' AND receiver_delete = 'y'";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Kullanıcı var mı kontrolü
     * 
     * @param int $userId Kullanıcı ID
     * @return bool
     */
    private function userExists($userId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->config['table_users']} WHERE id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch()->count > 0;
    }
    
    /**
     * Mesaj içeriğini temizle
     * 
     * @param string $message Mesaj
     * @return string Temizlenmiş mesaj
     */
    private function sanitizeMessage($message) {
        $message = trim($message);
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return $message;
    }
    
    /**
     * Konu başlığını temizle
     * 
     * @param string $subject Konu
     * @return string Temizlenmiş konu
     */
    private function sanitizeSubject($subject) {
        $subject = trim($subject);
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        return substr($subject, 0, 200); // Maksimum 200 karakter
    }
    
    /**
     * Email bildirimi gönder
     * 
     * @param int $to Alıcı ID
     * @param int $from Gönderen ID
     * @param string $subject Mesaj konusu
     */
    private function emailNotification($to, $from, $subject) {
        try {
            $sql = "SELECT first_name, last_name, email FROM {$this->config['table_users']} WHERE id = :user_id";
            
            // Alıcı bilgileri
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $to]);
            $receiver = $stmt->fetch();
            
            // Gönderen bilgileri
            $stmt->execute([':user_id' => $from]);
            $sender = $stmt->fetch();
            
            if (!$receiver || !$sender) {
                return;
            }
            
            $receiverName = $receiver->first_name . ' ' . $receiver->last_name;
            $senderName = $sender->first_name . ' ' . $sender->last_name;
            
            $body = "Sayın {$receiverName},\n\n";
            $body .= "Sistemimizde yeni bir mesajınız var.\n\n";
            $body .= "Gönderen: {$senderName}\n";
            $body .= "Konu: {$subject}\n\n";
            $body .= "Mesajınızı okumak için sitemize giriş yapabilirsiniz.\n\n";
            $body .= "İyi günler,\n";
            $body .= "Sistem Yönetimi";
            
            $headers = "From: {$this->config['website_email']}\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            mail($receiver->email, "Yeni Mesaj: {$subject}", $body, $headers);
            
        } catch (Exception $e) {
            error_log("Email gönderme hatası: " . $e->getMessage());
        }
    }
}
?>
