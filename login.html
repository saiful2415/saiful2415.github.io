<?php
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } else {
            // Cek apakah casis sudah pernah tes
            $stmt_cek = $pdo->prepare("SELECT id FROM hasil_tes WHERE user_id = ?");
            $stmt_cek->execute([$user['id']]);
            if ($stmt_cek->fetch()) {
                $_SESSION['sudah_tes'] = true;
            }
            header("Location: casis/index.php");
        }
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem - SPMB</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-card">
        <h2 class="login-title">Login Sistem</h2>
        
        <?php if($error): ?>
            <div class="alert-error" style="text-align: center; font-size: 0.875rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" required class="form-input" placeholder="Masukkan username" autocomplete="off">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required class="form-input" placeholder="Masukkan password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.625rem;">Masuk</button>
        </form>
    </div>

</body>
</html>