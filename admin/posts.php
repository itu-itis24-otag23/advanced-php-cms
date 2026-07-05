<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'user') {
    header("Location: login.php");
    exit;
}

require_once '../config/db.php';

$success = '';
$error = '';

// YAZI SİLME İŞLEMİ
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        // Önce yazının görseli varsa sunucudan (klasörden) silelim ki yer kaplamasın
        $img_stmt = $db->prepare("SELECT image FROM posts WHERE id = ?");
        $img_stmt->execute([$delete_id]);
        $post_img = $img_stmt->fetchColumn();
        
        if ($post_img && file_exists("../uploads/" . $post_img)) {
            unlink("../uploads/" . $post_img); // Dosyayı klasörden siler
        }
        
        // Şimdi veritabanından silelim (Köprü tablodaki veriler ON DELETE CASCADE sayesinde otomatik silinecek!)
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = "Yazı ve bağlı tüm verileri başarıyla silindi.";
    } catch (PDOException $e) {
        $error = "Silme başarısız: " . $e->getMessage();
    }
}

// SQL DETAYI: Yazıları çekerken hangi kategoride olduğunu ve kimin yazdığını JOIN ile birleştirerek çekiyoruz
$sql = "SELECT p.*, c.name as category_name, u.username 
        FROM posts p 
        JOIN categories c ON p.category_id = c.id 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.id DESC";
$posts = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yazı Yönetimi - CMS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f6f9; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; color: white; height: 100vh; position: fixed; }
        .sidebar h3 { text-align: center; padding: 20px 0; border-bottom: 1px solid #34495e; margin: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; }
        .sidebar ul li a:hover { background: #34495e; color: white; }
        
        .main-content { margin-left: 250px; padding: 30px; width: calc(100% - 250px); }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-add { background: #2ecc71; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; float: right; font-weight: bold; }
        
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: white; }
        .bg-success { background: #2ecc71; }
        .bg-warning { background: #f1c40f; color: #333; }
        .btn-delete { background: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 14px; }
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
        <li><a href="logout.php" style="background: #c0392b;">❌ Güvenli Çıkış</a></li>
    </ul>
</div>

<div class="main-content">
    <a href="add-post.php" class="btn-add">➕ Yeni Yazı Ekle</a>
    <h2>📝 Yazı Yönetimi</h2>
    <p>Sistemdeki tüm blog yazılarını buradan görebilir, yayından kaldırabilir veya silebilirsiniz.</p>

    <?php if(!empty($success)): ?> <div class="alert alert-success"><?php echo $success; ?></div> <?php endif; ?>
    <?php if(!empty($error)): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

    <div class="box">
        <table>
            <thead>
                <tr>
                    <th>Görsel</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Yazar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($posts) > 0): ?>
                    <?php foreach($posts as $post): ?>
                        <tr>
                            <td>
                                <?php if($post['image']): ?>
                                    <img src="../uploads/<?php echo $post['image']; ?>" width="60" height="40" style="object-fit: cover; border-radius:4px;">
                                <?php else: ?>
                                    <span style="color:#aaa; font-size:12px;">Yok</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                            <td><span style="color: #34495e;"><?php echo htmlspecialchars($post['category_name']); ?></span></td>
                            <td><em><?php echo htmlspecialchars($post['username']); ?></em></td>
                            <td>
                                <span class="badge <?php echo $post['status'] === 'published' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $post['status'] === 'published' ? 'Yayında' : 'Taslak'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></td>
                            <td>
                                <a href="posts.php?delete=<?php echo $post['id']; ?>" class="btn-delete" onclick="return confirm('Bu yazıyı silmek istediğinize emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999; padding: 20px;">Henüz hiç yazı eklenmemiş. Sağ üstten ilk yazınızı ekleyin!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>