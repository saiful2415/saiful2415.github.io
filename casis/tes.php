<?php
require_once '../config.php';

// Proteksi halaman: Hanya siswa yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ==========================================
// 1. AMBIL DATA PROFIL SISWA
// ==========================================
try {
    $stmt_siswa = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE user_id = ?");
    $stmt_siswa->execute([$user_id]);
    $siswa = $stmt_siswa->fetch();
    
    if (!$siswa) {
        die("Profil data siswa belum lengkap. Silakan hubungi admin.");
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}

// ==========================================
// 2. VALIDASI: JIKA SUDAH PERNAH TES
// ==========================================
try {
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM jawaban WHERE user_id = ?");
    $stmt_cek->execute([$user_id]);
    if ($stmt_cek->fetchColumn() > 0) {
        // Jika sudah pernah tes, langsung lempar ke halaman cetak hasil
        header("Location: cetak_siswa.php");
        exit;
    }
} catch (PDOException $e) {
    // Toleransi jika tabel jawaban baru saja dibuat/kosong
}

// ==========================================
// 3. PROSES KETIKA TOMBOL SELESAI DIKLIK
// ==========================================
if (isset($_POST['submit_jawaban'])) {
    $jawaban_siswa = isset($_POST['soal']) ? $_POST['soal'] : [];

    if (!empty($jawaban_siswa)) {
        try {
            $pdo->beginTransaction();

            $stmt_insert = $pdo->prepare("INSERT INTO jawaban (user_id, soal_id, nilai) VALUES (?, ?, ?)");
            foreach ($jawaban_siswa as $soal_id => $nilai) {
                $stmt_insert->execute([$user_id, $soal_id, (int)$nilai]);
            }

            $pdo->commit();
            
            // KE DISINI: Dialihkan langsung ke halaman cetak hasil rekomendasi siswa
            header("Location: cetak_siswa.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal menyimpan jawaban: " . $e->getMessage();
        }
    } else {
        $error = "Mohon isi pernyataan instrumen terlebih dahulu!";
    }
}

// ==========================================
// 4. AMBIL DAFTAR SOAL SECARA ACAK (RANDOM)
// ==========================================
try {
    $stmt_soal = $pdo->query("SELECT * FROM soal ORDER BY RAND()");
    $daftar_soal = $stmt_soal->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat butir soal: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Instrumen Tes Minat - SPMB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        
        <?php if (isset($error)): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form id="form-tes" action="" method="POST" onsubmit="return konfirmasiSelesai()">
            
            <div class="card-soal">
                
                <div class="header-baris">
                    <div>
                        <p class="meta-peserta">Peserta Tes</p>
                        <p class="nama-peserta">🎒 <?= htmlspecialchars($siswa['nama_lengkap']) ?></p>
                        <h2 id="judul-nomor" class="judul-nomor">Soal No. 1</h2>
                    </div>
                    <button type="button" onclick="toggleNavigasi()" id="btn-toggle-nav" class="btn-toggle">
                        Sembunyikan Navigasi
                    </button>
                </div>

                <?php if (count($daftar_soal) == 0): ?>
                    <div style="padding: 3rem; text-align: center; color: #9ca3af; font-style: italic;">
                        Belum ada data soal di database. Silakan isi tabel 'soal' terlebih dahulu melalui phpMyAdmin.
                    </div>
                <?php endif; ?>

                <?php foreach ($daftar_soal as $index => $soal): 
                    $teks_soal = '';
                    if (isset($soal['pernyataan'])) {
                        $teks_soal = $soal['pernyataan'];
                    } elseif (isset($soal['soal'])) {
                        $teks_soal = $soal['soal'];
                    }
                ?>
                    <div class="block-soal <?php echo $index === 0 ? '' : 'hidden'; ?>" data-index="<?php echo $index; ?>" id="blok-<?= $index ?>">
                        <p class="teks-pernyataan">
                            <?= htmlspecialchars($teks_soal) ?>
                        </p>

                        <div class="grid-opsi">
                            <?php 
                            $opsi = [1 => 'Tidak Suka', 2 => 'Kurang Suka', 3 => 'Suka', 4 => 'Sangat Suka'];
                            foreach ($opsi as $val => $teks): 
                            ?>
                                <label class="label-opsi">
                                    <input type="radio" name="soal[<?= $soal['id'] ?>]" value="<?= $val ?>" onclick="tandaiSudahDijawab(<?= $index ?>)" class="radio-input">
                                    <span><?= $teks ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($daftar_soal) > 0): ?>
                <div class="footer-baris">
                    <button type="button" id="btn-prev" onclick="gantiSoal(-1)" class="btn-prev" disabled>
                        Sebelumnya
                    </button>
                    
                    <div id="wrapper-aksi-kanan">
                        <button type="button" id="btn-next" onclick="gantiSoal(1)" class="btn-next">
                            Berikutnya
                        </button>
                        
                        <button type="submit" name="submit_jawaban" id="btn-submit" class="btn-submit hidden">
                            ✓ Kirim Jawaban
                        </button>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <?php if (count($daftar_soal) > 0): ?>
            <div id="panel-navigasi" class="panel-navigasi">
                <h3 class="judul-nav">Navigasi Nomor Soal</h3>
                
                <div class="flex-wrap-nav">
                    <?php foreach ($daftar_soal as $index => $soal): ?>
                        <button type="button" id="nav-btn-<?= $index ?>" onclick="lompatKeSoal(<?= $index ?>)" class="nav-btn <?php echo $index === 0 ? 'nav-active' : ''; ?>">
                            <?= $index + 1 ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="flex-petunjuk">
                    <div class="status-item">
                        <div class="kotak-status" style="background-color: #2563eb;"></div>
                        <span>Sedang Dibuka</span>
                    </div>
                    <div class="status-item">
                        <div class="kotak-status" style="background-color: #10b981;"></div>
                        <span>Sudah Dijawab</span>
                    </div>
                    <div class="status-item">
                        <div class="kotak-status" style="background-color: #ffffff; border: 1px solid #d1d5db;"></div>
                        <span>Belum Dijawab</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </form>
    </div>

    <script>
        let currentIndex = 0;
        const totalSoal = <?= count($daftar_soal) ?>;
        const blocks = document.querySelectorAll('.block-soal');
        const panelNav = document.getElementById('panel-navigasi');

        function updateView() {
            if (totalSoal === 0) return;

            blocks.forEach((block, idx) => {
                if(idx === currentIndex) {
                    block.classList.remove('hidden');
                } else {
                    block.classList.add('hidden');
                }
            });

            document.getElementById('judul-nomor').innerText = "Soal No. " + (currentIndex + 1);
            document.getElementById('btn-prev').disabled = (currentIndex === 0);
            
            if (currentIndex === totalSoal - 1) {
                document.getElementById('btn-next').classList.add('hidden');
                document.getElementById('btn-submit').classList.remove('hidden');
            } else {
                document.getElementById('btn-next').classList.remove('hidden');
                document.getElementById('btn-submit').classList.add('hidden');
            }

            for(let i=0; i<totalSoal; i++) {
                const navBtn = document.getElementById('nav-btn-' + i);
                if(i === currentIndex) {
                    navBtn.classList.add('nav-active');
                } else {
                    navBtn.classList.remove('nav-active');
                }
            }
        }

        function gantiSoal(arah) {
            currentIndex += arah;
            if(currentIndex < 0) currentIndex = 0;
            if(currentIndex >= totalSoal) currentIndex = totalSoal - 1;
            updateView();
        }

        function lompatKeSoal(index) {
            currentIndex = index;
            updateView();
        }

        function tandaiSudahDijawab(index) {
            const navBtn = document.getElementById('nav-btn-' + index);
            navBtn.classList.add('nav-answered');
        }

        function toggleNavigasi() {
            const btn = document.getElementById('btn-toggle-nav');
            if(panelNav.classList.contains('hidden')) {
                panelNav.classList.remove('hidden');
                btn.innerText = "Sembunyikan Navigasi";
            } else {
                panelNav.classList.add('hidden');
                btn.innerText = "Tampilkan Navigasi";
            }
        }

        function konfirmasiSelesai() {
            let terisi = 0;
            for(let i = 0; i < blocks.length; i++) {
                const radios = blocks[i].querySelectorAll('input[type="radio"]');
                let checked = false;
                radios.forEach(r => { if(r.checked) checked = true; });
                if(checked) terisi++;
            }

            if(terisi < totalSoal) {
                alert("Peringatan: Anda baru mengisi " + terisi + " dari " + totalSoal + " nomor. Mohon lengkapi semua nomor sebelum mengirim jawaban!");
                return false;
            }
            return confirm("Apakah Anda yakin seluruh jawaban sudah sesuai dan ingin mengakhiri tes ini?");
        }
    </script>
</body>
</html>