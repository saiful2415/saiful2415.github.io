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
// 1. PROSES TAMBAH USER (CREATE)
// ==========================================
if (isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];

    if (!empty($username) && !empty($password)) {
        // Hash password demi keamanan
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password_hashed, $role]);
            $message = "User berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "Gagal menambah user: Username mungkin sudah digunakan.";
        }
    } else {
        $error = "Semua fields harus diisi!";
    }
}

// ==========================================
// 2. PROSES EDIT USER (UPDATE)
// ==========================================
if (isset($_POST['edit_user'])) {
    $id       = $_POST['id'];
    $username = trim($_POST['username']);
    $role     = $_POST['role'];
    $password = trim($_POST['password']);

    try {
        if (!empty($password)) {
            // Jika password baru diisi, ikut update password
            $password_hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE user SET username = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $password_hashed, $role, $id]);
        } else {
            // Jika password dikosongkan, update username & role saja
            $stmt = $pdo->prepare("UPDATE user SET username = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $role, $id]);
        }
        $message = "Data user berhasil diperbarui!";
    } catch (PDOException $e) {
        $error = "Gagal memperbarui user: Username mungkin sudah digunakan.";
    }
}

// ==========================================
// 3. PROSES HAPUS USER (DELETE)
// ==========================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cegah admin menghapus dirinya sendiri yang sedang login
    if ($id == $_SESSION['user_id']) {
        $error = "Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
        $stmt->execute([$id]);
        $message = "User berhasil dihapus!";
    }
}

// ==========================================
// 4. PROSES IMPORT USER DARI CSV/EXCEL (IMPORT)
// ==========================================
if (isset($_POST['import_csv'])) {
    if (is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
        $handle = fopen($_FILES['file_csv']['tmp_name'], "r");
        
        // Lewati baris pertama jika itu adalah header (username, password, role)
        fgetcsv($handle, 1000, ",");
        
        $baris_berhasil = 0;
        $baris_gagal = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Pastikan kolom CSV minimal ada username (indeks 0), password (indeks 1), role (indeks 2)
            if (isset($data[0]) && isset($data[1]) && isset($data[2])) {
                $csv_user = trim($data[0]);
                $csv_pass = password_hash(trim($data[1]), PASSWORD_BCRYPT);
                $csv_role = strtolower(trim($data[2]));

                // Validasi role agar sesuai ENUM database
                if ($csv_role !== 'admin' && $csv_role !== 'siswa') {
                    $csv_role = 'siswa'; 
                }

                try {
                    // FIX BUG: Menambahkan nama tabel 'user' yang sebelumnya kosong
                    $stmt = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([$csv_user, $csv_pass, $csv_role]);
                    $baris_berhasil++;
                } catch (PDOException $e) {
                    $baris_gagal++; // Biasanya karena username duplikat
                }
            }
        }
        fclose($handle);
        $message = "Import selesai. Berhasil: $baris_berhasil, Gagal/Duplikat: $baris_gagal.";
    } else {
        $error = "Silakan pilih file CSV terlebih dahulu.";
    }
}

// ==========================================
// 5. QUERY MENAMPILKAN DATA USER (READ)
// ==========================================
$stmt = $pdo->query("SELECT * FROM user ORDER BY id DESC");
$all_users = $stmt->fetchAll();
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
                <a href="user.php" class="active">Data User</a>
                <a href="siswa.php">Data Calon Murid</a>
                <a href="jurusan.php">Data Jurusan</a>
                <a href="soal.php">Data Soal</a>
                <a href="hasil.php">Hasil Ujian</a>
                <a href="../logout.php" style="color: #ef4444; margin-left: 2rem;">Log Out</a>
            </nav>
        </header>

        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; color: #1e293b;">
            Manajemen Referensi Data User
        </h1>

        <?php if ($message): ?>
            <div class="badge badge-success" style="display: block; padding: 0.75rem; margin-bottom: 1rem; border-radius: 0.5rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            
            <div class="admin-card">
                <h2 id="form-title" style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem; color: #0f172a; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                    Tambah User Baru
                </h2>
                
                <form action="" method="POST" id="user-form">
                    <input type="hidden" name="id" id="user-id">

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="form-username" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="form-password" class="form-input">
                        <small id="password-help" style="color: #94a3b8; display: block; margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role / Hak Akses</label>
                        <select name="role" id="form-role" required class="form-select">
                            <option value="siswa">Casis (Calon Siswa)</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" name="tambah_user" id="submit-btn" class="btn btn-primary" style="width: 100%;">Simpan Data</button>
                        <button type="button" onclick="resetForm()" id="cancel-btn" class="btn hidden" style="background-color: #cbd5e1; color: #334155;">Batal</button>
                    </div>
                </form>

                <div style="margin-top: 2rem; pt-6: ; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                    <h3 style="font-size: 0.875rem; font-weight: 700; color: #1e293b; text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 0.05em;">
                        Import Massal (.csv)
                    </h3>
                    <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <input type="file" name="file_csv" accept=".csv" required class="form-input" style="padding: 0.4rem;">
                        <button type="submit" name="import_csv" class="btn btn-success" style="width: 100%;">Upload & Import</button>
                    </form>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                        *Format kolom: <code style="background-color: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 0.25rem; color: #ef4444; font-family: monospace;">username,password,role</code>
                    </p>
                </div>
            </div>

            <div class="admin-card" style="grid-column: span 2;">
                <h2 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem; color: #0f172a; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                    Daftar Akun Pengguna
                </h2>
                
                <div class="table-responsive">
                    <table class="table-admin">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th style="text-align: center; width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($all_users) == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; font-style: italic; padding: 2rem;">Belum ada data user.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($all_users as $index => $user): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #64748b;"><?= $index + 1 ?></td>
                                    <td style="font-family: monospace; font-size: 0.9rem; font-weight: 600; color: #2563eb;"><?= htmlspecialchars($user['username']) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge badge-danger">ADMIN</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">SISWA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button onclick="editData(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= $user['role'] ?>')" class="btn btn-sm" style="background-color: #eff6ff; color: #2563eb; margin-right: 0.25rem;">Edit</button>
                                        <a href="user.php?hapus=<?= $user['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="btn btn-sm" style="background-color: #fee2e2; color: #ef4444;">Hapus</a>
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
        function editData(id, username, role) {
            document.getElementById('form-title').innerText = "Edit Data User";
            document.getElementById('user-id').value = id;
            document.getElementById('form-username').value = username;
            document.getElementById('form-role').value = role;
            
            document.getElementById('form-password').value = "";
            document.getElementById('password-help').innerText = "*Biarkan kosong jika tidak ingin mengganti password.";
            
            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'edit_user');
            submitBtn.innerText = "Perbarui Data";
            submitBtn.style.backgroundColor = "#f59e0b"; // Merubah warna ke amber untuk mode edit
            
            document.getElementById('cancel-btn').classList.remove('hidden');
        }

        function resetForm() {
            document.getElementById('form-title').innerText = "Tambah User Baru";
            document.getElementById('user-id').value = "";
            document.getElementById('user-form').reset();
            document.getElementById('password-help').innerText = "";
            
            let submitBtn = document.getElementById('submit-btn');
            submitBtn.setAttribute('name', 'tambah_user');
            submitBtn.innerText = "Simpan Data";
            submitBtn.style.backgroundColor = "#2563eb"; // Kembalikan ke warna biru awal
            
            document.getElementById('cancel-btn').classList.add('hidden');
        }
    </script>
</body>
</html>