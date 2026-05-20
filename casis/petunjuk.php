<?php
require_once '../config.php';

// Proteksi halaman: Hanya user dengan role 'siswa' yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ==========================================
// 1. AMBIL DATA SISWA & VALIDASI
// ==========================================
try {
    $stmt_siswa = $pdo->prepare("SELECT * FROM siswa WHERE user_id = ?");
    $stmt_siswa->execute([$user_id]);
    $siswa = $stmt_siswa->fetch();
    
    if (!$siswa) {
        die("Profil data siswa belum lengkap. Silakan hubungi admin.");
    }

    // Cek apakah siswa sudah pernah mengerjakan tes sebelumnya
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM jawaban WHERE user_id = ?");
    $stmt_cek->execute([$user_id]);
    if ($stmt_cek->fetchColumn() > 0) {
        // Jika sudah pernah tes, paksa kembali ke dashboard utama
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petunjuk Pelaksanaan Tes - SPMB</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <header class="bg-blue-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <span class="text-lg font-bold tracking-wide">Peta Bakat Minat SMK</span>
            <div class="flex items-center space-x-4">
                <span class="text-sm font-medium">🎒 <?= htmlspecialchars($siswa['nama_lengkap']) ?></span>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-3xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col justify-center">
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-slate-800 p-6 text-white text-center sm:text-left">
                <h1 class="text-xl font-bold flex items-center justify-center sm:justify-start gap-2">
                    📢 Konfirmasi & Petunjuk Alur Tes
                </h1>
                <p class="text-xs text-slate-400 mt-1 font-mono">Peserta: <?= htmlspecialchars($siswa['nama_lengkap']) ?> (NISN: <?= htmlspecialchars($siswa['nisn'] ?: '-') ?>)</p>
            </div>

            <div class="p-6 sm:p-8 space-y-6 text-slate-600 text-sm leading-relaxed">
                
                <div class="space-y-3">
                    <h2 class="font-bold text-slate-800 text-base">Sebelum memulai, mohon perhatikan poin-poin penting berikut:</h2>
                    <ol class="list-decimal list-inside space-y-2.5 pl-1">
                        <li>Pastikan koneksi internet Anda <span class="font-bold text-slate-800">stabil</span> selama proses pengisian instrumen berlangsung.</li>
                        <li>Pilihlah jawaban yang <span class="font-bold text-blue-600">paling menggambarkan diri Anda sendiri</span>, bukan karena paksaan atau mengikuti pilihan teman.</li>
                        <li>Tidak ada batasan waktu kaku, namun rata-rata instrumen ini diselesaikan dalam waktu <span class="font-bold text-slate-800">10-15 menit</span>.</li>
                        <li>Setiap butir pernyataan wajib dijawab. Sistem tidak akan mengizinkan Anda mengirimkan lembar jawaban jika ada nomor yang terlewat.</li>
                    </ol>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-gray-200">
                    <span class="font-bold block text-slate-700 mb-2">📊 Pilihan Skala Jawaban:</span>
                    <p class="text-xs text-gray-500 mb-3">Anda diminta memilih salah satu dari 4 tingkat kesukaan terhadap pernyataan kegiatan yang disajikan:</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs font-bold text-slate-700">
                        <div class="p-2.5 bg-white rounded border shadow-sm border-gray-200">1 : Sangat Tidak Suka</div>
                        <div class="p-2.5 bg-white rounded border shadow-sm border-gray-200">2 : Kurang Suka</div>
                        <div class="p-2.5 bg-white rounded border shadow-sm border-gray-200">3 : Suka</div>
                        <div class="p-2.5 bg-white rounded border shadow-sm border-gray-200">4 : Sangat Suka</div>
                    </div>
                </div>

                <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-900 p-4 rounded shadow-sm text-xs space-y-1">
                    <span class="font-bold block">🚨 PENTING:</span>
                    <p>Tes ini <span class="font-bold">hanya dapat diikuti 1 kali</span>. Setelah Anda menekan tombol "Mulai" dan mengirimkan jawaban di akhir, Anda tidak dapat mengulang atau memperbaiki pilihan jawaban Anda kembali.</p>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row gap-3">
                    <a href="index.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-6 py-3 rounded-xl transition w-full sm:w-1/3">
                        Kembali
                    </a>
                    <a href="tes.php" class="block text-center bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5 w-full sm:w-2/3">
                        Saya Mengerti, Mulai Tes sekarang
                    </a>
                </div>

            </div>
        </div>

    </main>

    <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-400">
        &copy; 2026 Sistem Penerimaan Murid Baru (SPMB) - SMK.
    </footer>

</body>
</html>