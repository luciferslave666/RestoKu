<?php
session_start();
include '../includes/db_connect.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = ''; // 'success' atau 'error'
$edit_user = null;

// Pesan dari URL (setelah redirect)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// 2. LOGIKA CREATE / UPDATE (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';
    $user_id = $_POST['user_id'] ?? null;

    if ($user_id) { // Mode Edit
        $sql = !empty($password) 
            ? "UPDATE users SET username=?, password=?, role=? WHERE user_id=?" 
            : "UPDATE users SET username=?, role=? WHERE user_id=?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "sssi", $username, $hashed_password, $role, $user_id);
        } else {
            mysqli_stmt_bind_param($stmt, "ssi", $username, $role, $user_id);
        }
        $success_msg = "Data pengguna berhasil diperbarui.";

    } else { // Mode Tambah
        if (empty($password)) {
            $message = 'Password harus diisi untuk pengguna baru.';
            $message_type = 'error';
        } else {
            // Cek duplikasi username
            $stmt_check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt_check, "s", $username);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $message = 'Username sudah digunakan. Pilih username lain.';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);
                $success_msg = "Pengguna baru berhasil ditambahkan.";
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Eksekusi query jika tidak ada error sebelumnya
    if (empty($message) && isset($stmt)) {
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: user_management.php?message=" . urlencode($success_msg) . "&type=success");
            exit();
        } else {
            $message = 'Operasi database gagal: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// 3. LOGIKA DELETE (GET REQUEST)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = $_GET['id'];
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $msg = "Anda tidak bisa menghapus akun Anda sendiri.";
        $type = "error";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "Pengguna berhasil dihapus.";
            $type = "success";
        } else {
            $msg = "Gagal menghapus pengguna.";
            $type = "error";
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: user_management.php?message=" . urlencode($msg) . "&type=" . $type);
    exit();
}

// 4. LOGIKA READ (untuk mengisi form edit)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $user_id_to_edit = $_GET['id'];
    $stmt = mysqli_prepare($conn, "SELECT user_id, username, role FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id_to_edit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// 5. LOGIKA READ (untuk menampilkan daftar pengguna)
$users = [];
$result_all_users = mysqli_query($conn, "SELECT user_id, username, role FROM users ORDER BY username ASC");
if ($result_all_users) {
    $users = mysqli_fetch_all($result_all_users, MYSQLI_ASSOC);
}
mysqli_close($conn);

// Helper function untuk badge peran
function get_role_badge($role) {
    $role_text = ucfirst(htmlspecialchars($role));
    $colors = [
        'admin' => 'bg-red-100 text-red-800',
        'kasir' => 'bg-blue-100 text-blue-800',
        'pelayan' => 'bg-green-100 text-green-800',
        'chef' => 'bg-yellow-100 text-yellow-800',
    ];
    $class = $colors[$role] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-3 py-1 text-xs font-medium rounded-full $class'>$role_text</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">

<div class="flex h-screen bg-slate-800">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 flex flex-col p-4 text-white">
        <a href="../dashboard.php" class="flex items-center gap-3 px-2 mb-8">
            <div class="bg-orange-500 p-2 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            </div>
            <span class="text-xl font-bold">RestoKU</span>
        </a>
        <nav class="flex-1">
            <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
                Dashboard
            </a>
            <h3 class="px-4 mt-6 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Admin</h3>
            <a href="menu_management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>
                Manajemen Menu
            </a>
            <a href="user_management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                Manajemen Pengguna
            </a>
             <a href="reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>
                Laporan Penjualan
            </a>
        </nav>
        <!-- User Profile di sini -->
    </aside>

    <!-- Konten Utama -->
    <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold text-slate-800 mb-6">Manajemen Pengguna</h1>

        <!-- Form Card -->
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md mb-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-1"><?php echo $edit_user ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?></h2>
            <p class="mb-6 text-slate-500"><?php echo $edit_user ? 'Perbarui detail pengguna di bawah ini.' : 'Isi form untuk menambahkan pengguna baru.'; ?></p>
            
            <?php if ($message): ?>
            <div class="<?php echo $message_type == 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded-md mb-6" role="alert">
                <p class="font-bold"><?php echo $message_type == 'success' ? 'Berhasil' : 'Error'; ?></p>
                <p><?php echo $message; ?></p>
            </div>
            <?php endif; ?>

            <form action="user_management.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['user_id']); ?>">
                <?php endif; ?>

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition <?php echo $edit_user ? 'bg-slate-200 cursor-not-allowed' : ''; ?>" <?php echo $edit_user ? 'readonly' : ''; ?>>
                    <?php if ($edit_user): ?><p class="text-xs text-slate-500 mt-1">Username tidak dapat diubah.</p><?php endif; ?>
                </div>
                 <div>
                    <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Peran (Role)</label>
                    <select id="role" name="role" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="admin" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="kasir" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'kasir') ? 'selected' : ''; ?>>Kasir</option>
                        <option value="pelayan" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'pelayan') ? 'selected' : ''; ?>>Pelayan</option>
                        <option value="chef" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'chef') ? 'selected' : ''; ?>>Chef</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" <?php echo !$edit_user ? 'required' : ''; ?>>
                    <?php if ($edit_user): ?><p class="text-xs text-slate-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p><?php endif; ?>
                </div>

                <div class="md:col-span-2 flex items-center justify-start gap-4 mt-2">
                     <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <?php echo $edit_user ? 'Simpan Perubahan' : 'Tambah Pengguna'; ?>
                    </button>
                     <?php if ($edit_user): ?>
                        <a href="user_management.php" class="py-2 px-6 border border-slate-300 shadow-sm text-sm font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Daftar Pengguna</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-slate-500">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-2/5">Username</th>
                            <th scope="col" class="px-6 py-3">Peran</th>
                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr class="bg-white"><td colspan="3" class="text-center py-10 text-slate-500">Belum ada pengguna yang terdaftar.</td></tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="bg-white border-b hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-600 flex-shrink-0">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <span class="font-medium text-slate-900"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo get_role_badge($user['role']); ?></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                    <span class="font-medium text-slate-400 cursor-not-allowed">Anda</span>
                                <?php else: ?>
                                    <a href="user_management.php?action=edit&id=<?php echo $user['user_id']; ?>" class="font-medium text-blue-600 hover:text-blue-800 transition mr-4">Edit</a>
                                    <a href="user_management.php?action=delete&id=<?php echo $user['user_id']; ?>" onclick="return confirm('Anda yakin ingin menghapus pengguna \'<?php echo addslashes(htmlspecialchars($user['username'])); ?>\'?');" class="font-medium text-red-600 hover:text-red-800 transition">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>