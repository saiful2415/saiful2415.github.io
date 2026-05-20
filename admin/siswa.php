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
// 1. PROSES TAMBAH SISWA BARU
// ==========================================
if (isset($_POST['tambah_siswa'])) {
    $username     = trim($_POST['username']);
    $password     = trim($_POST['password']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nisn         = trim($_POST['nisn']);
    $asal_sekolah = trim($_POST['asal_sekolah']);

    if (!empty($username) && !empty($password) && !empty($nama_lengkap)) {
        try {
            $pdo->beginTransaction();

            // Cek apakah username sudah terdaftar
            $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
            $stmt_cek->execute([$username]);
            if ($stmt_cek->fetchColumn() > 0) {
                throw new Exception("Username sudah digunakan oleh akun lain!");
            }

            // Insert ke tabel user
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt_user = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'siswa')");
            $stmt_user->execute([$username, $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // Insert ke tabel siswa
            $stmt_siswa = $pdo->prepare("INSERT INTO siswa (user_id, nama_lengkap, nisn, asal_sekolah) VALUES (?, ?, ?, ?)");
            $stmt_siswa->execute([$user_id, $nama_lengkap, $nisn, $asal_sekolah]);

            $pdo->commit();
            $message = "Data calon siswa baru berhasil ditambahkan!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        $error = "Kolom Username, Password, dan Nama Lengkap wajib diisi!";
    }
}

// ==========================================
// BARU: PROSES IMPORT DATA SISWA VIA CSV
// ==========================================
if (isset($_POST['import_siswa'])) {
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file_csv']['tmp_name'];
        $filename = $_FILES['file_csv']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (strtolower($ext) === 'csv') {
            if (($handle = fopen($file_tmp, "r")) !== FALSE) {
                $inserted = 0;
                $skipped = 0;
                
                try {
                    $pdo->beginTransaction();

                    // Query persiapan untuk efisiensi eksekusi massal
                    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
                    $stmt_user = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'siswa')");
                    $stmt_siswa = $pdo->prepare("INSERT INTO siswa (user_id, nama_lengkap, nisn, asal_sekolah) VALUES (?, ?, ?, ?)");

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Pastikan kolom esensial (username, password, nama) terisi
                        if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                            $skipped++;
                            continue;
                        }

                        $username     = trim($data[0]);
                        $password     = trim($data[1]);
                        $nama_lengkap = trim($data[2]);
                        $nisn         = isset($data[3]) ? trim($data[3]) : '';
                        $asal_sekolah = isset($data[4]) ? trim($data[4]) : '';

                        // Cek duplikasi username
                        $stmt_cek->execute([$username]);
                        if ($stmt_cek->fetchColumn() > 0) {
                            $skipped++;
                            continue;
                        }

                        // Input ke tabel user
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $stmt_user->execute([$username, $hashed_password]);
                        $user_id = $pdo->lastInsertId();

                        // Input ke tabel siswa
                        $stmt_siswa->execute([$user_id, $nama_lengkap, $nisn, $asal_sekolah]);
                        $inserted++;
                    }
                    
                    fclose($handle);
                    $pdo->commit();
                    $message = "Berhasil mengimpor $inserted data siswa. (Lewati/Gagal: $skipped)";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    fclose($handle);
                    $error = "Gagal melakukan import data: " . $e->getMessage();
                }
            } else {
                $error = "File CSV tidak dapat dibaca.";
            }
        } else {
            $error = "Format file salah! Harap unggah file berformat .csv";
        }
    } else {
        $error = "Silakan pilih file CSV terlebih dahulu.";
    }
}

// ==========================================
// 2. PROSES EDIT PROFIL SISWA
// ==========================================
if (isset($_POST['edit_siswa'])) {
    $id           = $_POST['id'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nisn         = trim($_POST['nisn']);
    $asal_sekolah = trim($_POST['asal_sekolah']);

    if (!empty($nama_lengkap)) {
        try {
            $stmt = $pdo->prepare("UPDATE siswa SET nama_lengkap = ?, nisn = ?, asal_sekolah = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $nisn, $asal_sekolah, $id]);
            $message = "Profil calon siswa berhasil diperbarui!";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $error = "Nama lengkap siswa wajib diisi!";
    }
}

// ==========================================
// 3. PROSES HAPUS SISWA
// ==========================================
if (isset($_GET['hapus'])) {
    $user_id = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM user WHERE id = ? AND role = 'siswa'");
        $stmt->execute([$user_id]);
        header("Location: siswa.php?status=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
    $message = "Data siswa dan akun login berhasil dihapus dari sistem!";
}

// ==========================================
// 4. QUERY BACA DATA SISWA (READ)
// ==========================================
try {
    $query = "
        SELECT s.*, u.username 
        FROM siswa s 
        JOIN user u ON s.user_id = u.id 
        ORDER BY s.id DESC
    ";
    $stmt = $pdo->query($query);
    $all_siswa = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat data siswa: " . $e->getMessage());
}
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
                <a href="siswa.php" class="active">Data Calon Murid</a>
                <a href="jurusan.php">Data Jurusan</a>
                <a href="soal.php">Data Soal</a>
                <a href="hasil.php">Hasil Ujian</a>
                <a href="../logout.php" style="color: #ef4444; margin-left: 2rem;">Log Out</a>
            </nav>
        </header>

        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; color: #1e293b;">
            Manajemen Data Calon Murid (SPMB)
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

        <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- FORM REGISTRASI MANUAL -->
                <div class="admin-card">
                    <h2 id="form-title" style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem; color: #0f172a; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                        Registrasi Siswa Baru
                    </h2>
                    
                    <form action="" method="POST" id="siswa-form">
                        <input type="hidden" name="id" id="siswa-id">

                        <div id="akun-fields">
                            <div class="form-group">
                                <label class="form-label">Username Login</label>
                                <input type="text" name="username" id="form-username" placeholder="Contoh: budisat" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" id="form-password" placeholder="Minimal 6 karakter" class="form-input">
                            </div>
                            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="form-nama" required placeholder="Sesuai ijazah SMP" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">NISN</label>
                            <input type="text" name="nisn" id="form-nisn" placeholder="Nomor Induk Siswa Nasional" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Asal Sekolah (SMP/MTs)</label>
                            <input type="text" name="asal_sekolah" id="form-asal" placeholder="Contoh: SMP Negeri 1" class="form-input">
                        </div>

                        <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                            <button type="submit" name="tambah_siswa" id="submit-btn" class="btn btn-primary" style="width: 100%;">Daftarkan Siswa</button>
                            <button type="button" onclick="resetForm()" id="cancel-btn" class="btn hidden" style="background-color: #cbd5e1; color: #334155;">Batal</button>
                        </div>
                    </form>
                </div>

                <!-- BARU: FORM IMPORT DATA EXCEL/CSV -->
                <div class="admin-card" id="import-card">
                    <h2 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a;">
                        Import Data via CSV/Excel
                    </h2>
                    <p style="font-size: 0.825rem; color: #64748b; margin-bottom: 1.25rem; line-height: 1.4;">
                        Unggah file <strong>.csv</strong> tanpa header. <br>Format: <code>username,password,nama,nisn,sekolah</code>
                    </p>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="file_csv" accept=".csv" required class="form-input" style="padding: 0.4rem;">
                        </div>
                        <button type="submit" name="import_siswa" class="btn" style="width: 100%; background-color: #10b981; color: white; margin-top: 0.5rem;">
                            🚀 Mulai Import Data
                        </button>
                    </form>
                </div>
            </div>

            <!-- DATA PENDAFTAR -->
            <div class="admin-card" style="grid-column: span 2;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 700; color: #0f172a; margin: 0;">Daftar Pendaftar Terdata</h2>
                    <span class="badge badge-info" style="font-size: 0.75rem;">Total: <?= count($all_siswa) ?> Calon Murid</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Identitas Murid</th>
                                <th>Asal Sekolah</th>
                                <th>Akun Login</th>
                                <th style="text-align: center; width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($all_siswa) == 0): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; font-style: italic; padding: 2rem;">Belum ada data calon siswa.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($all_siswa as $index => $row): ?>
                                <tr>
                                    <td style="color: #94a3b8; font-family: monospace;"><?= $index + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;">NISN: <?= htmlspecialchars($row['nisn'] ?: '-') ?></div>
                                    </td>
                                    <td style="font-weight: 500; color: #334155;">🎒 <?= htmlspecialchars($row['asal_sekolah'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-family: monospace;">
                                            <?= htmlspecialchars($row['username']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <button onclick="editSiswa(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['nisn'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['asal_sekolah'], ENT_QUOTES) ?>')" class="btn btn-sm" style="background-color: #fef3c7; color: #d97706; margin-right: 0.25rem;">Edit</button>
                                        <a href="siswa.php?hapus=<?= $row['user_id'] ?>" onclick="return confirm('PERINGATAN: Menghapus data ini akan menghapus akun login & seluruh hasil ujian siswa terkait! Lanjutkan?')" class="btn btn-sm" style="background-color: #fee2e2; color: #ef4444;">Hapus</a>
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
        function editSiswa(id, nama, nisn, asal) {
            document.getElementById('form-title').innerText = "Edit Profil Calon Siswa";
            
            document.getElementById('siswa-id').value = id;
            document.getElementById('form-nama').value = nama;
            document.getElementById('form-nisn').value = nisn;
            document.getElementById('form-asal').value = asal;
            
            document.getElementById('akun-fields').style.display = 'none';
            // Sembunyikan form import saat mode edit aktif agar fokus
            document.getElementById('import-card').style.display = 'none';

            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'edit_siswa');
            submitBtn.innerText = "Simpan Perubahan";
            submitBtn.style.backgroundColor = "#f59e0b";
            
            document.getElementById('cancel-btn').classList.remove('hidden');
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        function resetForm() {
            document.getElementById('form-title').innerText = "Registrasi Siswa Baru";
            document.getElementById('siswa-id').value = "";
            document.getElementById('siswa-form').reset();
            
            document.getElementById('akun-fields').style.display = 'block';
            // Tampilkan kembali form import saat form di-reset
            document.getElementById('import-card').style.display = 'block';

            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'tambah_siswa');
            submitBtn.innerText = "Daftarkan Siswa";
            submitBtn.style.backgroundColor = "#2563eb";
            
            document.getElementById('cancel-btn').classList.add('hidden');
        }
    </script>
</body>
</html>