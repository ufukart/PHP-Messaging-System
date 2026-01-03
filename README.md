# 🔐 Secure PHP Messaging System
Modern, güvenli ve PDO tabanlı özel mesajlaşma sistemi. Kullanıcılar arası güvenli iletişim için tasarlanmış, hafif ve kolay entegre edilebilir PHP kütüphanesi.

## ✨ Özellikler

- 🔒 **Güvenli**: PDO Prepared Statements ile SQL Injection koruması
- 🚀 **Modern**: PHP 7.4+ uyumlu, nesne yönelimli tasarım
- 💬 **Tam Özellikli**: Mesaj gönderme, alma, silme ve yanıtlama
- 📧 **Email Bildirimleri**: Yeni mesaj bildirim desteği
- 🔍 **Sayfalama**: Büyük mesaj listeleri için sayfalama
- 🗑️ **Soft Delete**: Mesajlar her iki taraf silene kadar saklanır
- 📊 **Okunmamış Sayacı**: Gerçek zamanlı okunmamış mesaj takibi
- 🔄 **Konuşma Zincirleri**: Yanıt tabanlı mesaj dizileri

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7+ / MariaDB 10.2+
- PDO PHP Extension
- Session desteği

## 🚀 Kurulum

### 1. Dosyaları İndirin

```bash
git clone https://github.com/kullaniciadi/secure-php-messaging-system.git
cd secure-php-messaging-system
```

### 2. Veritabanını Oluşturun

```bash
mysql -u kullanici -p veritabani_adi < database.sql
```

veya phpMyAdmin'den `database.sql` dosyasını import edin.

### 3. Yapılandırma

```php
<?php
session_start();

require_once 'PrivateMessagingSystem.php';

$config = [
    'host' => 'localhost',
    'database' => 'veritabani_adi',
    'username' => 'kullanici_adi',
    'password' => 'sifre',
    'table_users' => 'users',
    'table_messages' => 'messages',
    'website_email' => 'noreply@siteniz.com'
];

$pms = new PrivateMessagingSystem($config);
```

## 💻 Kullanım

### Mesaj Gönderme

```php
// Kullanıcı oturumu açık olmalı
$_SESSION['user_id'] = 1;

$messageId = $pms->sendMessage(
    2,                           // Alıcı user ID
    "Merhaba, nasılsın?",       // Mesaj içeriği
    "Selamlar",                 // Konu
    0                           // Yanıt ID (opsiyonel)
);

if ($messageId) {
    echo "Mesaj gönderildi! ID: " . $messageId;
}
```

### Tüm Mesajları Listeleme

```php
$messages = $pms->getAllMessages(1, 20); // Sayfa 1, 20 mesaj

foreach ($messages as $message) {
    echo "<div class='message'>";
    echo "<strong>Gönderen:</strong> {$message->sender_firstname} {$message->sender_lastname}<br>";
    echo "<strong>Konu:</strong> {$message->subject}<br>";
    echo "<strong>Tarih:</strong> {$message->created_at}<br>";
    echo "</div>";
}
```

### Tek Mesaj Görüntüleme

```php
$data = $pms->getMessage(5); // Mesaj ID: 5

if ($data) {
    $message = $data['message'];
    $replies = $data['replies'];
    
    echo "<h3>{$message->subject}</h3>";
    echo "<p>{$message->message}</p>";
    
    foreach ($replies as $reply) {
        echo "<div class='reply'>{$reply->message}</div>";
    }
}
```

### Okunmamış Mesaj Sayısı

```php
$unreadCount = $pms->getUnreadCount();
echo "Okunmamış mesajınız: " . $unreadCount;
```

### Mesaj Silme

```php
// Tek mesaj silme
$pms->deleteMessage(5);

// Tüm konuşmayı silme
$pms->deleteConversation(5);
```

### Mesaja Yanıt Verme

```php
$replyId = $pms->sendMessage(
    2,                      // Alıcı
    "İyiyim, teşekkürler!", // Yanıt mesajı
    "Re: Selamlar",        // Konu
    5                      // Orijinal mesaj ID
);
```

## 📁 Dosya Yapısı

```
secure-php-messaging-system/
├── PrivateMessagingSystem.php    # Ana sınıf
├── database.sql                   # Veritabanı şeması
├── README.md                      # Bu dosya
├── LICENSE                        # MIT Lisans
└── examples/
    ├── send_message.php          # Mesaj gönderme örneği
    ├── inbox.php                 # Gelen kutusu örneği
    └── view_message.php          # Mesaj görüntüleme örneği
```

## 🗄️ Veritabanı Yapısı

### Users Tablosu
```sql
- id (Primary Key)
- first_name
- last_name
- email (Unique)
- password
- created_at
```

### Messages Tablosu
```sql
- id (Primary Key)
- user_to (Foreign Key -> users.id)
- user_from (Foreign Key -> users.id)
- subject
- message
- respond (Yanıt verilen mesaj ID)
- opened (0: Okunmadı, 1: Okundu)
- sender_delete (Gönderen sildi mi?)
- receiver_delete (Alıcı sildi mi?)
- created_at
```

## 🔒 Güvenlik Özellikleri

- ✅ PDO Prepared Statements (SQL Injection koruması)
- ✅ XSS koruması (htmlspecialchars)
- ✅ Session tabanlı kimlik doğrulama
- ✅ Input validasyonu
- ✅ Kullanıcı yetki kontrolü
- ✅ Hata yönetimi ve loglama

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/harika-ozellik`)
3. Değişikliklerinizi commit edin (`git commit -m 'Harika özellik eklendi'`)
4. Branch'inizi push edin (`git push origin feature/harika-ozellik`)
5. Pull Request oluşturun

## 📝 Yapılacaklar (TODO)

- [ ] Dosya eki desteği
- [ ] Grup mesajlaşma
- [ ] Mesaj arama özelliği
- [ ] Mesaj taslak kaydetme
- [ ] Önem derecesi işaretleme
- [ ] Mesaj arşivleme
- [ ] REST API endpoint'leri
- [ ] WebSocket ile gerçek zamanlı bildirimler

## ⚠️ Bilinen Sorunlar

Şu anda bilinen bir sorun bulunmamaktadır. Sorun bulursanız lütfen [issue açın](https://github.com/kullaniciadi/secure-php-messaging-system/issues).

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

## 👤 Yazar

**[Adınız]**

- GitHub: [@kullaniciadi](https://github.com/kullaniciadi)
- Email: email@example.com

## 🙏 Teşekkürler

Bu proje, güvenli mesajlaşma sistemlerine olan ihtiyaçtan doğmuştur ve topluluk katkılarına açıktır.

## 📚 Kaynaklar

- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [OWASP Security Guidelines](https://owasp.org/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)

---

⭐ **Projeyi beğendiyseniz yıldız vermeyi unutmayın!** ⭐
