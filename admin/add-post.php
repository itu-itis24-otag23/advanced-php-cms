<?php
session_start();

// Güvenlik Duvarı
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'user') {
    header("Location: login.php");
    exit;
}

require_once '../config/db.php';

$error = '';
$success = '';

// Formda listelemek için mevcut Kategorileri ve Etiketleri veritabanından çekiyoruz
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$tags       = $db->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll();

// SEO Dostu Slug Fonksiyonu
function createSlug($text) {
    $find = array('Ç', 'ç', 'Ğ', 'ğ', 'İ', 'ı', 'Ö', 'ö', 'Ş', 'ş', 'Ü', 'ü');
    $replace = array('c', 'c', 'g', 'g', 'i', 'i', 'o', 'o', 's', 's', 'u', 'u');
    $text = str_replace($find, $replace, $text);
    $text = preg_replace('/[^A-Za-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

// FORM GÖNDERİLDİĞİNDE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    $title       = trim($_POST['title']);
    $content     = trim($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $status      = $_POST['status'];
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : []; // Seçilen etiket dizisi
    
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = "Lütfen başlık, içerik ve kategori alanlarını doldurun.";
    } else {
        $slug = createSlug($title);
        $db_image_name = null; // Varsayılan olarak resim yok

        // 1. GÜVENLİ DOSYA YÜKLEME (FILE UPLOAD) İŞLEMİ
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $file_tmp    = $_FILES['image']['tmp_name'];
            $file_name   = $_FILES['image']['name'];
            $file_size   = $_FILES['image']['size'];
            $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            // Güvenlik Kontrolü 1: Uzantı kontrolü
            if (!in_array($file_ext, $allowed_extensions)) {
                $error = "Sadece JPG, JPEG, PNG ve WEBP formatlarında resim yükleyebilirsiniz.";
            } 
            // Güvenlik Kontrolü 2: Boyut kontrolü (Maksimum 2MB = 2097152 Byte)
            elseif ($file_size > 2097152) {
                $error = "Resim boyutu 2 MB'tan büyük olamaz.";
            } 
            else {
                // Güvenlik Kontrolü 3: Çakışmayı önlemek için benzersiz isim üretme
                $db_image_name = uniqid('img_', true) . '.' . $file_ext;
                $upload_path   = '../uploads/' . $db_image_name;
                
                // Dosyayı geçici klasörden asıl klasöre taşıyoruz
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $error = "Resim yüklenirken bir hata oluştu.";
                    $db_image_name = null;
                }
            }
        }

        // Eğer dosya yüklemede veya girdilerde bir hata oluşmadıysa kaydetmeye başla
        if (empty($error)) {
            try {
                // PDO Transaction başlatıyoruz. Hata olursa hiçbirini kaydetmeyecek (Veri bütünlüğü için)
                $db->beginTransaction();

                // 2. YAZIYI VERİTABANINA EKLE
                $sql = "INSERT INTO posts (user_id, category_id, title, slug, content, image, status) 
                        VALUES (:user_id, :category_id, :title, :slug, :content, :image, :status)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'user_id'     => $_SESSION['user_id'],
                    'category_id' => $category_id,
                    'title'       => $title,
                    'slug'        => $slug,
                    'content'     => $content,
                    'image'       => $db_image_name,
                    'status'      => $status
                ]);

                // Az önce eklenen yazının otomatik oluşan ID'sini alıyoruz
                $post_id = $db->lastInsertId();

                // 3. ÇOKA-ÇOK İLİŞKİYİ (KÖPRÜ TABLOYU) KAYDET
                if (!empty($selected_tags)) {
                    $tag_sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
                    $tag_stmt = $db->prepare($tag_sql);
                    
                    foreach ($selected_tags as $tag_id) {
                        $tag_stmt->execute([$post_id, (int)$tag_id]);
                    }
                }

                // Her şey başarılıysa veritabanı işlemlerini onayla ve kilitle
                $db->commit();
                $success = "Yazı başarıyla yayınlandı!";
                
            } catch (PDOException $e) {
                // Hata oluşursa yapılan tüm işlemleri geri al (Rollback)
                $db->rollBack();
                $error = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Yazı Ekle - CMS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f6f9; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; color: white; height: 100vh; position: fixed; }
        .sidebar h3 { text-align: center; padding: 20px 0; border-bottom: 1px solid #34495e; margin: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 15px 20px; color: #bdc3c7; text-decoration: none; border-bottom: 1px solid #34495e; }
        .sidebar ul li a:hover { background: #34495e; color: white; }
        
        .main-content { margin-left: 250px; padding: 30px; width: calc(100% - 250px); }
        .box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input[type="text"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { height: 200px; resize: vertical; }
        
        .tags-container { display: flex; flex-wrap: wrap; gap: 10px; background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .tag-checkbox { display: flex; align-items: center; gap: 5px; cursor: pointer; }
        
        .btn-submit { background: #2ecc71; color: white; border: 0; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-submit:hover { background: #27ae60; }
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
    <h2>➕ Yeni Yazı Ekle</h2>
    <p>Ziyaretçilerinizle paylaşmak için zengin içerikli yeni bir blog yazısı oluşturun.</p>

    <?php if(!empty($success)): ?> <div class="alert alert-success"><?php echo $success; ?></div> <?php endif; ?>
    <?php if(!empty($error)): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

    <div class="box">
        <form action="add-post.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="title">Yazı Başlığı</label>
                <input type="text" name="title" id="title" placeholder="Harika bir başlık yazın..." required>
            </div>

            <div class="form-group">
                <label for="category_id">Kategori</label>
                <select name="category_id" id="category_id" required>
                    <option value="">-- Kategori Seçin --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Etiketler (Çoklu Seçim)</label>
                <div class="tags-container">
                    <?php if (count($tags) > 0): ?>
                        <?php foreach ($tags as $tag): ?>
                            <label class="tag-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                                #<?php echo htmlspecialchars($tag['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color:#aaa; font-size:14px;">Henüz hiç etiket eklenmemiş. Önce etiket yönetiminden ekleyin.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Kapak Görseli</label>
                <input type="file" name="image" id="image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="content">Yazı İçeriği</label>
                <textarea name="content" id="content" placeholder="İçeriğinizi buraya detaylıca yazın..." required></textarea>
            </div>

            <div class="form-group">
                <label for="status">Yayın Durumu</label>
                <select name="status" id="status">
                    <option value="published">Doğrudan Yayınla</option>
                    <option value="draft">Taslak Olarak Kaydet</option>
                </select>
            </div>

            <button type="submit" name="add_post" class="btn-submit">Yazıyı Kaydet ve Yayınla</button>
        </form>
    </div>
</div>

</body>
</html>