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
$edit_menu = null;

// Menampilkan pesan dari redirect (misal setelah tambah/edit/hapus)
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    // Asumsikan pesan dari redirect selalu sukses, bisa dibuat lebih kompleks jika perlu
    $message_type = 'success';
}

// 2. LOGIKA CREATE / UPDATE (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $status = $_POST['status'];
    $category = trim($_POST['category']);
    $menu_id = $_POST['menu_id'] ?? null;
    $old_image_path = $_POST['old_image_url'] ?? '';

    $image_path_to_db = $old_image_path; // Defaultnya gunakan gambar lama

    // Logika upload gambar baru jika ada
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        $target_dir = "../assets/images/menu/";
        
        // Validasi ekstensi dan ukuran
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_extension, $allowed_types)) {
            $message = "Tipe file tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diizinkan.";
            $message_type = 'error';
        } elseif ($file['size'] > $max_size) {
            $message = "Ukuran file terlalu besar. Maksimal 5MB.";
            $message_type = 'error';
        } else {
            // Buat nama file unik dan pindahkan file
            $new_file_name = 'menu_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $image_path_to_db = "assets/images/menu/" . $new_file_name;
                // Hapus gambar lama jika ada dan bukan gambar default
                if (!empty($old_image_path) && file_exists("../" . $old_image_path) && strpos($old_image_path, 'default.png') === false) {
                    unlink("../" . $old_image_path);
                }
            } else {
                $message = "Gagal mengupload gambar.";
                $message_type = 'error';
            }
        }
    }

    // Lanjutkan ke database jika tidak ada error upload
    if (empty($message)) {
        if ($menu_id) { // Mode Update
            $sql = "UPDATE menu SET name=?, description=?, price=?, status=?, category=?, image_url=? WHERE menu_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdsssi", $name, $description, $price, $status, $category, $image_path_to_db, $menu_id);
            $success_msg = "Menu berhasil diperbarui.";
        } else { // Mode Create
            $sql = "INSERT INTO menu (name, description, price, status, category, image_url) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssdsss", $name, $description, $price, $status, $category, $image_path_to_db);
            $success_msg = "Menu berhasil ditambahkan.";
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: menu_management.php?message=" . urlencode($success_msg));
            exit();
        } else {
            $message = "Operasi database gagal: " . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// 3. LOGIKA DELETE (GET REQUEST)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $menu_id = $_GET['id'];
    
    // Ambil path gambar untuk dihapus nanti
    $stmt_img = mysqli_prepare($conn, "SELECT image_url FROM menu WHERE menu_id = ?");
    mysqli_stmt_bind_param($stmt_img, "i", $menu_id);
    mysqli_stmt_execute($stmt_img);
    $result_img = mysqli_stmt_get_result($stmt_img);
    $img_row = mysqli_fetch_assoc($result_img);
    $image_to_delete = $img_row['image_url'] ?? '';
    mysqli_stmt_close($stmt_img);

    // Hapus dari database
    $stmt_del = mysqli_prepare($conn, "DELETE FROM menu WHERE menu_id = ?");
    mysqli_stmt_bind_param($stmt_del, "i", $menu_id);

    if (mysqli_stmt_execute($stmt_del)) {
        // Hapus file gambar fisik
        if (!empty($image_to_delete) && file_exists("../" . $image_to_delete) && strpos($image_to_delete, 'default.png') === false) {
            unlink("../" . $image_to_delete);
        }
        header("Location: menu_management.php?message=" . urlencode("Menu berhasil dihapus."));
    } else {
        header("Location: menu_management.php?message=" . urlencode("Gagal menghapus menu. Mungkin terkait dengan data pesanan."));
    }
    mysqli_stmt_close($stmt_del);
    exit();
}

// 4. LOGIKA READ (untuk mengisi form edit)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $menu_id = $_GET['id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM menu WHERE menu_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $menu_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_menu = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// 5. LOGIKA READ (untuk menampilkan daftar menu)
$menus = [];
$stmt_all = mysqli_prepare($conn, "SELECT * FROM menu ORDER BY category, name ASC");
mysqli_stmt_execute($stmt_all);
$result_all_menu = mysqli_stmt_get_result($stmt_all);
if ($result_all_menu) {
    $menus = mysqli_fetch_all($result_all_menu, MYSQLI_ASSOC);
}
mysqli_stmt_close($stmt_all);

// Tutup koneksi di akhir script
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Menu - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Sembunyikan input file asli */
        input[type="file"] { display: none; }
    </style>
</head>
<body class="bg-slate-100">

<div class="flex h-screen bg-slate-800">
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
            <a href="menu_management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>
                Manajemen Menu
            </a>
            <a href="user_management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                Manajemen Pengguna
            </a>
             <a href="reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>
                Laporan Penjualan
            </a>
        </nav>
        </aside>

    <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold text-slate-800 mb-6">Manajemen Menu</h1>

        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md mb-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-1"><?php echo $edit_menu ? 'Edit Menu' : 'Tambah Menu Baru'; ?></h2>
            <p class="mb-6 text-slate-500"><?php echo $edit_menu ? 'Perbarui detail menu di bawah ini.' : 'Isi form untuk menambahkan menu baru.'; ?></p>
            
            <?php if ($message): ?>
            <div class="<?php echo $message_type == 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded-md mb-6" role="alert">
                <p class="font-bold"><?php echo $message_type == 'success' ? 'Berhasil' : 'Error'; ?></p>
                <p><?php echo $message; ?></p>
            </div>
            <?php endif; ?>

            <form action="menu_management.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if ($edit_menu): ?>
                    <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($edit_menu['menu_id']); ?>">
                    <input type="hidden" name="old_image_url" value="<?php echo htmlspecialchars($edit_menu['image_url'] ?? ''); ?>">
                <?php endif; ?>

                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Menu</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_menu['name'] ?? ''); ?>" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>
                 <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="4" class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"><?php echo htmlspecialchars($edit_menu['description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Harga (Rp)</label>
                    <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($edit_menu['price'] ?? ''); ?>" required placeholder="Contoh: 25000" class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                    <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($edit_menu['category'] ?? ''); ?>" placeholder="Contoh: Makanan Utama" class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>
                 <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status Ketersediaan</label>
                    <select id="status" name="status" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="ada" <?php echo (isset($edit_menu['status']) && $edit_menu['status'] == 'ada') ? 'selected' : ''; ?>>Ada</option>
                        <option value="habis" <?php echo (isset($edit_menu['status']) && $edit_menu['status'] == 'habis') ? 'selected' : ''; ?>>Habis</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Gambar Menu</label>
                    <label for="image_file" class="cursor-pointer bg-white border border-slate-300 rounded-lg px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                        <span id="file-chosen-text">Pilih file...</span>
                    </label>
                    <input type="file" id="image_file" name="image_file" accept="image/png, image/jpeg, image/gif">
                    
                    <?php if ($edit_menu && !empty($edit_menu['image_url'])): ?>
                    <div class="mt-4 flex items-center gap-4">
                        <img src="../<?php echo htmlspecialchars($edit_menu['image_url']); ?>" alt="Current Image" class="w-20 h-20 object-cover rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Gambar saat ini</p>
                            <p class="text-xs text-slate-500">Unggah file baru untuk mengganti.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="md:col-span-2 flex items-center justify-start gap-4 mt-4">
                     <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <?php echo $edit_menu ? 'Simpan Perubahan' : 'Tambah Menu'; ?>
                    </button>
                     <?php if ($edit_menu): ?>
                        <a href="menu_management.php" class="py-2 px-6 border border-slate-300 shadow-sm text-sm font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Daftar Menu</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-slate-500">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/4">Menu</th>
                            <th scope="col" class="px-6 py-3">Harga</th>
                            <th scope="col" class="px-6 py-3">Kategori</th>
                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($menus)): ?>
                        <tr class="bg-white"><td colspan="5" class="text-center py-10 text-slate-500">Belum ada menu yang ditambahkan.</td></tr>
                        <?php else: ?>
                        <?php foreach ($menus as $menu): ?>
                        <tr class="bg-white border-b hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <img src="../<?php echo !empty($menu['image_url']) ? htmlspecialchars($menu['image_url']) : 'assets/images/menu/default.png'; ?>" alt="<?php echo htmlspecialchars($menu['name']); ?>" class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                                    <div>
                                        <div class="font-medium text-slate-900"><?php echo htmlspecialchars($menu['name']); ?></div>
                                        <div class="text-xs text-slate-500 truncate w-60"><?php echo htmlspecialchars($menu['description']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($menu['category']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                $status_class = $menu['status'] == 'ada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                echo "<span class='px-3 py-1 text-xs font-medium rounded-full $status_class'>" . htmlspecialchars(ucfirst($menu['status'])) . "</span>";
                                ?>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <a href="menu_management.php?action=edit&id=<?php echo $menu['menu_id']; ?>" class="font-medium text-blue-600 hover:text-blue-800 transition mr-4">Edit</a>
                                <a href="menu_management.php?action=delete&id=<?php echo $menu['menu_id']; ?>" onclick="return confirm('Anda yakin ingin menghapus menu \'<?php echo addslashes(htmlspecialchars($menu['name'])); ?>\'?');" class="font-medium text-red-600 hover:text-red-800 transition">Hapus</a>
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

<script>
    const fileInput = document.getElementById('image_file');
    const fileChosenText = document.getElementById('file-chosen-text');

    fileInput.addEventListener('change', function() {
        fileChosenText.textContent = this.files[0] ? this.files[0].name : 'Pilih file...';
    });
</script>

</body>
</html>