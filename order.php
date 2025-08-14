<?php
session_start(); // Diperlukan untuk menyimpan keranjang belanja di sesi

include 'includes/db_connect.php'; // Koneksi ke database

$table_id_from_url = $_GET['table_id'] ?? 'Meja Umum'; // Ambil table_id dari URL, default jika tidak ada
$message = '';
$menus = [];
$customer_name_prefill = $_SESSION['customer_order_name'] ?? '';
$table_number_prefill = $_SESSION['customer_order_table'] ?? $table_id_from_url; // Pre-fill nomor meja dari sesi atau dari URL

// --- Logika Menambah Item ke Keranjang DARI URL (saat halaman dimuat via GET) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['menu_id']) && isset($_GET['quantity'])) {
    $menu_id_from_url = (int)$_GET['menu_id'];
    $quantity_from_url = (int)$_GET['quantity'];

    // Simpan nama dan nomor meja di sesi jika ada di URL saat pertama kali datang
    $_SESSION['customer_order_table'] = $table_id_from_url;

    if ($quantity_from_url > 0) {
        $stmt = mysqli_prepare($conn, "SELECT menu_id, name, price, status FROM menu WHERE menu_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $menu_id_from_url);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $menu_item_from_url = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($menu_item_from_url && $menu_item_from_url['status'] == 'ada') {
            if (!isset($_SESSION['qr_order_cart'])) {
                $_SESSION['qr_order_cart'] = [];
            }
            if (isset($_SESSION['qr_order_cart'][$menu_id_from_url])) {
                $_SESSION['qr_order_cart'][$menu_id_from_url]['quantity'] += $quantity_from_url;
            } else {
                $_SESSION['qr_order_cart'][$menu_id_from_url] = [
                    'menu_id' => $menu_id_from_url,
                    'name' => $menu_item_from_url['name'],
                    'price' => $menu_item_from_url['price'],
                    'quantity' => $quantity_from_url
                ];
            }
            $message = '<p class="text-green-500">Menu "' . htmlspecialchars($menu_item_from_url['name']) . '" berhasil ditambahkan ke keranjang.</p>';
        } else {
            $message = '<p class="text-red-500">Menu tidak tersedia atau tidak ditemukan.</p>';
        }
    }
    // Redirect untuk menghapus parameter menu_id dari URL setelah ditambahkan
    header("Location: order.php?table_id=" . urlencode($table_id_from_url) . "&status=" . urlencode($_GET['status'] ?? 'added') . "&message=" . urlencode(strip_tags($message)));
    exit();
}

// --- Logika Menambah Item ke Keranjang (saat form di-submit via POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $menu_id = (int)$_POST['menu_id'];
    $quantity = (int)$_POST['quantity'];
    $customer_name_input = trim($_POST['customer_name']);
    $table_number_input = trim($_POST['table_number_input']); // Ambil nomor meja dari input form

    // Simpan nama pelanggan dan nomor meja di sesi untuk pre-fill
    $_SESSION['customer_order_name'] = $customer_name_input;
    $_SESSION['customer_order_table'] = $table_number_input; // Simpan juga nomor meja di sesi

    if (empty($customer_name_input) || empty($table_number_input) || $quantity <= 0) {
        $message = '<p class="text-red-500">Nama Anda, Nomor Meja, dan Kuantitas menu harus diisi.</p>';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT menu_id, name, price, status FROM menu WHERE menu_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $menu_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $menu_item = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$menu_item || $menu_item['status'] != 'ada') {
            $message = '<p class="text-red-500">Menu tidak tersedia atau tidak ditemukan.</p>';
        } else {
            if (!isset($_SESSION['qr_order_cart'])) {
                $_SESSION['qr_order_cart'] = [];
            }
            if (isset($_SESSION['qr_order_cart'][$menu_id])) {
                $_SESSION['qr_order_cart'][$menu_id]['quantity'] += $quantity;
            } else {
                $_SESSION['qr_order_cart'][$menu_id] = [
                    'menu_id' => $menu_id,
                    'name' => $menu_item['name'],
                    'price' => $menu_item['price'],
                    'quantity' => $quantity
                ];
            }
            $message = '<p class="text-green-500">Menu "' . htmlspecialchars($menu_item['name']) . '" berhasil ditambahkan ke keranjang.</p>';
            header("Location: order.php?table_id=" . urlencode($table_id_from_url) . "&status=added&message=" . urlencode(strip_tags($message)));
            exit();
        }
    }
}

// --- Logika Konfirmasi Pesanan (Checkout dari QR) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_order'])) {
    $customer_name_final = trim($_POST['customer_name_final']);
    $order_table_id_final = trim($_POST['order_table_id_final']); // Ambil nomor meja final dari hidden input
    $current_cart = $_SESSION['qr_order_cart'] ?? [];

    if (empty($customer_name_final) || empty($order_table_id_final) || empty($current_cart)) {
        $message = '<p class="text-red-500">Nama Anda, Nomor Meja, dan pesanan tidak boleh kosong.</p>';
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Buat entri pesanan baru
            $stmt_order = mysqli_prepare($conn, "INSERT INTO orders (table_number, order_date, status, customer_name) VALUES (?, NOW(), 'pending', ?)");
            mysqli_stmt_bind_param($stmt_order, "ss", $order_table_id_final, $customer_name_final); // Gunakan nomor meja final
            mysqli_stmt_execute($stmt_order);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_order);

            if (!$order_id) {
                throw new Exception("Gagal membuat pesanan baru.");
            }

            // Tambahkan item-item pesanan
            foreach ($current_cart as $item_id => $item_details) {
                $stmt_item = mysqli_prepare($conn, "INSERT INTO order_items (order_id, menu_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_item, "iiid", $order_id, $item_details['menu_id'], $item_details['quantity'], $item_details['price']);
                mysqli_stmt_execute($stmt_item);
                mysqli_stmt_close($stmt_item);
            }

            mysqli_commit($conn);
            unset($_SESSION['qr_order_cart']); // Kosongkan keranjang setelah pesanan dikirim
            unset($_SESSION['customer_order_name']); // Kosongkan nama juga
            unset($_SESSION['customer_order_table']); // Kosongkan nomor meja juga
            $message = '<p class="text-green-500">Pesanan Anda berhasil dikirim! ID Pesanan: #' . htmlspecialchars($order_id) . '</p>';
            header("Location: order.php?table_id=" . urlencode($table_id_from_url) . "&status=success_order&message=" . urlencode(strip_tags($message)));
            exit();

        } catch (Exception | mysqli_sql_exception $e) { // Tangkap juga mysqli_sql_exception
            mysqli_rollback($conn);
            $message = '<p class="text-red-500">Terjadi kesalahan saat memproses pesanan: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}

// --- Logika Hapus Item dari Keranjang (QR) ---
if (isset($_GET['action']) && $_GET['action'] == 'remove_from_qr_cart' && isset($_GET['menu_id'])) {
    $menu_id_to_remove = (int)$_GET['menu_id'];
    if (isset($_SESSION['qr_order_cart'][$menu_id_to_remove])) {
        unset($_SESSION['qr_order_cart'][$menu_id_to_remove]);
        $message = '<p class="text-green-500">Item berhasil dihapus dari keranjang.</p>';
    }
    header("Location: order.php?table_id=" . urlencode($table_id_from_url) . "&status=removed&message=" . urlencode(strip_tags($message)));
    exit();
}

// Ambil daftar menu dari database
$result_menus = mysqli_query($conn, "SELECT menu_id, name, description, price, status, category, image_url FROM menu ORDER BY name ASC");
if (mysqli_num_rows($result_menus) > 0) {
    while ($row = mysqli_fetch_assoc($result_menus)) {
        $menus[] = $row;
    }
}

mysqli_close($conn);

// Menampilkan pesan dari GET parameter (setelah redirect)
if (isset($_GET['message'])) {
    $message = '<p class="text-green-500">' . htmlspecialchars($_GET['message']) . '</p>';
}
if (isset($_GET['status']) && $_GET['status'] == 'error' && isset($_GET['message'])) {
     $message = '<p class="text-red-500">' . htmlspecialchars(str_replace('_', ' ', $_GET['message'])) . '</p>';
}

$current_qr_cart = $_SESSION['qr_order_cart'] ?? [];
$total_qr_cart_amount = 0;
foreach($current_qr_cart as $item) {
    $total_qr_cart_amount += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Menu - Meja <?php echo htmlspecialchars($table_id_from_url); ?></title>
    <link rel="stylesheet" href="assets/css/output.css">
    <style>
        .grayscale-filter { filter: grayscale(100%); }
    </style>
</head>
<body class="font-sans bg-gray-100 flex flex-col min-h-screen">
    <header class="bg-orange-800 text-white p-4 shadow-md flex justify-between items-center">
        <h1 class="text-2xl font-bold">Restoran Soto Betawi UNIKOM</h1>
    </header>

    <main class="flex-1 p-6 max-w-6xl mx-auto my-5 bg-white rounded-lg shadow-lg">
        <h2 class="text-3xl font-bold mb-6 text-gray-800 text-center">Pesan Menu untuk Meja Anda</h2>
        <?php echo $message; // Menampilkan pesan sukses/error ?>

        <div class="mb-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
            <h3 class="text-xl font-semibold text-blue-800 mb-2">Informasi Pemesan & Meja</h3>
            <p class="text-gray-700">Anda memesan untuk **Meja <span id="display_table_id"><?php echo htmlspecialchars($table_id_from_url); ?></span>**.</p>
            <form id="customerInfoForm" method="POST" action="">
                <div class="mb-4">
                    <label for="customer_name_input" class="block text-gray-700 text-sm font-bold mb-2">Nama Anda:</label>
                    <input type="text" id="customer_name_input" name="customer_name_input"
                           value="<?php echo htmlspecialchars($customer_name_prefill); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           placeholder="Masukkan nama Anda (misal: Budi, Keluarga A)" required>
                </div>
                <div class="mb-4">
                    <label for="table_number_input" class="block text-gray-700 text-sm font-bold mb-2">Nomor Meja Anda:</label>
                    <input type="text" id="table_number_input" name="table_number_input"
                           value="<?php echo htmlspecialchars($table_number_prefill); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           placeholder="Konfirmasi atau masukkan nomor meja" required>
                </div>
                <p class="text-sm text-gray-500">Nama dan nomor meja akan digunakan untuk pesanan Anda.</p>
            </form>
        </div>


        <h3 class="text-2xl font-bold mb-4 text-gray-800 text-center">Daftar Menu</h3>

        <?php if (count($menus) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($menus as $menu):
                    $is_available = ($menu['status'] == 'ada');
                    $status_text = $is_available ? 'Ada' : 'Habis';
                    $status_color_class = $is_available ? 'text-green-600' : 'text-red-600';
                    $image_path = !empty($menu['image_url']) ? htmlspecialchars($menu['image_url']) : 'assets/images/menu/default.jpg';
                ?>
                    <div class="border border-gray-200 rounded-lg shadow-md overflow-hidden bg-white hover:shadow-xl transition duration-300 flex flex-col">
                        <div class="relative h-48 w-full overflow-hidden">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($menu['name']); ?>"
                                 class="w-full h-full object-cover <?php echo !$is_available ? 'grayscale-filter' : ''; ?>">
                            <?php if (!$is_available): ?>
                                <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold">HABIS</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex-grow flex flex-col">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($menu['name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-3 flex-grow"><?php echo htmlspecialchars($menu['description']); ?></p>
                            <p class="text-orange-700 font-bold text-lg mb-2">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></p>
                            <p class="text-sm font-semibold <?php echo $status_color_class; ?>">Status: <?php echo $status_text; ?></p>
                            <?php if ($is_available): ?>
                                <form action="order.php?table_id=<?php echo urlencode($table_id_from_url); ?>" method="POST" class="mt-4 flex items-center">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <input type="hidden" name="menu_id" value="<?php echo htmlspecialchars($menu['menu_id']); ?>">
                                    <input type="hidden" name="customer_name" id="hidden_customer_name_<?php echo $menu['menu_id']; ?>" value="<?php echo htmlspecialchars($customer_name_prefill); ?>">
                                    <input type="hidden" name="table_number_input" id="hidden_table_number_<?php echo $menu['menu_id']; ?>" value="<?php echo htmlspecialchars($table_number_prefill); ?>"> <input type="number" name="quantity" value="1" min="1" class="w-20 p-2 border rounded-md mr-2 text-center">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-sm flex-grow">
                                        Tambah ke Pesanan
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="mt-4 bg-gray-400 text-white font-bold py-2 px-4 rounded-md shadow-sm cursor-not-allowed">Habis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600 text-lg mt-8">Maaf, belum ada menu yang tersedia saat ini.</p>
        <?php endif; ?>

        <div class="mt-10 bg-purple-50 p-6 rounded-lg shadow-lg border border-purple-200">
            <h3 class="text-2xl font-bold mb-4 text-purple-800">Pesanan Anda (<span id="cart-total-items"><?php echo count($current_qr_cart); ?></span> item)</h3>
            <div class="overflow-x-auto mb-4">
                <?php if (!empty($current_qr_cart)): ?>
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Menu</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kuantitas</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_qr_cart as $item): ?>
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <a href="order.php?table_id=<?php echo urlencode($table_id_from_url); ?>&action=remove_from_qr_cart&menu_id=<?php echo htmlspecialchars($item['menu_id']); ?>" class="text-red-600 hover:text-red-800">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-right text-xl font-bold text-gray-800 mb-4">
                    Total Pesanan: Rp <?php echo number_format($total_qr_cart_amount, 0, ',', '.'); ?>
                </div>
                <form action="order.php?table_id=<?php echo urlencode($table_id_from_url); ?>" method="POST">
                    <input type="hidden" name="confirm_order" value="1">
                    <input type="hidden" name="customer_name_final" id="hidden_customer_name_final" value="<?php echo htmlspecialchars($customer_name_prefill); ?>">
                    <input type="hidden" name="order_table_id_final" id="hidden_table_number_final" value="<?php echo htmlspecialchars($table_number_prefill); ?>"> <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-md shadow-lg w-full text-lg">
                        Konfirmasi Pesanan
                    </button>
                </form>
            <?php else: ?>
                <p class="text-center text-gray-600">Keranjang pesanan Anda kosong. Silakan tambahkan menu.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-6 text-center text-sm mt-auto">
        <p>&copy; <?php echo date("Y"); ?> Restoran Soto Betawi UNIKOM. Semua Hak Dilindungi.</p>
    </footer>

    <script>
        // JavaScript untuk mensinkronkan input nama pelanggan dan nomor meja
        document.addEventListener('DOMContentLoaded', function() {
            var customerNameInput = document.getElementById('customer_name_input');
            var tableNumberInput = document.getElementById('table_number_input'); // Input nomor meja baru
            var displayTableId = document.getElementById('display_table_id'); // Span display di info pemesan
            var headerTableId = document.getElementById('header_table_id'); // Span display di header

            var hiddenCustomerNameInputs = document.querySelectorAll('[id^="hidden_customer_name_"]');
            var hiddenTableNumberInputs = document.querySelectorAll('[id^="hidden_table_number_"]'); // Hidden input nomor meja per form
            var hiddenCustomerNameFinal = document.getElementById('hidden_customer_name_final');
            var hiddenTableNumberFinal = document.getElementById('hidden_table_number_final'); // Hidden input nomor meja final

            // Fungsi untuk update semua hidden inputs dan display spans
            function updateAllInputs() {
                var currentName = customerNameInput.value;
                var currentTableNumber = tableNumberInput.value;

                hiddenCustomerNameInputs.forEach(function(input) {
                    input.value = currentName;
                });
                hiddenTableNumberInputs.forEach(function(input) {
                    input.value = currentTableNumber;
                });
                hiddenCustomerNameFinal.value = currentName;
                hiddenTableNumberFinal.value = currentTableNumber;

                displayTableId.textContent = currentTableNumber;
                headerTableId.textContent = currentTableNumber;

                // Simpan di local storage untuk persistent Browse
                localStorage.setItem('qr_customer_name', currentName);
                localStorage.setItem('qr_customer_table', currentTableNumber);
            }

            // Event listeners
            customerNameInput.addEventListener('input', updateAllInputs);
            tableNumberInput.addEventListener('input', updateAllInputs); // Tambahkan event listener untuk input nomor meja

            // Load dari local storage saat halaman dimuat
            var storedName = localStorage.getItem('qr_customer_name');
            var storedTable = localStorage.getItem('qr_customer_table');

            if (storedName) {
                customerNameInput.value = storedName;
            }
            if (storedTable) {
                tableNumberInput.value = storedTable;
            } else {
                // Jika tidak ada di local storage, gunakan nilai dari URL sebagai default
                tableNumberInput.value = "<?php echo htmlspecialchars($table_id_from_url); ?>";
            }
            updateAllInputs(); // Panggil sekali saat DOM siap untuk sinkronisasi awal
        });
    </script>
</body>
</html>