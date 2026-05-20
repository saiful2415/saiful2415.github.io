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

// Proteksi halaman: Hanya admin yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil ID user dari parameter URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    die("ID Siswa tidak valid.");
}

// Ambil data detail siswa dan akumulasi skornya
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
    die("Gagal mengambil data: " . $e->getMessage());
}

if (!$siswa) {
    die("Data hasil tes siswa tidak ditemukan atau siswa belum menyelesaikan tes.");
}

// Hitung rekomendasi jurusan
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
    <title>Hasil Tes Bakat Minat - <?= htmlspecialchars($siswa['nama_lengkap']) ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background-color: #fff;
            line-height: 1.3;
            margin: 0;
            padding: 1rem 2rem;
        }
        .print-container {
            max-width: 750px;
            margin: 0 auto;
        }
        
        /* Kop Surat Lebih Ringkas */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #000;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .kop-teks {
            flex-grow: 1;
            text-align: center;
        }
        .kop-teks h1 {
            font-size: 1.25rem;
            margin: 0;
            text-transform: uppercase;
        }
        .kop-teks h2 {
            font-size: 1.05rem;
            margin: 1px 0;
            text-transform: uppercase;
        }
        .kop-teks p {
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
            margin-bottom: 1rem;
        }

        /* Teks Narasi */
        p.narasi {
            font-size: 0.95rem;
            margin: 0.5rem 0;
        }

        /* Tabel Biodata Rapat */
        .tabel-biodata {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: collapse;
        }
        .tabel-biodata td {
            padding: 0.2rem 0;
            vertical-align: top;
            font-size: 1rem;
        }

        /* Tabel Skor Rapat */
        .tabel-skor {
            width: 100%;
            border-collapse: collapse;
            margin: 0.75rem 0;
        }
        .tabel-skor th, .tabel-skor td {
            border: 1px solid #000;
            padding: 0.4rem 0.6rem;
            text-align: left;
            font-size: 0.95rem;
        }
        .tabel-skor th {
            background-color: #f2f2f2 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            text-align: center;
        }

        /* Kotak Hasil Rekomendasi Rapat */
        .box-rekomendasi {
            border: 1.5px solid #000;
            padding: 0.6rem;
            margin: 1rem 0;
            text-align: center;
            background-color: #f9f9f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .box-rekomendasi .label {
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        .box-rekomendasi .hasil {
            font-size: 1.15rem;
            font-weight: bold;
        }

        /* Bagian Tanda Tangan Rapat */
        .ttd-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
        }
        .ttd-box {
            text-align: center;
            width: 220px;
            font-size: 1rem;
        }
        .ttd-space {
            height: 55px; /* Dipendekkan agar menghemat ruang vertical */
        }
        .ttd-box p {
            margin: 0 0 2px 0;
        }

        /* Navigasi di layar komputer */
        .no-print-area {
            max-width: 750px;
            margin: 0 auto 1rem auto;
            display: flex;
            gap: 1rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-back { background-color: #64748b; color: white; border: none; }
        .btn-print { background-color: #059669; color: white; border: none; }

        /* Pengaturan Cetak Paksa 1 Halaman */
        @media print {
            @page {
                size: A4;
                margin: 1cm 1.5cm 1cm 1.5cm; /* Set margin cetak kertas lebih ramping */
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
        <button onclick="window.close()" class="btn btn-close">❌ Keluar / Tutup</button>
        <button onclick="window.print()" class="btn btn-print">🖨 Cetak Surat</button>
    </div>

    <div class="print-container">
        
        <!-- KOP SURAT -->
        <div class="kop-surat">
            <div class="kop-teks">
                <h1>Panitia Sistem Penerimaan Murid Baru (SPMB)</h1>
                <h2>UPT SMK NEGERI 7 LUWU UTARA</h2>
                <p>Jl. Trans Sulawesi, Desa Dandang, Kec. Sabbang Selatan, Kab. Luwu Utara 92955</p>
                <p>Email : smkn01sabbang@gmail.com | Website : https://smkn7luwuutara.sch.id</p>
            </div>
        </div>

        <div class="judul-dokumen">
            Surat Keterangan Hasil Tes Bakat Minat
        </div>

        <p class="narasi">Berdasarkan hasil pengisian instrumen Pemetaan Bakat dan Minat pada Sistem Informasi Penerimaan Murid Baru, berikut adalah rincian data hasil evaluasi kompetensi calon Murid:</p>

        <!-- BIODATA SISWA -->
        <table class="tabel-biodata">
            <tr>
                <td style="width: 140px;">Nama Lengkap</td>
                <td style="width: 15px;">:</td>
                <td style="font-weight: bold;"><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
            </tr>
            <tr>
                <td>NISN</td>
                <td>:</td>
                <td><?= htmlspecialchars($siswa['nisn'] ?: '-') ?></td>
            </tr>
            <tr>
                <td>Asal Sekolah</td>
                <td>:</td>
                <td><?= htmlspecialchars($siswa['asal_sekolah'] ?: '-') ?></td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>:</td>
                <td><?= $current_date_text ?></td>
            </tr>
        </table>

        <p class="narasi">Nilai perolehan akumulatif aspek pemetaan pilihan Jurusan/Program Keahlian:</p>

        <!-- TABEL HASIL SKOR -->
        <table class="tabel-skor">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Jurusan/Program Keahlian</th>
                    <th style="width: 130px; text-align: center;">Skor Perolehan</th>
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

        <!-- KOTAK KESIMPULAN REKOMENDASI -->
        <div class="box-rekomendasi">
            <div class="label">Rekomendasi Pilihan Program Keahlian Utama:</div>
            <div class="hasil">
                <?= htmlspecialchars($rekomendasi_final) ?>
            </div>
        </div>

        <p style="font-style: italic; font-size: 0.85rem; margin: 0.5rem 0;">* Catatan: Surat keterangan ini diterbitkan oleh sistem secara otomatis sebagai bahan pertimbangan bagi Panitia SPMB dan guru Bimbingan Konseling (BK) dalam mengarahkan peminatan murid baru.</p>

        <!-- TANDA TANGAN -->
        <div class="ttd-container">
            <div class="ttd-box">
                <p>Luwu Utara, <?= $current_date_text ?></p>
                <p>Ketua Panitia SPMB,</p>
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
            }, 500);
        });
    </script>
</body>
</html>