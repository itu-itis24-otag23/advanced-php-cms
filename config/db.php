<?php
// Veritabanı bağlantı bilgileri
$host     = 'localhost';
$db_name  = 'advanced_cms';
$username = 'root'; // XAMPP/WAMP için varsayılan kullanıcı adı genellikle root'tur
$password = '';     // Varsayılan şifre genellikle boştur (Windows için)

try {
    // Veritabanı bağlantısını başlatan PDO nesnesini oluşturuyoruz
    // C++'taki constructor mantığı gibi düşünebilirsin
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Hata yönetim modunu aktifleştiriyoruz (Hata oluşursa Exception fırlatacak)
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verileri çekerken varsayılan olarak ilişkisel dizi (associative array) olarak gelmesini sağlıyoruz
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // PHP emülasyonunu kapatıp gerçek "prepared statements" kullanmasını zorunlu kılıyoruz (Güvenlik için)
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // Bağlantıda bir hata oluşursa programı durdur ve hatayı ekrana bas
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>