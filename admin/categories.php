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

// TÜRKÇE KARAKTERLERİ SEO DOSTU SLUG'A ÇEVİREN FONKSİYON
function createSlug($text) {
    $find = array('Ç', 'ç', 'Ğ', 'ğ', 'İ', 'ı', 'Ö', 'ö', 'Ş', 'ş', 'Ü', 'ü');
    $replace = array('c', 'c', 'g', 'g', 'i', 'i', 'o', 'o', 's', 's', 'u', 'u');
    $text = str_replace($find, $replace, $text);
    $text = preg_replace('/[^A-Za-z0-9\-]/', '-', $text); // Özel karakterleri - yap
    $text = preg_replace('/-+/', '-', $text); // Peş peşe gelen -- işaretlerini tek yap
    $text = trim($text, '-'); // Başındaki ve sonundaki - işaretlerini sil
    return strtolower($text); // Hepsini küçük harfe çevir
}

// 1. KATEGORİ EKLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    if (empty($category_name)) {
        $error = "Kategori adı boş olamaz!";
    } else {
        $slug = createSlug($category_name);
        
        try {
            // Önce bu slug daha önce eklenmiş mi kontrol edelim (UNIQUE kısıtlaması hatası almamak için)
            $check = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $check->execute([$slug]);
            
            if ($check->rowCount() > 0) {
                $error = "Bu kategori zaten mevcut!";
            } else {
                // Veritabanına ekleme yapıyoruz
                $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmt->execute([$category_name, $slug]);
                $success = "Kategori başarıyla eklendi.";
            }
        } catch (PDOException $e) {
            $error = "Hata oluştu: " . $e->getMessage();
        }
    }
}

// 2. KATEGORİ SİLME İŞLEMİ (GET)
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete']; // Güvenlik için gelen ID'yi integer'a zorluyoruz
    
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = "Kategori başarıyla silindi.";
    } catch (PDOException $e) {
        $error = "Silme işlemi başarısız: " . $e->getMessage();
    }
}

// 3. MEVCUT KATEGORİLERİ LİSTELEME
$categories = $db->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kategori Yönetimi - CMS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f6f9; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; color: white; height: 100vh; position: fixed; }
        .sidebar h3 { text-align: center; padding: 20px 0; border-bottom: 1px solid #34495e; margin: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; }
        .sidebar ul li a:hover { background: #34495e; color: white; }
        .sidebar .logout { background: #c0392b; }
        
        .main-content { margin-left: 250px; padding: 30px; width: calc(100% - 250px); }
        .container { display: flex; gap: 30px; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-box { flex: 1; height: fit-content; }
        .list-box { flex: 2; }
        
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        form input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; box-sizing: border-box; }
        form button { background: #2ecc71; color: white; border: 0; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; }
        .btn-delete { background: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 14px; }
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
    <h2>📂 Kategori Yönetimi</h2>
    <p>Yazılarınızı sınıflandırmak için kategoriler oluşturun, düzenleyin veya silin.</p>

    <?php if(!empty($success)): ?> <div class="alert alert-success"><?php echo $success; ?></div> <?php endif; ?>
    <?php if(!empty($error)): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

    <div class="container">
        <div class="box form-box">
            <h3>Yeni Kategori Ekle</h3>
            <form action="categories.php" method="POST">
                <label>Kategori Adı</label>
                <input type="text" name="category_name" placeholder="Örn: Web Geliştirme" required>
                <button type="submit" name="add_category">Kaydet</button>
            </form>
        </div>

        <div class="box list-box">
            <h3>Mevcut Kategoriler</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kategori Adı</th>
                        <th>URL Uzantısı (Slug)</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($categories) > 0): ?>
                        <?php foreach($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                <td>
                                    <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="btn-delete" onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz? (Bağlı tüm yazılar da silinecektir!)')">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999;">Henüz hiç kategori eklenmemiş.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>