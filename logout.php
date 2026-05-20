<?php
// 1. Inisialisasi atau aktifkan session yang sedang berjalan
session_start();

// 2. Hapus semua variabel session yang tersimpan di memori server
$_SESSION = array();

// 3. Jika sistem menggunakan session berbasis cookie, hancurkan juga cookie-nya
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Hancurkan / destroy session secara total di sisi server
session_destroy();

// 5. Alihkan halaman (redirect) kembali ke halaman login utama
header("location: login.php");
exit;
?>