<?php
session_start();
include 'includes/db_connect.php';

// 1. OTENTIKASI
if (!isset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];

// 2. PERSIAPAN DATA
$dashboard_data = [];
$navigation_links = [];

function fetch_single_value($conn, $sql, $params = null, $types = "") {
    $stmt = mysqli_prepare($conn, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);
    mysqli_stmt_close($stmt);
    return $row[0] ?? 0;
}

// 3. LOGIKA BERBASIS PERAN (FINAL)
$today = date('Y-m-d');

if ($user_role == 'pelayan') {
    $navigation_links = [
        ['href' => 'pelayan/booking_management.php', 'label' => 'Manajemen Booking', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>'],
        ['href' => 'pelayan/order_taking.php', 'label' => 'Input Pesanan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2V3zm5 9a1 1 0 11-2 0 1 1 0 012 0z" /><path d="M7 13a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" /></svg>'],
        ['href' => 'pelayan/order_status_view.php', 'label' => 'Status Pesanan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C3.732 5.943 7.523 3 12 3c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM12 15a5 5 0 100-10 5 5 0 000 10z" clip-rule="evenodd" /></svg>'],
    ];
    $dashboard_data['today_bookings'] = fetch_single_value($conn, "SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'pending'", [$today], "s");
    $sql_orders_status = "SELECT COUNT(CASE WHEN status = 'pending' THEN 1 END), COUNT(CASE WHEN status = 'processing' THEN 1 END), COUNT(CASE WHEN status = 'ready_to_serve' THEN 1 END) FROM orders WHERE status IN ('pending', 'processing', 'ready_to_serve')";
    $stmt = mysqli_prepare($conn, $sql_orders_status);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $counts = mysqli_fetch_row($result);
    $dashboard_data['pending_kitchen_orders'] = $counts[0] ?? 0;
    $dashboard_data['processing_kitchen_orders'] = $counts[1] ?? 0;
    $dashboard_data['ready_to_serve_orders'] = $counts[2] ?? 0;
    mysqli_stmt_close($stmt);
    $sql_all_orders = "SELECT order_id, table_number, order_date, status, customer_name FROM orders WHERE status IN ('pending', 'processing', 'ready_to_serve') ORDER BY FIELD(status, 'ready_to_serve', 'processing', 'pending'), order_date ASC";
    $stmt = mysqli_prepare($conn, $sql_all_orders);
    mysqli_stmt_execute($stmt);
    $result_all_orders = mysqli_stmt_get_result($stmt);
    $dashboard_data['all_current_orders'] = mysqli_fetch_all($result_all_orders, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

} elseif ($user_role == 'chef') {
    $navigation_links = [['href' => 'chef/order_processing.php', 'label' => 'Kelola Pesanan Dapur', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>']];
    $dashboard_data['new_orders'] = fetch_single_value($conn, "SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $dashboard_data['processing_orders'] = fetch_single_value($conn, "SELECT COUNT(*) FROM orders WHERE status = 'processing'");

} elseif ($user_role == 'kasir') {
    $navigation_links = [
        ['href' => 'kasir/payment_processing.php', 'label' => 'Proses Pembayaran', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>'],
        ['href' => 'admin/reports.php', 'label' => 'Laporan Penjualan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>'],
    ];
    $total_sales = fetch_single_value($conn, "SELECT SUM(total_amount) FROM transactions WHERE DATE(transaction_date) = ?", [$today], "s");
    $dashboard_data['total_sales_today'] = 'Rp ' . number_format($total_sales, 0, ',', '.');
    $dashboard_data['count_transactions_today'] = fetch_single_value($conn, "SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date) = ?", [$today], "s");
    $dashboard_data['awaiting_payment_orders'] = fetch_single_value($conn, "SELECT COUNT(*) FROM orders WHERE status = 'awaiting_payment'");

} elseif ($user_role == 'admin') {
    $navigation_links = [
        ['href' => 'admin/menu_management.php', 'label' => 'Manajemen Menu', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>'],
        ['href' => 'admin/user_management.php', 'label' => 'Manajemen Pengguna', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm-3 2a5 5 0 00-5 5v1a1 1 0 001 1h8a1 1 0 001-1v-1a5 5 0 00-5-5zM17 6a3 3 0 11-6 0 3 3 0 016 0zm-3 2a5 5 0 00-4.545 3.372A3.999 3.999 0 0115 11a4 4 0 014 4v1a1 1 0 01-1 1h-2a1 1 0 01-1-1v-1a2 2 0 00-2-2z" /></svg>'],
        ['href' => 'admin/reports.php', 'label' => 'Laporan Penjualan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>'],
    ];
    $dashboard_data['total_menu'] = fetch_single_value($conn, "SELECT COUNT(*) FROM menu");
    $dashboard_data['total_users'] = fetch_single_value($conn, "SELECT COUNT(*) FROM users");
    $total_sales = fetch_single_value($conn, "SELECT SUM(total_amount) FROM transactions WHERE DATE(transaction_date) = ?", [$today], "s");
    $dashboard_data['total_sales_today'] = 'Rp ' . number_format($total_sales, 0, ',', '.');
    $dashboard_data['count_transactions_today'] = fetch_single_value($conn, "SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date) = ?", [$today], "s");
    $dashboard_data['active_orders'] = fetch_single_value($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing', 'ready_to_serve')");
    $dashboard_data['pending_bookings'] = fetch_single_value($conn, "SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'pending'", [$today], "s");
}

mysqli_close($conn);

function get_status_badge($status) {
    $status_text = ucfirst(str_replace('_', ' ', htmlspecialchars($status)));
    $status_class = '';
    switch ($status) {
        case 'pending': $status_class = 'bg-yellow-100 text-yellow-800'; break;
        case 'processing': $status_class = 'bg-blue-100 text-blue-800'; break;
        case 'ready_to_serve': $status_class = 'bg-green-100 text-green-800'; break;
        default: $status_class = 'bg-gray-100 text-gray-800'; break;
    }
    return "<span class='px-3 py-1 text-xs font-medium rounded-full $status_class'>$status_text</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RestoKU</title>
    <link href="./assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">
    <div class="flex h-screen bg-slate-800">
        <aside class="w-64 flex-shrink-0 flex flex-col p-4 text-white">
            <a href="dashboard.php" class="flex items-center gap-3 px-2 mb-8">
                <div class="bg-orange-500 p-2 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></div>
                <span class="text-xl font-bold">RestoKU</span>
            </a>
            <nav class="flex-1 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 text-white font-semibold"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>Dashboard</a>
                <?php if (!empty($navigation_links)): ?>
                <div class="border-t border-slate-700 my-4"></div>
                <h3 class="px-4 mt-4 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Aplikasi</h3>
                <?php foreach ($navigation_links as $link): ?>
                    <a href="<?php echo $link['href']; ?>" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                        <?php echo $link['icon']; ?>
                        <?php echo htmlspecialchars($link['label']); ?>
                    </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="mt-auto border-t border-slate-700 pt-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center font-bold"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    <div>
                        <p class="font-semibold"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-sm text-slate-400"><?php echo ucfirst($user_role); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center justify-center gap-2 w-full mt-4 px-4 py-2 bg-slate-700 hover:bg-red-600 rounded-lg transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" /></svg>Logout</a>
            </div>
        </aside>

        <main class="flex-1 p-6 lg:p-8 overflow-y-auto bg-slate-100">
            <h1 class="text-3xl font-bold text-slate-800 mb-2">Selamat Datang, <?php echo htmlspecialchars($username); ?>!</h1>
            <p class="text-slate-600 mb-6">Berikut adalah ringkasan aktivitas restoran Anda hari ini, <?php echo date('d F Y'); ?>.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <?php if ($user_role == 'admin'): ?>
                    <a href="admin/reports.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-green-100 text-green-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Pendapatan Hari Ini</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['total_sales_today']; ?></p></div></a>
                    <a href="admin/reports.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-blue-100 text-blue-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Transaksi Hari Ini</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['count_transactions_today']; ?></p></div></a>
                    <a href="pelayan/order_status_view.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Pesanan Aktif</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['active_orders']; ?></p></div></a>
                    <a href="pelayan/booking_management.php?filter=pending" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-indigo-100 text-indigo-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Booking Pending</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['pending_bookings']; ?></p></div></a>
                    <a href="admin/menu_management.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-slate-100 text-slate-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Total Menu</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['total_menu']; ?></p></div></a>
                    <a href="admin/user_management.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-slate-100 text-slate-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Total Pengguna</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['total_users']; ?></p></div></a>
                
                <?php elseif ($user_role == 'pelayan'): ?>
                    <a href="pelayan/booking_management.php?filter=pending" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-indigo-100 text-indigo-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Booking Pending</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['today_bookings']; ?></p></div></a>
                    <a href="pelayan/order_status_view.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Menunggu Dapur</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['pending_kitchen_orders']; ?></p></div></a>
                    <a href="pelayan/order_status_view.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-blue-100 text-blue-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Sedang Diproses</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['processing_kitchen_orders']; ?></p></div></a>
                    <a href="pelayan/order_status_view.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-green-100 text-green-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Siap Disajikan</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['ready_to_serve_orders']; ?></p></div></a>

                <?php elseif ($user_role == 'chef'): ?>
                    <a href="chef/order_processing.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 col-span-1 md:col-span-2 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Pesanan Baru</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['new_orders']; ?></p></div></a>
                    <a href="chef/order_processing.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 col-span-1 md:col-span-2 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-blue-100 text-blue-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.657 7.343A8 8 0 0117.657 18.657z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Sedang Dimasak</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['processing_orders']; ?></p></div></a>

                <?php elseif ($user_role == 'kasir'): ?>
                    <a href="admin/reports.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-green-100 text-green-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Pendapatan Hari Ini</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['total_sales_today']; ?></p></div></a>
                    <a href="admin/reports.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-blue-100 text-blue-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Transaksi Hari Ini</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['count_transactions_today']; ?></p></div></a>
                    <a href="kasir/payment_processing.php" class="block bg-white p-6 rounded-xl shadow-md flex items-center gap-4 transition hover:ring-2 hover:ring-blue-500 hover:shadow-lg"><div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Menunggu Pembayaran</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $dashboard_data['awaiting_payment_orders']; ?></p></div></a>
                <?php endif; ?>
            </div>

            <?php if ($user_role == 'pelayan' && !empty($dashboard_data['all_current_orders'])): ?>
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">Detail Pesanan Aktif</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-slate-500">
                            <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">No. Meja</th>
                                    <th scope="col" class="px-6 py-3">Pelanggan</th>
                                    <th scope="col" class="px-6 py-3">Waktu Pesan</th>
                                    <th scope="col" class="px-6 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_data['all_current_orders'] as $order): ?>
                                <tr class="bg-white border-b hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($order['table_number']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars(date('H:i, d M Y', strtotime($order['order_date']))); ?></td>
                                    <td class="px-6 py-4 text-center"><?php echo get_status_badge($order['status']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif($user_role == 'pelayan' && empty($dashboard_data['all_current_orders'])): ?>
                 <div class="bg-white p-8 rounded-xl shadow-md text-center">
                    <h3 class="text-lg font-medium text-slate-800">🎉 Semua pesanan sudah selesai!</h3>
                    <p class="text-slate-500 mt-2">Belum ada pesanan aktif saat ini. Waktu yang tepat untuk bersantai sejenak.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>