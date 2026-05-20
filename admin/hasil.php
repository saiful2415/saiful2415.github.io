<?php
require_once '../config.php';

// Proteksi halaman: Hanya admin yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 1. Ambil data semua siswa yang sudah mengikuti tes bakat minat
$query = "
    SELECT 
        u.id AS user_id,
        u.username,
        s.nama_lengkap,
        s.nisn,
        s.asal_sekolah,
        -- Hitung total skor per konsentrasi keahlian
        SUM(CASE WHEN q.kategori = 'Agribisnis Tanaman' THEN j.nilai ELSE 0 END) AS skor_at,
        SUM(CASE WHEN q.kategori = 'Agriteknologi Pengolahan Hasil Pertanian' THEN j.nilai ELSE 0 END) AS skor_aphp,
        SUM(CASE WHEN q.kategori = 'Teknik Jaringan Komputer & Telekomunikasi' THEN j.nilai ELSE 0 END) AS skor_tjkt,
        SUM(CASE WHEN q.kategori = 'Teknik Otomotif' THEN j.nilai ELSE 0 END) AS skor_to
    FROM user u
    JOIN siswa s ON u.id = s.user_id
    JOIN jawaban j ON u.id = j.user_id
    JOIN soal q ON j.soal_id = q.id
    GROUP BY u.id, u.username, s.nama_lengkap, s.nisn, s.asal_sekolah
    ORDER BY s.nama_lengkap ASC
";

try {
    $stmt = $pdo->query($query);
    $hasil_tes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data hasil tes: " . $e->getMessage());
}

// Fungsi bantu untuk menentukan rekomendasi jurusan berdasarkan skor tertinggi (Teks Murni agar aman dari XSS)
function dapatkanRekomendasiTeks($at, $aphp, $tjkt, $to) {
    $skor = [
        'Agribisnis Tanaman' => $at,
        'Agriteknologi Pengolahan Hasil Pertanian' => $aphp,
        'Teknik Jaringan Komputer & Telekomunikasi' => $tjkt,
        'Teknik Otomotif' => $to
    ];
    
    // Cari nilai tertinggi
    $max_skor = max($skor);
    
    // Jika belum ada tes atau semua nilai 0
    if ($max_skor == 0) {
        return '';
    }
    
    // Ambil nama jurusan yang memiliki nilai tertinggi
    $rekomendasi = array_keys($skor, $max_skor);
    
    // Jika ada nilai kembar (tie), gabungkan dengan koma
    return implode(', ', $rekomendasi);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - SPMB</title>
    
    <link rel="stylesheet" href="/tesbaminat/assets/css/style.css">
    
    <style>
        /* CSS tambahan khusus untuk modul cetak laporan */
        .report-header-flex {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem; 
            gap: 1rem;
        }
        .col-skor {
            text-align: center; 
            font-weight: 600; 
            font-size: 1rem;
        }
        .btn-print-sm {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background-color: #0284c7;
            color: white;
            padding: 0.35rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-print-sm:hover {
            background-color: #0369a1;
        }
        
        /* Aturan Cetak (Print Stylesheet) */
        @media print {
            .no-print, .admin-header, .col-aksi { 
                display: none !important; 
            }
            body { 
                background-color: #fff; 
                color: #000;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .container { 
                padding: 0 !important; 
                max-width: 100% !important;
            }
            .admin-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }
            .table-admin th, .table-admin td {
                border: 1px solid #cbd5e1 !important; /* Beri border tegas saat dicetak */
                padding: 8px !important;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <header class="admin-header no-print">
            <div class="admin-title">Panel Admin</div>
            <nav class="admin-nav-links">
                <a href="index.php">Dashboard</a>
                <a href="user.php">Data User</a>
                <a href="siswa.php">Data Calon Murid</a>
                <a href="jurusan.php">Data Jurusan</a>
                <a href="soal.php">Data Soal</a>
                <a href="hasil.php" class="active">Hasil Ujian</a>
                <a href="../logout.php" style="color: #ef4444; margin-left: 2rem;">Log Out</a>
            </nav>
        </header>

        <div class="report-header-flex">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Laporan Hasil Tes Bakat Minat</h1>
                <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0 0;">Hasil akumulasi nilai pemetaan pilihan Konsentrasi Keahlian Calon Siswa Baru.</p>
            </div>
            <button onclick="window.print()" class="btn btn-primary no-print" style="background-color: #059669; white-space: nowrap; cursor: pointer;">
                🖨️ Cetak Semua Laporan
            </button>
        </div>

        <div class="admin-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 1rem; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1rem; font-weight: 700; color: #334155; margin: 0;">Data Rekomendasi Calon Murid</h2>
                <span class="badge badge-info">Total Peserta: <?= count($hasil_tes) ?></span>
            </div>
            
            <div class="table-responsive">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Identitas Calon Murid</th>
                            <th style="text-align: center; width: 110px; background-color: #f0fdf4; color: #166534;">Agribisnis Tanaman</th>
                            <th style="text-align: center; width: 110px; background-color: #fffbeb; color: #92400e;">Agriteknologi (PHP)</th>
                            <th style="text-align: center; width: 110px; background-color: #eff6ff; color: #1e40af;">TJKT</th>
                            <th style="text-align: center; width: 110px; background-color: #fef2f2; color: #991b1b;">Teknik Otomotif</th>
                            <th style="background-color: #eef2ff; color: #312e81; width: 220px;">Rekomendasi Keahlian Utama</th>
                            <th class="col-aksi no-print" style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($hasil_tes) == 0): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #94a3b8; font-style: italic; padding: 3rem;">
                                    Belum ada data pendaftar yang menyelesaikan tes bakat minat.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($hasil_tes as $index => $row): ?>
                            <?php 
                                $rekomendasi = dapatkanRekomendasiTeks($row['skor_at'], $row['skor_aphp'], $row['skor_tjkt'], $row['skor_to']); 
                            ?>
                            <tr>
                                <td style="color: #94a3b8; font-family: monospace;"><?= $index + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;">NISN: <?= htmlspecialchars($row['nisn'] ?: '-') ?></div>
                                    <div style="font-size: 0.75rem; color: #475569; margin-top: 0.125rem;">🎒 <?= htmlspecialchars($row['asal_sekolah'] ?: '-') ?></div>
                                </td>
                                <td class="col-skor" style="background-color: rgba(240, 253, 244, 0.4); color: #166534; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;"><?= (int)$row['skor_at'] ?></td>
                                <td class="col-skor" style="background-color: rgba(255, 251, 235, 0.4); color: #92400e; border-right: 1px solid #e2e8f0;"><?= (int)$row['skor_aphp'] ?></td>
                                <td class="col-skor" style="background-color: rgba(239, 246, 255, 0.4); color: #1e40af; border-right: 1px solid #e2e8f0;"><?= (int)$row['skor_tjkt'] ?></td>
                                <td class="col-skor" style="background-color: rgba(254, 242, 242, 0.4); color: #991b1b; border-right: 1px solid #e2e8f0;"><?= (int)$row['skor_to'] ?></td>
                                
                                <td style="background-color: rgba(238, 242, 255, 0.3); border-left: 1px solid #e2e8f0; vertical-align: middle;">
                                    <?php if ($rekomendasi === ''): ?>
                                        <span style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">Belum Mengikuti Tes</span>
                                    <?php else: ?>
                                        <span class="badge" style="display: inline-block; background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; font-weight: 700; padding: 0.35rem 0.6rem; border-radius: 0.375rem; font-size: 0.85rem;">
                                            💡 <?= htmlspecialchars($rekomendasi) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-aksi no-print" style="vertical-align: middle; text-align: center;">
                                    <?php if ($rekomendasi !== ''): ?>
                                        <a href="cetak_siswa.php?id=<?= urlencode($row['user_id']) ?>" target="_blank" class="btn-print-sm">
                                            🖨️ Cetak
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-print-sm" style="background-color: #cbd5e1; color: #64748b; cursor: not-allowed;" disabled>
                                            🖨️ Cetak
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>