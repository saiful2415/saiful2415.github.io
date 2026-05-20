<?php
require_once '../config.php';

// Proteksi halaman: Hanya admin yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

// ==========================================
// 1. PROSES TAMBAH JURUSAN (CREATE)
// ==========================================
if (isset($_POST['tambah_jurusan'])) {
    $nama_jurusan = trim($_POST['nama_jurusan']);

    if (!empty($nama_jurusan)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO jurusan (nama_jurusan) VALUES (?)");
            $stmt->execute([$nama_jurusan]);
            $message = "Jurusan baru berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "Gagal menambah data: " . $e->getMessage();
        }
    } else {
        $error = "Nama Jurusan tidak boleh kosong!";
    }
}

// ==========================================
// 2. PROSES EDIT JURUSAN (UPDATE)
// ==========================================
if (isset($_POST['edit_jurusan'])) {
    $id           = $_POST['id'];
    $nama_jurusan = trim($_POST['nama_jurusan']);

    if (!empty($nama_jurusan)) {
        try {
            $stmt = $pdo->prepare("UPDATE jurusan SET nama_jurusan = ? WHERE id = ?");
            $stmt->execute([$nama_jurusan, $id]);
            $message = "Nama jurusan berhasil diperbarui!";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $error = "Nama Jurusan tidak boleh kosong!";
    }
}

// ==========================================
// 3. PROSES HAPUS JURUSAN (DELETE)
// ==========================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    try {
        // Catatan: Relasi database diset ON DELETE SET NULL pada tabel siswa,
        // jadi jika jurusan dihapus, siswa yang memilih jurusan ini pilihan jurusannya menjadi kosong (tidak ikut terhapus)
        $stmt = $pdo->prepare("DELETE FROM jurusan WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Jurusan berhasil dihapus dari sistem!";
    } catch (PDOException $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}

// ==========================================
// 4. PROSES IMPORT JURUSAN DARI CSV (IMPORT)
// ==========================================
if (isset($_POST['import_csv'])) {
    if (is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
        $handle = fopen($_FILES['file_csv']['tmp_name'], "r");
        
        // Lewati baris pertama (Header CSV: nama_jurusan)
        fgetcsv($handle, 1000, ",");
        
        $baris_berhasil = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (isset($data[0]) && !empty(trim($data[0]))) {
                $csv_nama_jurusan = trim($data[0]);
                try {
                    $stmt = $pdo->prepare("INSERT INTO jurusan (nama_jurusan) VALUES (?)");
                    $stmt->execute([$csv_nama_jurusan]);
                    $baris_berhasil++;
                } catch (PDOException $e) {
                    // Skip jika ada error struktural internal
                }
            }
        }
        fclose($handle);
        $message = "Import selesai. Berhasil memasukkan $baris_berhasil program keahlian/jurusan.";
    } else {
        $error = "Silakan lampirkan file .csv terlebih dahulu.";
    }
}

// ==========================================
// 5. QUERY MENAMPILKAN DATA JURUSAN (READ)
// ==========================================
$stmt = $pdo->query("SELECT * FROM jurusan ORDER BY id DESC");
$all_jurusan = $stmt->fetchAll();
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
                <a href="index.php">Dashboard</a>
                <a href="user.php">Data User</a>
                <a href="siswa.php">Data Calon Murid</a>
                <a href="jurusan.php" class="active">Data Jurusan</a>
                <a href="soal.php">Data Soal</a>
                <a href="hasil.php">Hasil Ujian</a>
                <a href="../logout.php" style="color: #ef4444; margin-left: 2rem;">Log Out</a>
            </nav>
        </header>

        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; color: #1e293b;">
            Manajemen Kompetensi / Jurusan
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
                    Tambah Jurusan Baru
                </h2>
                
                <form action="" method="POST" id="jurusan-form">
                    <input type="hidden" name="id" id="jurusan-id">

                    <div class="form-group">
                        <label class="form-label">Nama Jurusan / Program Keahlian</label>
                        <input type="text" name="nama_jurusan" id="form-nama-jurusan" required placeholder="Contoh: Rekayasa Perangkat Lunak" class="form-input">
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" name="tambah_jurusan" id="submit-btn" class="btn btn-primary" style="width: 100%;">Simpan Jurusan</button>
                        <button type="button" onclick="resetForm()" id="cancel-btn" class="btn hidden" style="background-color: #cbd5e1; color: #334155;">Batal</button>
                    </div>
                </form>

                <div style="margin-top: 2rem; pt-1.5rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
                    <h3 style="font-size: 0.75rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        Import Jurusan (.csv)
                    </h3>
                    <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <input type="file" name="file_csv" accept=".csv" required style="font-size: 0.875rem; color: #64748b;">
                        <button type="submit" name="import_csv" class="btn" style="background-color: #059669; color: #ffffff; font-size: 0.875rem; padding: 0.5rem 1rem;">
                            Upload & Import
                        </button>
                    </form>
                    <p style="font-size: 11px; color: #94a3b8; margin-top: 0.5rem; line-height: 1.4;">
                        *Format Excel ke CSV cukup 1 kolom: <br>
                        <code style="background-color: #f1f5f9; padding: 0.125rem 0.25rem; rounded: 0.25rem; color: #ef4444; font-family: monospace;">nama_jurusan</code>
                    </p>
                </div>
            </div>

            <div class="admin-card" style="grid-column: span 2;">
                <h2 style="font-size: 1.125rem; font-weight: 700; color: #0f172a; margin-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                    Daftar Jurusan Terdaftar
                </h2>
                <div class="table-responsive">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID (Ref)</th>
                                <th>Nama Jurusan / Program Keahlian</th>
                                <th style="text-align: center; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($all_jurusan) == 0): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #94a3b8; font-style: italic; padding: 2rem;">Belum ada data jurusan yang diinput.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($all_jurusan as $row): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 700; color: #64748b;"><?= $row['id'] ?></td>
                                    <td style="font-weight: 500; color: #1e293b;"><?= htmlspecialchars($row['nama_jurusan']) ?></td>
                                    <td style="text-align: center;">
                                        <button onclick="editJurusan(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_jurusan'], ENT_QUOTES) ?>')" class="btn btn-sm" style="background-color: #fef3c7; color: #d97706; margin-right: 0.25rem;">Edit</button>
                                        <a href="jurusan.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus jurusan ini? Siswa yang memilih jurusan ini akan otomatis berubah menjadi \'Belum memilih\'.')" class="btn btn-sm" style="background-color: #fee2e2; color: #ef4444;">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        function editJurusan(id, nama_jurusan) {
            // Ubah konteks Form menjadi EDIT
            document.getElementById('form-title').innerText = "Edit Nama Jurusan";
            
            document.getElementById('jurusan-id').value = id;
            document.getElementById('form-nama-jurusan').value = nama_jurusan;
            
            // Konfigurasi tombol kirim data menggunakan inline CSS kustom
            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'edit_jurusan');
            submitBtn.innerText = "Simpan Perubahan";
            submitBtn.style.backgroundColor = "#f59e0b"; // Warna Amber / Orange khas edit
            
            // Tampilkan tombol batal pemrosesan
            document.getElementById('cancel-btn').classList.remove('hidden');
        }

        function resetForm() {
            // Kembalikan form ke fungsi INPUT/TAMBAH BARU
            document.getElementById('form-title').innerText = "Tambah Jurusan Baru";
            document.getElementById('jurusan-id').value = "";
            document.getElementById('jurusan-form').reset();
            
            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'tambah_jurusan');
            submitBtn.innerText = "Simpan Jurusan";
            submitBtn.style.backgroundColor = "#2563eb"; // Kembalikan ke warna biru primer
            
            document.getElementById('cancel-btn').classList.add('hidden');
        }
    </script>
</body>
</html>