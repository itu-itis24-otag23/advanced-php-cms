<?php
// Oturum işlemlerini başlatıyoruz
session_start();

// GÜVENLİK DUVARI: Eğer session'da kullanıcı ID'si yoksa VEYA rolü 'admin'/'editor'/'author' değilse
// Giriş yapmamış demektir. Doğrudan login.php sayfasına geri şutluyoruz.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'user') {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısı (İleride istatistikleri çekmek için kullanacağız)
require_once '../config/db.php';
// İSTATİSTİK SORGULARI
try {
    // 1. Toplam Yazı Sayısı
    $total_posts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();

    // 2. Toplam Kategori Sayısı
    $total_categories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

    // 3. Onay Bekleyen Yorum Sayısı (status = 'pending')
    $pending_comments = $db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    // Hata durumunda sistemin çökmemesi için varsayılan 0 değerlerini atıyoruz
    $total_posts = 0;
    $total_categories = 0;
    $pending_comments = 0;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>CMS Yönetim Paneli</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: #f4f6f9; display: flex; }
        
        /* Sol Menü (Sidebar) */
        .sidebar { width: 250px; background: #2c3e50; color: white; height: 100vh; position: fixed; }
        .sidebar h3 { text-align: center; padding: 20px 0; border-bottom: 1px solid #34495e; margin: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; }
        .sidebar ul li a:hover { background: #34495e; color: white; }
        .sidebar .logout { background: #c0392b; }
        .sidebar .logout:hover { background: #e74c3c; }

        /* Sağ İçerik Alanı */
        .main-content { margin-left: 250px; padding: 30px; width: calc(100% - 250px); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* İstatistik Kartları */
        .stats-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex: 1; border-left: 5px solid #007bff; }
        .card.categories { border-left-color: #2ecc71; }
        .card.comments { border-left-color: #f1c40f; }
        .card h4 { margin: 0 0 10px 0; color: #666; }
        .card p { margin: 0; font-size: 24px; font-weight: bold; color: #333; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>CMS Panel</h3>
    <ul>
        <li><a href="index.php">📊 Kontrol Paneli</a></li>
        <li><a href="posts.php">📝 Yazı Yönetimi</a></li>
        <li><a href="categories.php">📂 Kategori Yönetimi</a></li>
        <li><a href="tags.php">🏷️ Etiket Yönetimi</a></li>
        <li><a href="comments.php">💬 Yorum Onayları</a></li>
        <li><a href="logout.php" class="logout">❌ Güvenli Çıkış</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="header">
        <h2>Hoş Geldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h2>
        <span style="background: #e17055; color: white; padding: 5px 10px; border-radius: 4px; font-size: 14px;">
            Rol: <?php echo ucfirst($_SESSION['role']); ?>
        </span>
    </div>

    <div class="stats-container">
    <div class="card">
        <h4>Toplam Yazı</h4>
        <p><?php echo $total_posts; ?></p>
    </div>
    <div class="card categories">
        <h4>Kategoriler</h4>
        <p><?php echo $total_categories; ?></p>
    </div>
    <div class="card comments">
        <h4>Onay Bekleyen Yorum</h4>
        <p style="<?php echo $pending_comments > 0 ? 'color: #e74c3c;' : ''; ?>">
            <?php echo $pending_comments; ?>
        </p>
    </div>
</div>

    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3>Sistem Durumu</h3>
        <p>Gelişmiş İçerik Yönetim Sisteminizin yönetim paneline başarıyla giriş yaptınız. Sol menüyü kullanarak içeriklerinizi yönetmeye başlayabilirsiniz.</p>
    </div>
</div>

</body>
</html>