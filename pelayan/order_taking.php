<?php
session_start();
include '../includes/db_connect.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'pelayan') {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];

$message = '';
$message_type = '';

// 2. LOGIKA PENYIMPANAN PESANAN (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_order'])) {
    $customer_name = trim($_POST['customer_name']);
    $table_number = trim($_POST['table_number']);
    $menu_ids = $_POST['menu_items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    $ordered_items = [];
    if (!empty($menu_ids)) {
        foreach ($menu_ids as $index => $menu_id) {
            $quantity = (int)($quantities[$index] ?? 0);
            if ($quantity > 0) {
                $ordered_items[$menu_id] = $quantity;
            }
        }
    }

    if (empty($customer_name) || empty($table_number) || empty($ordered_items)) {
        $message = 'Nama pelanggan, nomor meja, dan minimal satu item harus diisi.';
        $message_type = 'error';
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Status awal pesanan adalah 'pending', yang berarti menunggu diproses oleh dapur
            $stmt_order = mysqli_prepare($conn, "INSERT INTO orders (customer_name, table_number, status, order_date) VALUES (?, ?, 'pending', NOW())");
            mysqli_stmt_bind_param($stmt_order, "ss", $customer_name, $table_number);
            mysqli_stmt_execute($stmt_order);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_order);

            if (!$order_id) throw new Exception("Gagal membuat pesanan baru.");

            $stmt_item_price = mysqli_prepare($conn, "SELECT price, status FROM menu WHERE menu_id = ?");
            $stmt_insert_item = mysqli_prepare($conn, "INSERT INTO order_items (order_id, menu_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");

            foreach ($ordered_items as $menu_id => $quantity) {
                // Ambil harga saat ini dan pastikan menu tersedia
                mysqli_stmt_bind_param($stmt_item_price, "i", $menu_id);
                mysqli_stmt_execute($stmt_item_price);
                $result_price = mysqli_stmt_get_result($stmt_item_price);
                $menu_info = mysqli_fetch_assoc($result_price);

                if (!$menu_info || $menu_info['status'] != 'ada') {
                    // Lewati item yang tidak tersedia, bisa juga throw exception
                    continue;
                }
                $price_at_order = $menu_info['price'];

                // Masukkan ke order_items
                mysqli_stmt_bind_param($stmt_insert_item, "iiid", $order_id, $menu_id, $quantity, $price_at_order);
                mysqli_stmt_execute($stmt_insert_item);
            }
            mysqli_stmt_close($stmt_item_price);
            mysqli_stmt_close($stmt_insert_item);

            mysqli_commit($conn);
            $message = "Pesanan untuk {$customer_name} berhasil dibuat dan dikirim ke dapur!";
            $message_type = 'success';

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = 'Terjadi kesalahan: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// 3. LOGIKA PENGAMBILAN DATA MENU
$menus = [];
// Mengambil data menu diurutkan berdasarkan kategori, lalu nama
$result_menu = mysqli_query($conn, "SELECT menu_id, name, price, status, image_url, category FROM menu ORDER BY category, name ASC");
if ($result_menu) {
    $menus = mysqli_fetch_all($result_menu, MYSQLI_ASSOC);
}
mysqli_close($conn);

// Mengelompokkan menu berdasarkan kategori
$categorized_menus = [];
foreach ($menus as $menu) {
    $category = $menu['category'] ?: 'Lain-lain';
    $categorized_menus[$category][] = $menu;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">

<div class="flex h-screen bg-slate-800">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 flex flex-col p-4 text-white">
        <a href="../dashboard.php" class="flex items-center gap-3 px-2 mb-8">
            <div class="bg-orange-500 p-2 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></div>
            <span class="text-xl font-bold">RestoKU</span>
        </a>
        <nav class="flex-1 space-y-2">
            <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>Dashboard</a>
            <div class="border-t border-slate-700 my-4"></div>
            <h3 class="px-4 mt-4 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Aplikasi</h3>
            <a href="booking_management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>Manajemen Booking</a>
            <a href="order_taking.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2V3zm5 9a1 1 0 11-2 0 1 1 0 012 0z" /><path d="M7 13a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" /></svg>Input Pesanan</a>
            <a href="order_status_view.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C3.732 5.943 7.523 3 12 3c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM12 15a5 5 0 100-10 5 5 0 000 10z" clip-rule="evenodd" /></svg>Status Pesanan</a>
        </nav>
    </aside>

    <!-- Konten Utama -->
    <main class="flex-1 overflow-y-auto">
        <form action="order_taking.php" method="POST" class="grid grid-cols-1 lg:grid-cols-3 h-full">
            
            <!-- Kolom Kiri & Tengah: Informasi & Daftar Menu -->
            <div class="lg:col-span-2 p-6 lg:p-8 overflow-y-auto">
                <h1 class="text-3xl font-bold text-slate-800 mb-6">Buat Pesanan Baru</h1>
                
                <?php if ($message): ?>
                <div class="mb-6 <?php echo $message_type == 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded-md" role="alert">
                    <p class="font-bold"><?php echo ucfirst($message_type); ?></p>
                    <p><?php echo $message; ?></p>
                </div>
                <?php endif; ?>

                <!-- Informasi Pelanggan -->
                <div class="bg-white p-6 rounded-xl shadow-md mb-8">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">Informasi Pelanggan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-slate-700 mb-1">Nama Pelanggan</label>
                            <input type="text" id="customer_name" name="customer_name" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="table_number" class="block text-sm font-medium text-slate-700 mb-1">Nomor Meja</label>
                            <input type="text" id="table_number" name="table_number" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Daftar Menu -->
                <div class="space-y-8">
                <?php foreach ($categorized_menus as $category => $menu_items): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 mb-4 pb-2 border-b-2 border-slate-200"><?php echo htmlspecialchars($category); ?></h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php foreach ($menu_items as $menu): ?>
                                <?php $is_available = ($menu['status'] == 'ada'); ?>
                                <div class="menu-item bg-white rounded-xl shadow-md overflow-hidden flex flex-col transition hover:shadow-lg" 
                                     data-id="<?php echo $menu['menu_id']; ?>" 
                                     data-name="<?php echo htmlspecialchars($menu['name']); ?>" 
                                     data-price="<?php echo $menu['price']; ?>">
                                    
                                    <div class="relative">
                                        <img src="../<?php echo !empty($menu['image_url']) ? htmlspecialchars($menu['image_url']) : 'assets/images/menu/default.png'; ?>" alt="<?php echo htmlspecialchars($menu['name']); ?>" class="h-48 w-full object-cover">
                                        <?php if (!$is_available): ?>
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <span class="text-white text-lg font-bold bg-red-600 px-4 py-1 rounded-md">HABIS</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="p-4 flex-grow">
                                        <h3 class="font-bold text-lg text-slate-800"><?php echo htmlspecialchars($menu['name']); ?></h3>
                                        <p class="text-slate-600 font-semibold">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></p>
                                    </div>

                                    <div class="p-4 bg-slate-50">
                                        <div class="flex items-center justify-center gap-4">
                                            <button type="button" class="quantity-change-btn bg-slate-200 text-slate-800 rounded-full w-8 h-8 font-bold text-lg hover:bg-slate-300 disabled:opacity-50 disabled:cursor-not-allowed" data-action="decrease" <?php echo !$is_available ? 'disabled' : ''; ?>>-</button>
                                            <input type="hidden" name="menu_items[]" value="<?php echo $menu['menu_id']; ?>">
                                            <input type="hidden" name="quantities[]" class="quantity-input" value="0">
                                            <span class="quantity-display text-lg font-bold w-8 text-center">0</span>
                                            <button type="button" class="quantity-change-btn bg-slate-800 text-white rounded-full w-8 h-8 font-bold text-lg hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed" data-action="increase" <?php echo !$is_available ? 'disabled' : ''; ?>>+</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Kolom Kanan: Keranjang Pesanan -->
            <div class="lg:col-span-1 bg-white p-6 lg:p-8 border-l border-slate-200 flex flex-col h-full">
                <h2 class="text-2xl font-bold text-slate-800 mb-6">Keranjang Pesanan</h2>
                <div id="order-summary-list" class="flex-grow space-y-4 overflow-y-auto pr-2">
                    <p id="empty-cart-message" class="text-slate-500 text-center py-10">Keranjang masih kosong. <br>Pilih menu untuk memulai.</p>
                </div>
                <div class="mt-auto pt-6 border-t-2 border-slate-200 space-y-4">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span class="text-slate-600">Total</span>
                        <span id="order-total" class="text-slate-900">Rp 0</span>
                    </div>
                    <button type="submit" name="submit_order" id="submit-order-btn" disabled class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed transition">
                        Buat Pesanan
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const order = {}; // { menuId: { name, price, quantity } }
    const menuContainer = document.querySelector('.lg\\:col-span-2'); // a container for all menu items
    const summaryList = document.getElementById('order-summary-list');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const orderTotalEl = document.getElementById('order-total');
    const submitBtn = document.getElementById('submit-order-btn');

    menuContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('quantity-change-btn')) {
            const button = e.target;
            const menuItemEl = button.closest('.menu-item');
            const action = button.dataset.action;
            
            const id = menuItemEl.dataset.id;
            const name = menuItemEl.dataset.name;
            const price = parseFloat(menuItemEl.dataset.price);

            updateOrder(id, name, price, action);
        }
    });

    function updateOrder(id, name, price, action) {
        if (!order[id]) {
            order[id] = { name, price, quantity: 0 };
        }

        let currentQuantity = order[id].quantity;

        if (action === 'increase') {
            currentQuantity++;
        } else if (action === 'decrease' && currentQuantity > 0) {
            currentQuantity--;
        }

        order[id].quantity = currentQuantity;

        // Update the specific menu item's display
        const menuItemEl = document.querySelector(`.menu-item[data-id='${id}']`);
        if (menuItemEl) {
            menuItemEl.querySelector('.quantity-display').textContent = currentQuantity;
            menuItemEl.querySelector('.quantity-input').value = currentQuantity;
        }
        
        renderCart();
    }

    function renderCart() {
        summaryList.innerHTML = ''; // Clear the list
        let total = 0;
        let hasItems = false;

        Object.keys(order).forEach(id => {
            const item = order[id];
            if (item.quantity > 0) {
                hasItems = true;
                const itemTotal = item.quantity * item.price;
                total += itemTotal;

                const li = document.createElement('div');
                li.className = 'flex items-center justify-between gap-4';
                li.innerHTML = `
                    <div class="flex-grow">
                        <p class="font-semibold text-slate-800">${item.name}</p>
                        <p class="text-sm text-slate-500">${item.quantity} x Rp ${formatCurrency(item.price)}</p>
                    </div>
                    <p class="font-semibold text-slate-900">Rp ${formatCurrency(itemTotal)}</p>
                `;
                summaryList.appendChild(li);
            }
        });
        
        if (hasItems) {
            emptyCartMessage.style.display = 'none';
            submitBtn.disabled = false;
        } else {
            summaryList.appendChild(emptyCartMessage);
            emptyCartMessage.style.display = 'block';
            submitBtn.disabled = true;
        }

        orderTotalEl.textContent = `Rp ${formatCurrency(total)}`;
    }

    function formatCurrency(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    // Initial render
    renderCart();
});
</script>

</body>
</html>