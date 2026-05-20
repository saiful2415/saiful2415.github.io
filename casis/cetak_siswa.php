<?php
require_once '../config.php';

// 0. FUNGSI BANTU FORMAT TANGGAL INDONESIA
function tanggal_indonesia($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    
    // $pecahkan[2] = Tanggal, (int)$pecahkan[1] = Urutan Bulan, $pecahkan[0] = Tahun
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// Mengambil tanggal hari ini dalam format Indonesia
$current_date_text = tanggal_indonesia(date('Y-m-d'));

// 1. PROTEKSI AKSES: Harus login (baik admin maupun siswa)
if (!isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// 2. STRATEGI ROUTING IDENTITAS (Dynamic ID Selector)
if ($_SESSION['role'] === 'admin') {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        die("<div style='padding:20px; color:red; font-family:sans-serif;'><b>Kesalahan:</b> Admin harus menentukan ID Siswa yang ingin dicetak!</div>");
    }
    $user_id = (int)$_GET['id'];
    $tombol_kembali_url = "../admin/index.php"; 
} else {
    $user_id = (int)$_SESSION['user_id'];
    $tombol_kembali_url = "index.php"; 
}

if ($user_id <= 0) {
    die("Identitas Siswa Tidak Valid.");
}

// 3. QUERY DATA SISWA & REKAPITULASI SKOR MINAT
$query = "
    SELECT 
        u.id AS user_id,
        s.nama_lengkap,
        s.nisn,
        s.asal_sekolah,
        SUM(CASE WHEN q.kategori = 'Agribisnis Tanaman' THEN j.nilai ELSE 0 END) AS skor_at,
        SUM(CASE WHEN q.kategori = 'Agriteknologi Pengolahan Hasil Pertanian' THEN j.nilai ELSE 0 END) AS skor_aphp,
        SUM(CASE WHEN q.kategori = 'Teknik Jaringan Komputer & Telekomunikasi' THEN j.nilai ELSE 0 END) AS skor_tjkt,
        SUM(CASE WHEN q.kategori = 'Teknik Otomotif' THEN j.nilai ELSE 0 END) AS skor_to
    FROM user u
    JOIN siswa s ON u.id = s.user_id
    JOIN jawaban j ON u.id = j.user_id
    JOIN soal q ON j.soal_id = q.id
    WHERE u.id = ?
    GROUP BY u.id, s.nama_lengkap, s.nisn, s.asal_sekolah
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $siswa = $stmt->fetch();
} catch (PDOException $e) {
    die("Terjadi kegagalan sistem penarikan data: " . $e->getMessage());
}

if (!$siswa) {
    die("<div style='padding:20px; font-family:sans-serif; text-align:center;'>
            <h3>Hasil Evaluasi Belum Tersedia</h3>
            <p>Siswa yang bersangkutan belum mengambil atau belum menyelesaikan instrumen tes minat.</p>
            <a href='javascript:window.history.back()' style='color:blue;'>Kembali</a>
         </div>");
}

// 4. KALKULASI REKOMENDASI JURUSAN (NILAI TERTINGGI)
$skor = [
    'Agribisnis Tanaman' => $siswa['skor_at'],
    'Agriteknologi Pengolahan Hasil Pertanian' => $siswa['skor_aphp'],
    'Teknik Jaringan Komputer & Telekomunikasi' => $siswa['skor_tjkt'],
    'Teknik Otomotif' => $siswa['skor_to']
];

$max_skor = max($skor);
$rekomendasi_array = array_keys($skor, $max_skor);
$rekomendasi_final = implode(', ', $rekomendasi_array);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Hasil Tes - <?= htmlspecialchars($siswa['nama_lengkap']) ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background-color: #fff;
            line-height: 1.4;
            margin: 0;
            padding: 0.5rem 2.5rem;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Kop Surat Sesuai Gambar */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 0.3rem;
            margin-bottom: 1.2rem;
        }
        .kop-surat h1 {
            font-size: 1.35rem;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .kop-surat h2 {
            font-size: 1.2rem;
            margin: 2px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .kop-surat p {
            font-size: 0.8rem;
            margin: 0;
            font-style: italic;
        }
        
        /* Judul Dokumen */
        .judul-dokumen {
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 1.1rem;
            text-decoration: underline;
            margin-bottom: 1.2rem;
        }

        p.narasi {
            font-size: 0.95rem;
            margin: 0.4rem 0;
            text-align: justify;
        }

        /* Tabel Biodata */
        .tabel-biodata {
            width: 100%;
            margin: 0.8rem 0;
            border-collapse: collapse;
        }
        .tabel-biodata td {
            padding: 0.15rem 0;
            vertical-align: top;
            font-size: 0.95rem;
        }

        /* Tabel Skor Sesuai Gambar */
        .tabel-skor {
            width: 100%;
            border-collapse: collapse;
            margin: 0.8rem 0;
        }
        .tabel-skor th, .tabel-skor td {
            border: 1px solid #000;
            padding: 0.35rem 0.6rem;
            font-size: 0.95rem;
        }
        .tabel-skor th {
            font-weight: bold;
            text-align: center;
        }

        /* Kotak Rekomendasi Sesuai Gambar */
        .box-rekomendasi {
            border: 1px solid #000;
            padding: 0.5rem;
            margin: 1rem 0;
            text-align: center;
        }
        .box-rekomendasi .label {
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 0.2rem;
        }
        .box-rekomendasi .hasil {
            font-size: 1.1rem;
            font-weight: bold;
        }

        /* Catatan Kaki */
        .catatan-kaki {
            font-style: italic;
            font-size: 0.8rem;
            margin-top: 0.8rem;
            text-align: justify;
            line-height: 1.3;
        }

        /* Bagian Tanda Tangan */
        .ttd-container {
            margin-top: 1.2rem;
            display: flex;
            justify-content: flex-end;
        }
        .ttd-box {
            text-align: center;
            width: 250px;
            font-size: 0.95rem;
        }
        .ttd-space {
            height: 55px; 
        }
        .ttd-box p {
            margin: 0 0 2px 0;
        }

        /* Menu Navigasi Layar Monitor (Disembunyikan saat cetak) */
        .no-print-area {
            max-width: 800px;
            margin: 0 auto 1rem auto;
            background: #f8fafc;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: sans-serif;
        }
        .info-akses {
            font-size: 0.85rem;
            color: #64748b;
        }
        .info-akses strong {
            color: #0f172a;
            text-transform: uppercase;
        }
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-back { background-color: #64748b; color: white; border: 1px solid #475569; }
        .btn-print { background-color: #059669; color: white; border: 1px solid #047857; }

        @media print {
            @page {
                size: A4;
                margin: 1cm 1.5cm;
            }
            .no-print-area {
                display: none !important;
            }
            body {
                padding: 0;
                font-size: 11pt;
            }
            .print-container {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-area">
        <div style="font-size:0.85rem;">Mode Akses: <b><?= htmlspecialchars($_SESSION['role']) ?></b></div>
        <div class="btn-group">
            <button onclick="window.close()" class="btn btn-close">❌ Keluar / Tutup</button>
            <button onclick="window.print()" class="btn btn-print">🖨 Cetak Surat</button>
        </div>
    </div>

    <div class="print-container">
        
        <div class="kop-surat">
            <h1>Panitia Sistem Penerimaan Murid Baru (SPMB)</h1>
            <h2>UPT SMK NEGERI 7 LUWU UTARA</h2>
            <p>Jl. Trans Sulawesi, Desa Dandang, Kec. Sabbang Selatan, Kab. Luwu Utara 92955</p>
            <p>Email : smkn01sabbang@gmail.com | Website : https://smkn7luwuutara.sch.id</p>
        </div>

        <div class="judul-dokumen">
            Surat Keterangan Hasil Tes Bakat Minat
        </div>

        <p class="narasi">Berdasarkan hasil pengisian instrumen Pemetaan Bakat dan Minat pada Sistem Informasi Penerimaan Murid Baru, berikut adalah rincian data hasil evaluasi kompetensi calon Murid:</p>

        <table class="tabel-biodata">
            <tr>
                <td style="width: 140px;">Nama Lengkap</td>
                <td style="width: 20px;">:</td>
                <td style="font-weight: bold; text-transform: uppercase;"><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
            </tr>
            <tr>
                <td>NISN</td>
                <td>:</td>
                <td><?= htmlspecialchars($siswa['nisn'] ?: '-') ?></td>
            </tr>
            <tr>
                <td>Asal Sekolah</td>
                <td>:</td>
                <td style="text-transform: uppercase;"><?= htmlspecialchars($siswa['asal_sekolah'] ?: '-') ?></td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>:</td>
                <td><?= $current_date_text ?></td>
            </tr>
        </table>

        <p class="narasi">Nilai perolehan akumulatif aspek pemetaan pilihan Jurusan/Program Keahlian:</p>

        <table class="tabel-skor">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Jurusan/Program Keahlian</th>
                    <th style="width: 150px; text-align: center;">Skor Perolehan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>Agribisnis Tanaman</td>
                    <td style="text-align: center; font-weight: bold;"><?= (int)$siswa['skor_at'] ?></td>
                </tr>
                <tr>
                    <td style="text-align: center;">2</td>
                    <td>Agriteknologi Pengolahan Hasil Pertanian</td>
                    <td style="text-align: center; font-weight: bold;"><?= (int)$siswa['skor_aphp'] ?></td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td>
                    <td>Teknik Jaringan Komputer & Telekomunikasi</td>
                    <td style="text-align: center; font-weight: bold;"><?= (int)$siswa['skor_tjkt'] ?></td>
                </tr>
                <tr>
                    <td style="text-align: center;">4</td>
                    <td>Teknik Otomotif</td>
                    <td style="text-align: center; font-weight: bold;"><?= (int)$siswa['skor_to'] ?></td>
                </tr>
            </tbody>
        </table>

        <div class="box-rekomendasi">
            <div class="label">Rekomendasi Pilihan Program Keahlian Utama:</div>
            <div class="hasil">
                <?= htmlspecialchars($rekomendasi_final) ?>
            </div>
        </div>

        <p class="catatan-kaki">
            * Catatan: Surat keterangan ini diterbitkan oleh sistem secara otomatis sebagai bahan pertimbangan bagi Panitia SPMB dan guru Bimbingan Konseling (BK) dalam mengarahkan peminatan murid baru.
        </p>

        <div class="ttd-container">
            <div class="ttd-box">
                <p>Luwu Utara, <?= $current_date_text ?></p>
                <p>Guru BK,</p>
                <div class="ttd-space"></div>
                <p style="font-weight: bold; text-decoration: underline;">FENTI TIBAR, S.Pd., Gr.</p>
                <p style="font-size: 0.85rem; color: #333;">NIPPPK. 199805 21202521 2 101</p>
            </div>
        </div>

    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>