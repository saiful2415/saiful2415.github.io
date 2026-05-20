<?php
require_once '../config.php';

// Proteksi halaman: Hanya peran siswa yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Ambil data profil siswa
try {
    $stmt_siswa = $pdo->prepare("SELECT nama_lengkap, nisn, asal_sekolah FROM siswa WHERE user_id = ?");
    $stmt_siswa->execute([$user_id]);
    $siswa = $stmt_siswa->fetch();
    
    if (!$siswa) {
        die("Profil data siswa belum lengkap. Silakan hubungi admin.");
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}

// 2. Cek status apakah sudah pernah mengunci/menyimpan jawaban tes minat bakat
$sudah_tes = false;
try {
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM jawaban WHERE user_id = ?");
    $stmt_cek->execute([$user_id]);
    if ($stmt_cek->fetchColumn() > 0) {
        $sudah_tes = true;
    }
} catch (PDOException $e) {
    $sudah_tes = false;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Calon Siswa - SPMB</title>
    <style>
        body { font-family: sans-serif; background-color: #f1f5f9; color: #1e293b; margin: 0; padding: 2rem; }
        .container { max-width: 650px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); position: relative; }
        
        /* Barisan Header Utama */
        .header-dashboard { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; margin-bottom: 1rem; }
        h1 { margin: 0; color: #0f172a; font-size: 1.5rem; }
        
        .info-profil { margin: 1.5rem 0; background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; }
        .info-profil p { margin: 0.4rem 0; }
        .alert-box { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: bold; text-align: center; }
        .alert-info { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .alert-success { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        
        /* Gaya Tombol */
        .btn { display: block; width: 100%; text-align: center; padding: 0.75rem; color: white; text-decoration: none; font-weight: bold; border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        .btn-start { background-color: #2563eb; border: 1px solid #1d4ed8; }
        .btn-start:hover { background-color: #1d4ed8; }
        .btn-print { background-color: #059669; border: 1px solid #047857; }
        .btn-print:hover { background-color: #047857; }
        
        /* Tombol Log Out Khusus */
        .btn-logout { background-color: #ef4444; color: white; text-decoration: none; padding: 0.4rem 0.8rem; font-size: 0.85rem; font-weight: bold; border-radius: 4px; border: 1px solid #dc2626; transition: background 0.2s; }
        .btn-logout:hover { background-color: #dc2626; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-dashboard">
        <h1>Portal Calon Murid Baru</h1>
        <a href="../logout.php" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">🚪 Keluar</a>
    </div>
    
    <div class="info-profil">
        <p><strong>Nama Lengkap:</strong> <?= htmlspecialchars($siswa['nama_lengkap']) ?></p>
        <p><strong>NISN:</strong> <?= htmlspecialchars($siswa['nisn'] ?: '-') ?></p>
        <p><strong>Asal Sekolah:</strong> <?= htmlspecialchars($siswa['asal_sekolah'] ?: '-') ?></p>
    </div>

    <?php if (!$sudah_tes): ?>
        <div class="alert-box alert-info">
            Anda belum mengikuti Tes Minat & Bakat Pemetaan Jurusan.
        </div>
        <a href="tes.php" class="btn btn-start">🚀 Mulai Tes Minat & Bakat</a>
    <?php else: ?>
        <div class="alert-box alert-success">
            ✓ Anda telah menyelesaikan Tes Minat & Bakat!
        </div>
        <a href="cetak_siswa.php" target="_blank" class="btn btn-print">🖨️ Cetak Surat Keterangan Hasil (PDF)</a>
    <?php endif; ?>
</div>

</body>
</html>