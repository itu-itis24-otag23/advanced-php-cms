<?php
session_start();

// Güvenlik Duvarı
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'user') {
    header("Location: login.php");
    exit;
}

require_once '../config/db.php';

$success = '';
$error = '';

// 1. YORUM ONAYLAMA İŞLEMİ (GET)
if (isset($_GET['approve'])) {
    $approve_id = (int)$_GET['approve'];
    
    try {
        $stmt = $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
        $stmt->execute([$approve_id]);
        $success = "Yorum başarıyla onaylandı ve yayına alındı.";
    } catch (PDOException $e) {
        $error = "Onaylama işlemi başarısız: " . $e->getMessage();
    }
}

// 2. YORUM SİLME İŞLEMİ (GET)
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = "Yorum başarıyla silindi.";
    } catch (PDOException $e) {
        $error = "Silme işlemi başarısız: " . $e->getMessage();
    }
}

// 3. YORUMLARI LİSTELEME
// SQL DETAYI: Yorumun hangi yazıya yapıldığını başlığıyla birlikte görebilmek için posts tablosu ile JOIN yapıyoruz.
$sql = "SELECT c.*, p.title as post_title 
        FROM comments c 
        JOIN posts p ON c.post_id = p.id 
        ORDER BY c.status DESC, c.id DESC"; // Önce onay bekleyenler (pending) üste gelsin diye status DESC yaptık
$comments = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yorum Yönetimi - CMS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f6f9; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; color: white; height: 100vh; position: fixed; }
        .sidebar h3 { text-align: center; padding: 20px 0; border-bottom: 1px solid #34495e; margin: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; }
        .sidebar ul li a:hover { background: #34495e; color: white; }
        
        .main-content { margin-left: 250px; padding: 30px; width: calc(100% - 250px); }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: white; font-weight: bold; }
        .bg-success { background: #2ecc71; }
        .bg-warning { background: #e67e22; }
        
        .btn-approve { background: #2ecc71; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; margin-right: 5px; }
        .btn-approve:hover { background: #27ae60; }
        .btn-delete { background: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .btn-delete:hover { background: #c0392b; }
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
    <h2>💬 Yorum Yönetimi</h2>
    <p>Ziyaretçilerin yazılarınıza yaptığı yorumları inceleyin, onaylayın veya silin.</p>

    <?php if(!empty($success)): ?> <div class="alert alert-success"><?php echo $success; ?></div> <?php endif; ?>
    <?php if(!empty($error)): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

    <div class="box">
        <table>
            <thead>
                <tr>
                    <th>Yazar / E-posta</th>
                    <th>Yorum İçeriği</th>
                    <th>İlgili Yazı</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($comments) > 0): ?>
                    <?php foreach($comments as $comment): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong><br>
                                <small style="color:#777;"><?php echo htmlspecialchars($comment['author_email']); ?></small>
                            </td>
                            <td style="max-width: 300px; font-size: 14px;"><?php echo htmlspecialchars($comment['content']); ?></td>
                            <td><span style="color:#2980b9; font-size: 14px;"><?php echo htmlspecialchars($comment['post_title']); ?></span></td>
                            <td>
                                <span class="badge <?php echo $comment['status'] === 'approved' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $comment['status'] === 'approved' ? 'Onaylı' : 'Onay Bekliyor'; ?>
                                </span>
                            </td>
                            <td><small><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small></td>
                            <td>
                                <?php if($comment['status'] === 'pending'): ?>
                                    <a href="comments.php?approve=<?php echo $comment['id']; ?>" class="btn-approve">Onayla</a>
                                <?php endif; ?>
                                <a href="comments.php?delete=<?php echo $comment['id']; ?>" class="btn-delete" onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #999; padding: 20px;">Henüz hiç yorum yapılmamış.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>