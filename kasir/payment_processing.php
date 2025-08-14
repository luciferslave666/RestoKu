<?php
session_start();
include '../includes/db_connect.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['kasir', 'admin'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];

// Inisialisasi
$message = '';
$message_type = '';
$selected_order = null;
$order_items_details = [];
$view = $_GET['view'] ?? 'pending'; // 'pending' atau 'history'
$order_id_to_select = $_GET['select_order_id'] ?? null;

// Tampilkan pesan
if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
    $message_type = 'success';
}

// 2. LOGIKA PROSES PEMBAYARAN (POST) - (Tidak ada perubahan signifikan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    // ... (Logika POST tetap sama seperti sebelumnya) ...
    $order_id_to_pay = (int)$_POST['order_id'];
    $payment_method = $_POST['payment_method'];
    $total_amount_paid = (float)$_POST['total_amount_display'];

    if (empty($order_id_to_pay) || empty($payment_method) || empty($total_amount_paid)) {
        $message = 'Data pembayaran tidak lengkap.';
        $message_type = 'error';
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt_trans = mysqli_prepare($conn, "INSERT INTO transactions (order_id, total_amount, payment_method) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_trans, "ids", $order_id_to_pay, $total_amount_paid, $payment_method);
            mysqli_stmt_execute($stmt_trans);
            mysqli_stmt_close($stmt_trans);

            $stmt_order_update = mysqli_prepare($conn, "UPDATE orders SET status = 'completed' WHERE order_id = ? AND status = 'awaiting_payment'");
            mysqli_stmt_bind_param($stmt_order_update, "i", $order_id_to_pay);
            mysqli_stmt_execute($stmt_order_update);
            
            if (mysqli_stmt_affected_rows($stmt_order_update) == 0) throw new Exception("Pesanan tidak ditemukan atau sudah dibayar.");
            mysqli_stmt_close($stmt_order_update);

            mysqli_commit($conn);
            $success_msg = "Pembayaran untuk Pesanan #{$order_id_to_pay} berhasil!";
            header("Location: payment_processing.php?message=" . urlencode($success_msg));
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = 'Gagal memproses pembayaran: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}


// 3. LOGIKA UNTUK MENAMPILKAN DETAIL PESANAN TERPILIH (JIKA ADA)
if ($order_id_to_select) {
    // Ambil detail pesanan utama
    $stmt_order = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_id = ?");
    mysqli_stmt_bind_param($stmt_order, "i", $order_id_to_select);
    mysqli_stmt_execute($stmt_order);
    $selected_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_order));
    mysqli_stmt_close($stmt_order);

    if ($selected_order) {
        $stmt_items = mysqli_prepare($conn, "SELECT oi.quantity, oi.price_at_order, m.name FROM order_items oi JOIN menu m ON oi.menu_id = m.menu_id WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($stmt_items, "i", $order_id_to_select);
        mysqli_stmt_execute($stmt_items);
        $order_items_details = mysqli_fetch_all(mysqli_stmt_get_result($stmt_items), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_items);
    }
}

// 4. LOGIKA MENGAMBIL DAFTAR PESANAN / RIWAYAT
$orders_list = [];
if ($view == 'pending') {
    $sql_list = "SELECT o.order_id, o.table_number, o.customer_name, SUM(oi.quantity * oi.price_at_order) AS total_amount
                 FROM orders o JOIN order_items oi ON o.order_id = oi.order_id
                 WHERE o.status = 'awaiting_payment' GROUP BY o.order_id ORDER BY o.order_date ASC";
    $result_list = mysqli_query($conn, $sql_list);
    if ($result_list) $orders_list = mysqli_fetch_all($result_list, MYSQLI_ASSOC);

} elseif ($view == 'history') {
    $today_date = date('Y-m-d');
    $sql_list = "SELECT t.transaction_id, o.order_id, o.table_number, o.customer_name, t.total_amount
                 FROM transactions t JOIN orders o ON t.order_id = o.order_id
                 WHERE DATE(t.transaction_date) = ? ORDER BY t.transaction_date DESC";
    $stmt_list = mysqli_prepare($conn, $sql_list);
    mysqli_stmt_bind_param($stmt_list, "s", $today_date);
    mysqli_stmt_execute($stmt_list);
    $result_list = mysqli_stmt_get_result($stmt_list);
    if ($result_list) $orders_list = mysqli_fetch_all($result_list, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_list);
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pembayaran - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">

<div class="flex h-screen bg-slate-800">
    <aside class="w-64 flex-shrink-0 flex flex-col p-4 text-white">
        <a href="../dashboard.php" class="flex items-center gap-3 px-2 mb-8"><div class="bg-orange-500 p-2 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></div><span class="text-xl font-bold">RestoKU</span></a>
        <nav class="flex-1 space-y-2"><a href="../dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>Dashboard</a><div class="border-t border-slate-700 my-4"></div><h3 class="px-4 mt-4 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Aplikasi</h3><a href="payment_processing.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>Proses Pembayaran</a><a href="../admin/reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>Laporan Penjualan</a></nav>
    </aside>

    <main class="flex-1 grid grid-cols-1 lg:grid-cols-3 h-screen overflow-hidden">
        <div class="lg:col-span-1 bg-white border-r border-slate-200 flex flex-col h-screen">
            <div class="p-4 border-b border-slate-200">
                <div class="flex items-center bg-slate-100 p-1 rounded-lg">
                    <a href="?view=pending" class="flex-1 text-center px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $view == 'pending' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-800'; ?>">Menunggu Pembayaran</a>
                    <a href="?view=history" class="flex-1 text-center px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $view == 'history' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-800'; ?>">Riwayat Hari Ini</a>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-2">
                <?php if (empty($orders_list)): ?>
                    <p class="text-center text-slate-500 p-10">Tidak ada data untuk ditampilkan.</p>
                <?php else: foreach ($orders_list as $order): ?>
                    <?php if($view == 'pending'): ?>
                    <a href="?view=pending&select_order_id=<?php echo $order['order_id']; ?>" class="block p-4 rounded-lg mb-2 transition <?php echo ($order_id_to_select == $order['order_id']) ? 'bg-blue-100 ring-2 ring-blue-500' : 'hover:bg-slate-100'; ?>">
                        <div class="flex justify-between items-center"><span class="font-bold text-slate-800">Meja <?php echo htmlspecialchars($order['table_number']); ?></span><span class="text-sm font-semibold text-slate-900">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span></div>
                        <p class="text-sm text-slate-600"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                    </a>
                    <?php else: // $view == 'history' ?>
                    <div class="block p-4 rounded-lg mb-2 bg-slate-50 border border-slate-200">
                         <div class="flex justify-between items-center"><span class="font-bold text-slate-800">Meja <?php echo htmlspecialchars($order['table_number']); ?></span><span class="text-sm font-semibold text-slate-900">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span></div>
                         <p class="text-sm text-slate-600 mb-2"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                         <a href="receipt.php?order_id=<?php echo $order['order_id']; ?>" target="_blank" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Lihat & Cetak Ulang</a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="lg:col-span-2 p-6 lg:p-8 flex flex-col h-screen overflow-y-auto">
            <?php if ($message): ?>
            <div class="mb-6 <?php echo $message_type == 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded-md" role="alert"><p><?php echo $message; ?></p></div>
            <?php endif; ?>
            
            <?php if ($selected_order && $selected_order['status'] == 'awaiting_payment'): ?>
                <div class="bg-white p-6 rounded-xl shadow-md flex-grow flex flex-col"><div class="border-b-2 border-dashed border-slate-200 pb-4 mb-4"><h2 class="text-center text-xl font-bold text-slate-800">RestoKU</h2><p class="text-center text-xs text-slate-500">Pesanan #<?php echo htmlspecialchars($selected_order['order_id']); ?> | <?php echo date('d/m/Y H:i', strtotime($selected_order['order_date'])); ?></p></div><div class="mb-4"><p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($selected_order['customer_name']); ?></p><p><strong>Meja:</strong> <?php echo htmlspecialchars($selected_order['table_number']); ?></p></div><div class="flex-grow overflow-y-auto border-t border-b border-slate-200 py-4"><?php $grand_total = 0; foreach ($order_items_details as $item): $subtotal = $item['quantity'] * $item['price_at_order']; $grand_total += $subtotal; ?><div class="flex justify-between items-start mb-2"><div><p class="font-semibold text-slate-800"><?php echo htmlspecialchars($item['name']); ?></p><p class="text-sm text-slate-500"><?php echo $item['quantity']; ?> x Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></p></div><p class="font-semibold text-slate-800">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></p></div><?php endforeach; ?></div><div class="pt-4 space-y-2"><div class="flex justify-between items-center text-lg font-bold"><span class="text-slate-600">Total</span><span class="text-slate-900">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></span></div></div></div>
                <div class="bg-white p-6 rounded-xl shadow-md mt-6"><form action="payment_processing.php" method="POST"><input type="hidden" name="order_id" value="<?php echo htmlspecialchars($selected_order['order_id']); ?>"><input type="hidden" name="total_amount_display" value="<?php echo htmlspecialchars($grand_total); ?>"><div class="mb-4"><label for="payment_method" class="block text-sm font-medium text-slate-700 mb-1">Metode Pembayaran</label><select id="payment_method" name="payment_method" required class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="Tunai">Tunai</option><option value="Kartu Debit">Kartu Debit</option><option value="Kartu Kredit">Kartu Kredit</option><option value="QRIS">QRIS</option></select></div><div class="grid grid-cols-2 gap-4"><a href="receipt.php?order_id=<?php echo $selected_order['order_id']; ?>" target="_blank" class="w-full text-center bg-slate-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-slate-700 transition">Cetak Struk</a><button type="submit" name="process_payment" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 transition">Proses Pembayaran</button></div></form></div>
            <?php else: ?>
                <div class="flex-grow flex flex-col items-center justify-center text-center bg-white p-6 rounded-xl shadow-md"><svg class="w-16 h-16 text-slate-300 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg><h2 class="text-xl font-bold text-slate-700">Pilih Pesanan atau Riwayat</h2><p class="text-slate-500 mt-2">Silakan pilih item dari daftar di sebelah kiri untuk memulai.</p></div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>