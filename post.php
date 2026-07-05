<?php
// Üst kısmı ve veritabanı bağlantısını dahil ediyoruz
include 'includes/header.php';

// URL'den slug parametresi gelmediyse anasayfaya geri şutluyoruz
if (!isset($_GET['slug'])) {
    header("Location: index.php");
    exit;
}

$slug = trim($_GET['slug']);

// 1. YAZI DETAYLARINI VERİTABANINDAN ÇEKME (JOIN İLE)
$sql = "SELECT p.*, c.name as category_name, u.username 
        FROM posts p 
        JOIN categories c ON p.category_id = c.id 
        JOIN users u ON p.user_id = u.id 
        WHERE p.slug = :slug AND p.status = 'published' LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute(['slug' => $slug]);
$post = $stmt->fetch();

// Eğer bu slug ile eşleşen bir yazı bulunamadıysa anasayfaya yönlendiriyoruz
if (!$post) {
    header("Location: index.php");
    exit;
}

$post_id = $post['id']; // İleride yorumlar ve etiketler için kullanacağız

$comment_success = '';
$comment_error = '';

// 2. YENİ YORUM GÖNDERİLME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $author_name  = trim($_POST['author_name']);
    $author_email = trim($_POST['author_email']);
    $content      = trim($_POST['content']);
    
    if (empty($author_name) || empty($author_email) || empty($content)) {
        $comment_error = "Lütfen yorum formundaki tüm alanları doldurun.";
    } elseif (!filter_var($author_email, FILTER_VALIDATE_EMAIL)) {
        $comment_error = "Lütfen geçerli bir e-posta adresi girin.";
    } else {
        try {
            // Yorum doğrudan 'pending' (onay bekliyor) olarak veritabanına kaydolur
            $comment_sql = "INSERT INTO comments (post_id, author_name, author_email, content, status) 
                            VALUES (?, ?, ?, ?, 'pending')";
            $c_stmt = $db->prepare($comment_sql);
            $c_stmt->execute([$post_id, $author_name, $author_email, $content]);
            $comment_success = "Yorumunuz başarıyla alındı! Yönetici onayından sonra yayınlanacaktır.";
        } catch (PDOException $e) {
            $comment_error = "Yorum kaydedilirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

// 3. BU YAZIYA BAĞLI ONAYLANMIŞ YORUMLARI ÇEKME
$comments_stmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY id DESC");
$comments_stmt->execute([$post_id]);
$approved_comments = $comments_stmt->fetchAll();

// 4. BU YAZIYA BAĞLI ETİKETLERİ ÇEKME (ÇOKA-ÇOK JOIN SORGUSU)
$tags_stmt = $db->prepare("SELECT t.* FROM tags t 
                           JOIN post_tags pt ON t.id = pt.tag_id 
                           WHERE pt.post_id = ?");
$tags_stmt->execute([$post_id]);
$post_tags = $tags_stmt->fetchAll();
?>

<article class="blog-card" style="display: block;">
    <?php if (!empty($post['image'])): ?>
        <img src="uploads/<?php echo $post['image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width:100%; height:auto; max-height:450px; object-fit:cover;">
    <?php endif; ?>

    <div class="blog-card-body">
        <span class="badge cat"><?php echo htmlspecialchars($post['category_name']); ?></span>
        <h1 style="color: #2c3e50; margin: 15px 0; font-size: 32px;"><?php echo htmlspecialchars($post['title']); ?></h1>
        
        <div class="blog-meta">
            <span>✍️ Yazar: <strong><?php echo htmlspecialchars($post['username']); ?></strong></span>
            <span>📅 Tarih: <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></span>
            <span>💬 Yorum: (<?php echo count($approved_comments); ?>)</span>
        </div>

        <div style="color: #2d3748; font-size: 18px; line-height: 1.8; margin-top: 25px; white-space: pre-line;">
            <?php echo htmlspecialchars($post['content']); ?>
        </div>

        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #edf2f7;">
            <strong style="color: #718096; font-size: 14px;">ETİKETLER:</strong>
            <?php if (count($post_tags) > 0): ?>
                <?php foreach ($post_tags as $p_tag): ?>
                    <a href="index.php?tag=<?php echo $p_tag['slug']; ?>" class="badge" style="margin-left: 5px; background: #ebf8ff; color: #2b6cb0;">#<?php echo htmlspecialchars($p_tag['name']); ?></a>
                <?php endforeach; ?>
            <?php else: ?>
                <span style="color:#aaa; font-size:14px; margin-left: 5px;">Bu yazıya etiket eklenmemiş.</span>
            <?php endif; ?>
        </div>
    </div>
</article>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <h3 style="color: #2c3e50; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; margin-top:0;">💬 Yorumlar (<?php echo count($approved_comments); ?>)</h3>
    
    <?php if (count($approved_comments) > 0): ?>
        <?php foreach ($approved_comments as $com): ?>
            <div style="padding: 15px; border-bottom: 1px solid #edf2f7;">
                <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                    <strong>🤵 <?php echo htmlspecialchars($com['author_name']); ?></strong>
                    <small style="color:#aaa;"><?php echo date('d.m.Y H:i', strtotime($com['created_at'])); ?></small>
                </div>
                <p style="margin:0; color:#4a5568; font-size:15px;"><?php echo htmlspecialchars($com['content']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #a0aec0; text-align: center; padding: 15px 0; margin:0;">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
    <?php endif; ?>
</div>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <h3 style="color: #2c3e50; margin-top:0;">✏️ Yorum Yap</h3>
    
    <?php if(!empty($comment_success)): ?> <div style="background:#d4edda; color:#155724; padding:10px; border-radius:4px; margin-bottom:15px;"><?php echo $comment_success; ?></div> <?php endif; ?>
    <?php if(!empty($comment_error)): ?> <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:15px;"><?php echo $comment_error; ?></div> <?php endif; ?>

    <form action="post.php?slug=<?php echo $slug; ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <div style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Adınız Soyadınız</label>
                <input type="text" name="author_name" style="width:100%; padding:10px; border:1px solid #cbd5e0; border-radius:4px; box-sizing:border-box;" required>
            </div>
            <div style="flex: 1;">
                <label style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">E-posta Adresiniz</label>
                <input type="email" name="author_email" style="width:100%; padding:10px; border:1px solid #cbd5e0; border-radius:4px; box-sizing:border-box;" required>
            </div>
        </div>
        <div>
            <label style="display:block; margin-bottom:5px; font-weight:bold; font-size:14px;">Yorumunuz</label>
            <textarea name="content" style="width:100%; height:120px; padding:10px; border:1px solid #cbd5e0; border-radius:4px; box-sizing:border-box; resize:vertical;" required></textarea>
        </div>
        <button type="submit" name="submit_comment" style="background:#2ecc71; color:white; border:0; padding:12px 20px; border-radius:4px; font-weight:bold; cursor:pointer; align-self:flex-start; font-size:16px;">Yorumu Gönder</button>
    </form>
</div>

<?php
// Sağ paneli ve alt kısmı dahil edip kapatıyoruz
include 'includes/footer.php';
?>