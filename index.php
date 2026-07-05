<?php
// Yapbozun ilk parçası: Üst kısmı ve veritabanı bağlantısını dahil ediyoruz
include 'includes/header.php';

// SQL sorgumuzun temel halini yazıyoruz (Sadece yayınlanmış yazıları getirecek)
$sql = "SELECT p.*, c.name as category_name, u.username 
        FROM posts p 
        JOIN categories c ON p.category_id = c.id 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'published'";

$params = []; // SQL parametrelerini güvenli göndermek için boş dizi

// 1. DİNAMİK FİLTRELEME: Eğer URL'den kategori (slug) gelmişse
if (isset($_GET['category'])) {
    $cat_slug = trim($_GET['category']);
    $sql .= " AND c.slug = :cat_slug";
    $params['cat_slug'] = $cat_slug;
}

// 2. DİNAMİK FİLTRELEME: Eğer URL'den etiket (slug) gelmişse (Çoka-Çok İlişki Sorgusu)
if (isset($_GET['tag'])) {
    $tag_slug = trim($_GET['tag']);
    // Subquery (Alt sorgu) kullanarak köprü tablodan bu etikete bağlı yazıları süzüyoruz
    $sql .= " AND p.id IN (
                SELECT pt.post_id FROM post_tags pt 
                JOIN tags t ON pt.tag_id = t.id 
                WHERE t.slug = :tag_slug
             )";
    $params['tag_slug'] = $tag_slug;
}

// Yazıları id değerine göre tersten sıralıyoruz (En yeni yazı en üstte)
$sql .= " ORDER BY p.id DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    echo "<div class='alert alert-danger'>Yazılar yüklenirken bir hata oluştu.</div>";
}
?>

<?php if (isset($_GET['category'])): ?>
    <h2 style="margin-bottom: 30px; color: #2c3e50;">📂 Kategori: <span style="color:#2ecc71;"><?php echo htmlspecialchars($_GET['category']); ?></span> için sonuçlar</h2>
<?php elseif (isset($_GET['tag'])): ?>
    <h2 style="margin-bottom: 30px; color: #2c3e50;">🏷️ Etiket: <span style="color:#3498db;">#<?php echo htmlspecialchars($_GET['tag']); ?></span> için sonuçlar</h2>
<?php endif; ?>

<?php if (count($posts) > 0): ?>
    <?php foreach ($posts as $post): ?>
        <article class="blog-card">
            <?php if (!empty($post['image'])): ?>
                <img src="uploads/<?php echo $post['image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
            <?php endif; ?>
            
            <div class="blog-card-body">
                <span class="badge cat"><?php echo htmlspecialchars($post['category_name']); ?></span>
                
                <h2 class="blog-card-title">
                    <a href="post.php?slug=<?php echo $post['slug']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                </h2>
                
                <div class="blog-meta">
                    <span>✍️ Yazar: <strong><?php echo htmlspecialchars($post['username']); ?></strong></span>
                    <span>📅 Tarih: <?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                </div>
                
                <p style="color: #4a5568;">
                    <?php 
                        $strip_content = strip_tags($post['content']); // HTML etiketlerini temizle
                        echo mb_substr($strip_content, 0, 250, 'UTF-8') . '...'; 
                    ?>
                </p>
                
                <a href="post.php?slug=<?php echo $post['slug']; ?>" class="btn-read">Devamını Oku &rarr;</a>
            </div>
        </article>
    <?php endforeach; ?>
<?php else: ?>
    <div style="background: white; padding: 40px; text-align: center; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h3 style="color: #7f8c8d;">Henüz bu kritere uygun bir yazı yayınlanmadı.</h3>
        <a href="index.php" style="color: #2ecc71; text-decoration: none; font-weight: bold;">Anasayfaya Dön</a>
    </div>
<?php endif; ?>

<?php
// Yapbozun son parçası: Sağ paneli ve alt kısmı dahil edip sayfayı kapatıyoruz
include 'includes/footer.php';
?>