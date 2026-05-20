<?php
require_once 'config.php';

try {
    // Hapus akun admin lama jika ada ganjalan duplikat
    $pdo->query("DELETE FROM users WHERE username = 'admin'");

    // Generate password hash baru yang valid
    $username = 'admin';
    $password_baru = 'admin123';
    $password_hashed = password_hash($password_baru, PASSWORD_BCRYPT);
    $role = 'admin';

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password_hashed, $role]);

    echo "Akun Admin berhasil di-reset!<br>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>admin123</b><br><br>";
    echo "<a href='login.php'>Kembali ke Halaman Login</a>";
} catch (PDOException $e) {
    echo "Gagal mereset admin: " . $e->getMessage();
}
?>