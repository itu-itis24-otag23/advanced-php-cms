<?php
// Bu dosya her sayfanın en üstüne dahil edileceği için veritabanı bağlantısını da burada başlatabiliriz.
// Böylece her sayfada tek tek require_once '../config/db.php' yazmak zorunda kalmayız.
// Klasör yapısına dikkat: Bu dosya 'includes/' içinde olduğu için bir üst klasöre çıkıp 'config/'e girmeli.
require_once __DIR__ . '/../config/db.php';

// Menüde listelemek için tüm kategorileri çekiyoruz
try {
    $nav_categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $nav_categories = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelişmiş PHP & MySQL CMS Blog</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f8f9fa; color: #333; line-height: 1.6; }
        
        /* Navigasyon Barı */
        .navbar { background: #2c3e50; color: white; padding: 15px 0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { width: 85%; max-width: 1200px; margin: auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; color: #2ecc71; text-decoration: none; }
        .nav-menu { display: flex; gap: 20px; list-style: none; margin: 0; padding: 0; }
        .nav-menu li a { color: #edf2f7; text-decoration: none; font-size: 16px; font-weight: 500; transition: color 0.3s; }
        .nav-menu li a:hover { color: #2ecc71; }
        
        /* Ana İçerik Konteyneri */
        .main-container { width: 85%; max-width: 1200px; margin: 40px auto; display: flex; gap: 40px; }
        .content-area { flex: 3; }
        .sidebar-area { flex: 1; }
        
        /* Kart Tasarımları (Yazılar için) */
        .blog-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; flex-direction: column; }
        .blog-card img { width: 100%; height: 350px; object-fit: cover; }
        .blog-card-body { padding: 25px; }
        .blog-card-title { margin: 0 0 15px 0; font-size: 26px; }
        .blog-card-title a { color: #2c3e50; text-decoration: none; }
        .blog-card-title a:hover { color: #2ecc71; }
        .blog-meta { font-size: 14px; color: #7f8c8d; margin-bottom: 15px; display: flex; gap: 15px; flex-wrap: wrap; }
        .badge { background: #e2e8f0; color: #4a5568; padding: 3px 8px; border-radius: 4px; font-size: 12px; text-decoration: none; }
        .badge.cat { background: #e1f5fe; color: #0288d1; font-weight: bold; }
        
        /* Sağ Yan Panel (Sidebar) Kartları */
        .sidebar-widget { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .sidebar-widget h3 { margin-top: 0; border-bottom: 2px solid #2ecc71; padding-bottom: 8px; color: #2c3e50; }
        .widget-list { list-style: none; padding: 0; margin: 0; }
        .widget-list li { padding: 10px 0; border-bottom: 1px solid #edf2f7; }
        .widget-list li a { color: #4a5568; text-decoration: none; }
        .widget-list li a:hover { color: #2ecc71; padding-left: 5px; transition: all 0.3s; }
        
        /* Butonlar */
        .btn-read { display: inline-block; background: #2c3e50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 15px; transition: background 0.3s; }
        .btn-read:hover { background: #2ecc71; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">🚀 DevCMS</a>
        <ul class="nav-menu">
            <li><a href="index.php">Anasayfa</a></li>
            <?php foreach($nav_categories as $nav_cat): ?>
                <li><a href="index.php?category=<?php echo $nav_cat['slug']; ?>"><?php echo htmlspecialchars($nav_cat['name']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="content-area">