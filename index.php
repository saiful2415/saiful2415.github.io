<?php
require_once '../config.php';
if ($_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

// Count data untuk widget dashboard
$count_siswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$count_soal  = $pdo->query("SELECT COUNT(*) FROM soal")->fetchColumn();
$count_hasil = $pdo->query("SELECT COUNT(*) FROM hasil_tes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - SPMB</title>
    
    <link rel="stylesheet" href="/tesbaminat/assets/css/style.css">
</head>
<body>
    
    <div class="container">
        
        <header class="admin-header">
            <div class="admin-title">Panel Admin</div>
            <nav class="admin-nav-links">
                <a href="index.php" class="active">Dashboard</a>
                <a href="user.php">Data User</a>
                <a href="siswa.php">Data Calon Murid</a>
                <a href="jurusan.php">Data Jurusan</a>
                <a href="soal.php">Data Soal</a>
                <a href="hasil.php">Hasil Ujian</a>
                <a href="../logout.php" style="color: #ef4444; margin-left: 2rem;">Log Out</a>
            </nav>
        </header>

        <main class="admin-card">
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; color: #1e293b;">
                Ringkasan Sistem
            </h2>
            
            <div class="admin-grid admin-grid-3">
                
                <div class="widget-card" style="border-top: 4px solid #2563eb;">
                    <div class="widget-title">Total Pendaftar</div>
                    <div class="widget-val"><?= htmlspecialchars($count_siswa) ?></div>
                </div>

                <div class="widget-card" style="border-top: 4px solid #f59e0b;">
                    <div class="widget-title">Bank Soal</div>
                    <div class="widget-val"><?= htmlspecialchars($count_soal) ?></div>
                </div>

                <div class="widget-card" style="border-top: 4px solid #10b981;">
                    <div class="widget-title">Sudah Tes</div>
                    <div class="widget-val"><?= htmlspecialchars($count_hasil) ?></div>
                </div>

            </div>
        </main>

    </div>

</body>
</html>