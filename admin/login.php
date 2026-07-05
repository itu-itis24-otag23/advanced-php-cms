<?php
// PHP'de session (oturum) işlemlerini kullanabilmek için her sayfanın EN ÜSTÜNDE bu fonksiyonu çağırmalıyız.
session_start();

// Eğer kullanıcı zaten giriş yapmışsa, tekrar giriş sayfasını görmesin, doğrudan panelle yönlendirilsin.
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit;
}

// Veritabanı bağlantı dosyamızı dahil ediyoruz
require_once '../config/db.php';

$error = ''; // Hata mesajlarını tutacağımız değişken

// Formun POST metoduyla gönderilip gönderilmediğini kontrol ediyoruz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen verileri alıyoruz ve başındaki-sonundaki boşlukları temizliyoruz (trim)
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Girdilerin boş olup olmadığını kontrol ediyoruz
    if (empty($username) || empty($password)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        try {
            // SQL Injection'ı önlemek için PREPARED STATEMENT kullanıyoruz.
            // Soru işareti (?) veya isimlendirilmiş parametreler (:username) kullanabiliriz.
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // Kullanıcı bulunduysa ve girilen şifre veritabanındaki hash ile eşleşiyorsa
            if ($user && password_verify($password, $user['password'])) {
                
                // Kullanıcının rolü admin, editor veya author değilse (düz üyeyse) admin panele giremez
                if ($user['role'] === 'user') {
                    $error = "Bu alana erişim yetkiniz yok.";
                } else {
                    // Oturum verilerini tarayıcı hafızasına (Session) kaydediyoruz
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'];

                    // Başarılı giriş sonrası admin anasayfasına yönlendiriyoruz
                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = "Hatalı kullanıcı adı veya şifre!";
            }
        } catch (PDOException $e) {
            $error = "Sistemsel bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Girişi - CMS</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; border: 0; color: white; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>CMS Yönetim Paneli</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Kullanıcı Adı</label>
            <input type="text" name="username" id="username" required>
        </div>
        <div class="form-group">
            <label for="password">Şifre</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit">Giriş Yap</button>
    </form>
</div>

</body>
</html>