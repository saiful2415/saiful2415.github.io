<?php
require_once '../config.php';
if ($_SESSION['role'] !== 'casis') { header("Location: ../login.php"); exit; }

// Proteksi ganda dari URL langsung jika sudah tes
$stmt_cek = $pdo->prepare("SELECT id FROM hasil_tes WHERE user_id = ?");
$stmt_cek->execute([$_SESSION['user_id']]);
if ($stmt_cek->fetch()) {
    $_SESSION['sudah_tes'] = true;
    header("Location: index.php");
    exit;
}

// Ambil semua soal
$soal_stmt = $pdo->query("SELECT * FROM soal");
$daftar_soal = $soal_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skor = 0;
    foreach ($daftar_soal as $s) {
        $jawaban_user = $_POST['soal_' . $s['id']] ?? '';
        if ($jawaban_user === $s['jawaban_benar']) {
            $skor += 10; // Sesuaikan bobot nilai instrumen Anda
        }
    }

    // Simpan Ke Hasil Tes
    $insert = $pdo->prepare("INSERT INTO hasil_tes (user_id, skor) VALUES (?, ?)");
    $insert->execute([$_SESSION['user_id'], $skor]);

    $_SESSION['sudah_tes'] = true;
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Lembar Kerja Ujian</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 border-b pb-2 text-blue-600">Pengerjaan Instrumen Soal</h2>
        <form action="" method="POST">
            <?php foreach ($daftar_soal as $index => $s): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded border">
                    <p class="font-medium mb-2"><?= ($index+1) ?>. <?= htmlspecialchars($s['pertanyaan']) ?></p>
                    <div class="space-y-2 pl-4">
                        <label class="block"><input type="radio" name="soal_<?= $s['id'] ?>" value="A" required> A. <?= htmlspecialchars($s['opsi_a']) ?></label>
                        <label class="block"><input type="radio" name="soal_<?= $s['id'] ?>" value="B"> B. <?= htmlspecialchars($s['opsi_b']) ?></label>
                        <label class="block"><input type="radio" name="soal_<?= $s['id'] ?>" value="C"> C. <?= htmlspecialchars($s['opsi_c']) ?></label>
                        <label class="block"><input type="radio" name="soal_<?= $s['id'] ?>" value="D"> D. <?= htmlspecialchars($s['opsi_d']) ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" onclick="return confirm('Apakah Anda yakin selesai? Jawaban tidak dapat diubah kembali.')" class="bg-green-600 text-white px-6 py-3 rounded font-bold hover:bg-green-700">Kirim Jawaban</button>
        </form>
    </div>
</body>
</html>