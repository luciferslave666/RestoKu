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

// Menampilkan pesan dari redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = 'success';
}

// 2. LOGIKA UNTUK MENYELESAIKAN PESANAN
if (isset($_GET['action']) && $_GET['action'] == 'complete_order' && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $new_status = 'completed'; // Status akhir setelah disajikan

    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE order_id = ? AND status = 'ready_to_serve'");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $msg = "Pesanan #{$order_id} telah diselesaikan.";
        } else {
            $msg = "Gagal menyelesaikan pesanan #{$order_id} atau statusnya bukan 'Siap Disajikan'.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "Terjadi kesalahan pada server.";
    }
    header("Location: order_status_view.php?message=" . urlencode($msg));
    exit();
}

// 3. LOGIKA UNTUK MENGAMBIL DAN MENGELOMPOKKAN PESANAN
$orders_by_status = [
    'pending' => [],
    'processing' => [],
    'ready_to_serve' => []
];

$sql_orders = "SELECT o.order_id, o.table_number, o.order_date, o.status, o.customer_name,
               GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, 'x)') SEPARATOR ', ') AS order_details
               FROM orders o
               JOIN order_items oi ON o.order_id = oi.order_id
               JOIN menu mi ON oi.menu_id = mi.menu_id
               WHERE o.status IN ('pending', 'processing', 'ready_to_serve')
               GROUP BY o.order_id
               ORDER BY o.order_date ASC";

// Menggunakan prepared statement untuk konsistensi
$stmt_get_orders = mysqli_prepare($conn, $sql_orders);
mysqli_stmt_execute($stmt_get_orders);
$result_orders = mysqli_stmt_get_result($stmt_get_orders);

if ($result_orders) {
    while ($row = mysqli_fetch_assoc($result_orders)) {
        $orders_by_status[$row['status']][] = $row;
    }
} else {
    $message = 'Gagal mengambil data pesanan.';
    $message_type = 'error';
}
mysqli_stmt_close($stmt_get_orders);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan Dapur - RestoKU</title>
    <meta http-equiv="refresh" content="30">
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">

<div class="flex h-screen bg-slate-800">
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
            <a href="order_taking.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2V3zm5 9a1 1 0 11-2 0 1 1 0 012 0z" /><path d="M7 13a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" /></svg>Input Pesanan</a>
            <a href="order_status_view.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C3.732 5.943 7.523 3 12 3c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM12 15a5 5 0 100-10 5 5 0 000 10z" clip-rule="evenodd" /></svg>Status Pesanan</a>
        </nav>
    </aside>

    <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Status Pesanan Dapur</h1>
            <a href="order_status_view.php" class="flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.899 2.186l-1.353.902a5.002 5.002 0 00-8.546-2.261V5a1 1 0 01-2 0V3a1 1 0 011-1zm12 15a1 1 0 01-1-1v-2.101a7.002 7.002 0 01-11.899-2.186l1.353-.902a5.002 5.002 0 008.546 2.261V15a1 1 0 012 0v2a1 1 0 01-1 1z" clip-rule="evenodd" /></svg>
                Refresh Halaman
            </a>
        </div>
        
        <?php if ($message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6" role="alert">
            <p><?php echo $message; ?></p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
            
            <div class="bg-white rounded-xl shadow-md flex flex-col h-full">
                <div class="p-4 border-b-2 border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                        Menunggu Dapur
                        <span class="ml-auto text-sm font-semibold bg-yellow-100 text-yellow-800 rounded-full px-2 py-0.5"><?php echo count($orders_by_status['pending']); ?></span>
                    </h2>
                </div>
                <div class="p-4 space-y-4 overflow-y-auto">
                    <?php if (empty($orders_by_status['pending'])): ?>
                        <p class="text-slate-500 text-center py-10">Tidak ada pesanan baru.</p>
                    <?php else: foreach ($orders_by_status['pending'] as $order): ?>
                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-900">Meja <?php echo htmlspecialchars($order['table_number']); ?></span>
                                <span class="text-xs text-slate-500"><?php echo date('H:i', strtotime($order['order_date'])); ?></span>
                            </div>
                            <p class="text-sm text-slate-600 mb-3"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <div class="text-sm border-t border-slate-200 pt-3">
                                <p class="font-semibold text-slate-700"><?php echo htmlspecialchars($order['order_details']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md flex flex-col h-full">
                <div class="p-4 border-b-2 border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        Sedang Diproses
                         <span class="ml-auto text-sm font-semibold bg-blue-100 text-blue-800 rounded-full px-2 py-0.5"><?php echo count($orders_by_status['processing']); ?></span>
                    </h2>
                </div>
                 <div class="p-4 space-y-4 overflow-y-auto">
                    <?php if (empty($orders_by_status['processing'])): ?>
                        <p class="text-slate-500 text-center py-10">Tidak ada pesanan yang diproses.</p>
                    <?php else: foreach ($orders_by_status['processing'] as $order): ?>
                         <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-900">Meja <?php echo htmlspecialchars($order['table_number']); ?></span>
                                <span class="text-xs text-slate-500"><?php echo date('H:i', strtotime($order['order_date'])); ?></span>
                            </div>
                            <p class="text-sm text-slate-600 mb-3"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <div class="text-sm border-t border-slate-200 pt-3">
                                <p class="font-semibold text-slate-700"><?php echo htmlspecialchars($order['order_details']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md flex flex-col h-full">
                <div class="p-4 border-b-2 border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        Siap Disajikan
                         <span class="ml-auto text-sm font-semibold bg-green-100 text-green-800 rounded-full px-2 py-0.5"><?php echo count($orders_by_status['ready_to_serve']); ?></span>
                    </h2>
                </div>
                 <div class="p-4 space-y-4 overflow-y-auto">
                    <?php if (empty($orders_by_status['ready_to_serve'])): ?>
                        <p class="text-slate-500 text-center py-10">Tidak ada pesanan yang siap.</p>
                    <?php else: foreach ($orders_by_status['ready_to_serve'] as $order): ?>
                         <div class="bg-green-50 border-2 border-green-500 p-4 rounded-lg shadow-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-900">Meja <?php echo htmlspecialchars($order['table_number']); ?></span>
                                <span class="text-xs text-slate-500"><?php echo date('H:i', strtotime($order['order_date'])); ?></span>
                            </div>
                            <p class="text-sm text-slate-600 mb-3"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <div class="text-sm border-t border-green-200 pt-3 mb-4">
                                <p class="font-semibold text-slate-700"><?php echo htmlspecialchars($order['order_details']); ?></p>
                            </div>
                            <a href="?action=complete_order&order_id=<?php echo $order['order_id']; ?>" 
                               onclick="return confirm('Konfirmasi bahwa pesanan ini sudah diantar ke pelanggan?');"
                               class="w-full block text-center bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700 transition">
                                Sajikan & Selesaikan Pesanan
                            </a>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>