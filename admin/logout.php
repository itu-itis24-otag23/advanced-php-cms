<?php
session_start();
session_unset(); // Tüm session değişkenlerini temizler
session_destroy(); // Oturumu tamamen yok eder

header("Location: login.php"); // Giriş sayfasına yönlendirir
exit;
?>