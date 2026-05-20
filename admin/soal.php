<?php
require_once '../config.php';

// Proteksi halaman: Hanya admin yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

// Daftar Konsentrasi Keahlian Definitif untuk validasi & dropdown
$list_kategori = [
    'Agribisnis Tanaman',
    'Agriteknologi Pengolahan Hasil Pertanian',
    'Teknik Jaringan Komputer & Telekomunikasi',
    'Teknik Otomotif'
];

// ==========================================
// 1. PROSES TAMBAH SOAL (CREATE)
// ==========================================
if (isset($_POST['tambah_soal'])) {
    $pertanyaan = trim($_POST['pertanyaan']);
    $kategori   = $_POST['kategori'];

    if (!empty($pertanyaan) && in_array($kategori, $list_kategori)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO soal (pertanyaan, kategori) VALUES (?, ?)");
            $stmt->execute([$pertanyaan, $kategori]);
            $message = "Soal instrumen baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "Gagal menyimpan soal: " . $e->getMessage();
        }
    } else {
        $error = "Pertanyaan wajib diisi dan kategori harus valid!";
    }
}

// ==========================================
// 2. PROSES EDIT SOAL (UPDATE)
// ==========================================
if (isset($_POST['edit_soal'])) {
    $id         = $_POST['id'];
    $pertanyaan = trim($_POST['pertanyaan']);
    $kategori   = $_POST['kategori'];

    if (!empty($pertanyaan) && in_array($kategori, $list_kategori)) {
        try {
            $stmt = $pdo->prepare("UPDATE soal SET pertanyaan = ?, kategori = ? WHERE id = ?");
            $stmt->execute([$pertanyaan, $kategori, $id]);
            $message = "Butir soal berhasil diperbarui!";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui soal: " . $e->getMessage();
        }
    } else {
        $error = "Pertanyaan wajib diisi dan kategori harus valid!";
    }
}

// ==========================================
// 3. PROSES HAPUS SOAL (DELETE)
// ==========================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM soal WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Soal berhasil dihapus dari bank instrumen!";
    } catch (PDOException $e) {
        $error = "Gagal menghapus soal: " . $e->getMessage();
    }
}

// ==========================================
// 4. PROSES IMPORT SOAL DARI CSV (IMPORT)
// ==========================================
if (isset($_POST['import_csv'])) {
    if (is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
        $handle = fopen($_FILES['file_csv']['tmp_name'], "r");
        
        // Lewati baris pertama (Header CSV: pertanyaan,kategori)
        fgetcsv($handle, 1000, ",");
        
        $baris_berhasil = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (!empty($data[0]) && !empty($data[1])) {
                $csv_pertanyaan = trim($data[0]);
                $csv_kategori   = trim($data[1]);

                // Validasi agar yang masuk hanya kategori yang sesuai konsentrasi keahlian resmi
                if (in_array($csv_kategori, $list_kategori)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO soal (pertanyaan, kategori) VALUES (?, ?)");
                        $stmt->execute([$csv_pertanyaan, $csv_kategori]);
                        $baris_berhasil++;
                    } catch (PDOException $e) {
                        // Skip jika baris bermasalah secara internal
                    }
                }
            }
        }
        fclose($handle);
        $message = "Import selesai. Berhasil memasukkan $baris_berhasil butir soal tes minat bakat.";
    } else {
        $error = "Silakan lampirkan file .csv instrumen soal yang valid.";
    }
}

// ==========================================
// 5. QUERY MENAMPILKAN DATA SOAL (READ)
// ==========================================
$stmt = $pdo->query("SELECT * FROM soal ORDER BY id DESC");
$all_soal = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal Minat Bakat - Admin</title>
    
    <link rel="stylesheet" href="/tesbaminat/assets/css/style.css">
</head>
<body>

    <div class="container">
        
        <header class="admin-header">
            <div class="admin-title">Panel Admin</div>
            <nav class="admin-nav-links">
                <a href="index.php">Dashboard</a>
                <a href="user.php">Data User</a>
                <a href="siswa.php">Data Calon Murid</a>
                <a href="jurusan.php">Data Jurusan</a>
                <a href="soal.php" class="active">Data Soal</a>
                <a href="hasil.php">Hasil Ujian</a>
                <a href="../logout.php" style="color: #ef4444; margin-left: 2rem;">Log Out</a>
            </nav>
        </header>

        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; color: #1e293b;">
            Manajemen Bank Soal Tes Minat Bakat
        </h1>

        <?php if ($message): ?>
            <div class="badge badge-success" style="display: block; padding: 0.75rem; margin-bottom: 1rem; border-radius: 0.5rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error" style="margin-bottom: 1rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
            
            <div class="admin-card">
                <h2 id="form-title" style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem; color: #0f172a; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                    Tambah Soal Baru
                </h2>
                
                <form action="" method="POST" id="soal-form">
                    <input type="hidden" name="id" id="soal-id">

                    <div class="form-group">
                        <label class="form-label">Pertanyaan / Deskripsi Indikator Minat</label>
                        <textarea name="pertanyaan" id="form-pertanyaan" rows="4" required placeholder="Contoh: Mengganti oli mesin, busi, dan membersihkan filter udara..." class="form-input" style="resize: vertical; min-height: 80px;"></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Konsentrasi Keahlian (Kategori)</label>
                        <select name="kategori" id="form-kategori" required class="form-input" style="background-color: #f8fafc; color: #334155; font-weight: 500;">
                            <option value="">-- Pilih Konsentrasi Keahlian --</option>
                            <?php foreach ($list_kategori as $kat): ?>
                                <option value="<?= $kat ?>"><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top: 1rem; background-color: #f8fafc; padding: 0.75rem; border-radius: 0.375rem; border: 1px dashed #cbd5e1; font-size: 0.75rem; color: #64748b; line-height: 1.5;">
                        <span style="font-weight: 700; color: #475569; display: block; margin-bottom: 0.25rem;">ℹ️ Info Pilihan Jawaban Siswa:</span>
                        <div>• Skor 1: Tidak Suka</div>
                        <div>• Skor 2: Kurang Suka</div>
                        <div>• Skor 3: Suka</div>
                        <div>• Skor 4: Sangat Suka</div>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" name="tambah_soal" id="submit-btn" class="btn btn-primary" style="width: 100%;">Simpan Soal</button>
                        <button type="button" onclick="resetForm()" id="cancel-btn" class="btn hidden" style="background-color: #cbd5e1; color: #334155;">Batal</button>
                    </div>
                </form>

                <div style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
                    <h3 style="font-size: 0.75rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        Import Soal Massal (.csv)
                    </h3>
                    <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <input type="file" name="file_csv" accept=".csv" required style="font-size: 0.875rem; color: #64748b;">
                        <button type="submit" name="import_csv" class="btn" style="background-color: #059669; color: #ffffff; font-size: 0.875rem; padding: 0.5rem 1rem;">
                            Upload & Eksekusi
                        </button>
                    </form>
                    <p style="font-size: 11px; color: #94a3b8; margin-top: 0.5rem; line-height: 1.4;">
                        *Format Excel ke CSV cukup 2 kolom: <br>
                        <code style="background-color: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 0.25rem; color: #ef4444; font-family: monospace;">pertanyaan,kategori</code>
                    </p>
                </div>
            </div>

            <div class="admin-card" style="grid-column: span 2;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 700; color: #0f172a; margin: 0;">Daftar Butir Instrumen</h2>
                    <span class="badge badge-info">Total: <?= count($all_soal) ?> Nomor</span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if (count($all_soal) == 0): ?>
                        <p style="text-align: center; color: #94a3b8; font-style: italic; padding: 3rem 0;">Belum ada butir instrumen soal di database.</p>
                    <?php endif; ?>

                    <?php foreach ($all_soal as $soal): ?>
                        <div style="padding: 1rem; background-color: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                    <span class="badge" style="background-color: #eff6ff; color: #1e40af; font-family: monospace; font-size: 10px;">ID #<?= $soal['id'] ?></span>
                                    <span class="badge" style="background-color: #f3e8ff; color: #6b21a8; font-size: 11px; font-weight: 600;">
                                        🎯 <?= htmlspecialchars($soal['kategori']) ?>
                                    </span>
                                </div>
                                <p style="color: #334155; font-size: 0.875rem; line-height: 1.6; margin: 0; font-weight: 500;">
                                    <?= nl2br(htmlspecialchars($soal['pernyataan'])) ?>
                                </p>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 0.35rem; min-width: 80px;">
                                <button onclick="editSoal(<?= $soal['id'] ?>, '<?= htmlspecialchars($soal['pernyataan'], ENT_QUOTES) ?>', '<?= htmlspecialchars($soal['kategori'], ENT_QUOTES) ?>')" class="btn btn-sm" style="background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; width: 100%; text-align: center;">Edit</button>
                                <a href="soal.php?hapus=<?= $soal['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus indikator soal ini?')" class="btn btn-sm" style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; width: 100%; text-align: center; text-decoration: none;">Hapus</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        function editSoal(id, pertanyaan, kategori) {
            document.getElementById('form-title').innerText = "Edit Butir Instrumen";
            
            document.getElementById('soal-id').value = id;
            document.getElementById('form-pertanyaan').value = pertanyaan;
            document.getElementById('form-kategori').value = kategori;
            
            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'edit_soal');
            submitBtn.innerText = "Simpan Perubahan";
            submitBtn.style.backgroundColor = "#f59e0b"; // Warna amber ketika state edit aktif
            
            document.getElementById('cancel-btn').classList.remove('hidden');
            
            // Scroll halus ke area atas agar fokus input form langsung tertuju
            window.scrollTo({top: 0, behavior: 'smooth'});
            document.documentElement.scrollTop = 0;
        }

        function resetForm() {
            document.getElementById('form-title').innerText = "Tambah Soal Baru";
            document.getElementById('soal-id').value = "";
            document.getElementById('soal-form').reset();
            
            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'tambah_soal');
            submitBtn.innerText = "Simpan Soal";
            submitBtn.style.backgroundColor = "#2563eb"; // Kembalikan ke warna default primer biru
            
            document.getElementById('cancel-btn').classList.add('hidden');
        }
    </script>
</body>
</html>