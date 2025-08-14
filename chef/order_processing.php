<?php
session_start();
include '../includes/db_connect.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'chef') {
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

// 2. LOGIKA UPDATE STATUS
if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['order_id']) && isset($_GET['new_status'])) {
    $order_id = (int)$_GET['order_id'];
    $new_status = $_GET['new_status'];

    $allowed_statuses = ['processing', 'ready_to_serve', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $msg = "Status pesanan #{$order_id} berhasil diperbarui.";
        } else {
            $msg = "Gagal memperbarui status pesanan #{$order_id}.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "Status tidak valid.";
    }
    header("Location: order_processing.php?message=" . urlencode($msg));
    exit();
}

// 3. LOGIKA MENGAMBIL DAN MENGELOMPOKKAN PESANAN
$orders_by_status = [
    'pending' => [],
    'processing' => []
];
$new_order_sound = false; // Flag untuk notifikasi suara

$sql_orders = "SELECT o.order_id, o.table_number, o.order_date, o.status,
               GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, 'x)') SEPARATOR '||') AS order_details
               FROM orders o
               JOIN order_items oi ON o.order_id = oi.order_id
               JOIN menu mi ON oi.menu_id = mi.menu_id
               WHERE o.status IN ('pending', 'processing')
               GROUP BY o.order_id
               ORDER BY o.order_date ASC";

$result_orders = mysqli_query($conn, $sql_orders);
if ($result_orders) {
    while ($row = mysqli_fetch_assoc($result_orders)) {
        $orders_by_status[$row['status']][] = $row;
        // Jika ada pesanan pending yang baru masuk (kurang dari 30 detik), set flag notifikasi
        if ($row['status'] == 'pending' && (time() - strtotime($row['order_date'])) < 30) {
            $new_order_sound = true;
        }
    }
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengelolaan Pesanan Dapur - RestoKU</title>
    <meta http-equiv="refresh" content="20">
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
            <a href="order_processing.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>Kelola Pesanan Dapur</a>
        </nav>
    </aside>

    <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Kitchen Display System</h1>
            <a href="order_processing.php" class="flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-800"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.899 2.186l-1.353.902a5.002 5.002 0 00-8.546-2.261V5a1 1 0 01-2 0V3a1 1 0 011-1zm12 15a1 1 0 01-1-1v-2.101a7.002 7.002 0 01-11.899-2.186l1.353-.902a5.002 5.002 0 008.546 2.261V15a1 1 0 012 0v2a1 1 0 01-1 1z" clip-rule="evenodd" /></svg>Refresh Halaman</a>
        </div>
        
        <?php if ($message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6" role="alert">
            <p><?php echo $message; ?></p>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">
            
            <div class="bg-white rounded-xl shadow-md flex flex-col h-full">
                <div class="p-4 border-b-2 border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>Pesanan Baru
                        <span class="ml-auto text-sm font-semibold bg-yellow-100 text-yellow-800 rounded-full px-2 py-0.5"><?php echo count($orders_by_status['pending']); ?></span>
                    </h2>
                </div>
                <div class="p-4 space-y-4 overflow-y-auto">
                    <?php if (empty($orders_by_status['pending'])): ?>
                        <p class="text-slate-500 text-center py-10">Tidak ada pesanan baru.</p>
                    <?php else: foreach ($orders_by_status['pending'] as $order): ?>
                        <div class="bg-yellow-50 border-2 border-yellow-400 p-4 rounded-lg shadow-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-900 text-xl">Meja <?php echo htmlspecialchars($order['table_number']); ?></span>
                                <span class="text-sm text-slate-600 font-semibold" data-time="<?php echo $order['order_date']; ?>"></span>
                            </div>
                            <ul class="list-disc list-inside space-y-1 mb-4">
                                <?php foreach (explode('||', $order['order_details']) as $detail): ?>
                                    <li class="text-slate-700 font-medium"><?php echo htmlspecialchars(trim($detail)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="?action=update_status&order_id=<?php echo $order['order_id']; ?>&new_status=processing" class="w-full block text-center bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-yellow-600 transition">
                                Mulai Masak
                            </a>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md flex flex-col h-full">
                <div class="p-4 border-b-2 border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>Sedang Dimasak
                         <span class="ml-auto text-sm font-semibold bg-blue-100 text-blue-800 rounded-full px-2 py-0.5"><?php echo count($orders_by_status['processing']); ?></span>
                    </h2>
                </div>
                <div class="p-4 space-y-4 overflow-y-auto">
                    <?php if (empty($orders_by_status['processing'])): ?>
                        <p class="text-slate-500 text-center py-10">Tidak ada pesanan yang sedang dimasak.</p>
                    <?php else: foreach ($orders_by_status['processing'] as $order): ?>
                         <div class="bg-blue-50 border border-blue-300 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-slate-900 text-xl">Meja <?php echo htmlspecialchars($order['table_number']); ?></span>
                                <span class="text-sm text-slate-600 font-semibold" data-time="<?php echo $order['order_date']; ?>"></span>
                            </div>
                            <ul class="list-disc list-inside space-y-1 mb-4">
                                <?php foreach (explode('||', $order['order_details']) as $detail): ?>
                                    <li class="text-slate-700 font-medium"><?php echo htmlspecialchars(trim($detail)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="?action=update_status&order_id=<?php echo $order['order_id']; ?>&new_status=ready_to_serve" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition">
                                Selesai & Siap Disajikan
                            </a>
                         </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<audio id="notificationSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fitur Notifikasi Suara
    const playSound = <?php echo $new_order_sound ? 'true' : 'false'; ?>;
    if (playSound) {
        document.getElementById('notificationSound').play().catch(e => console.log("Autoplay ditolak oleh browser. Diperlukan interaksi pengguna."));
    }
    
    // Fitur Penghitung Waktu
    const timerElements = document.querySelectorAll('[data-time]');
    
    function updateTimers() {
        timerElements.forEach(el => {
            const orderTime = new Date(el.dataset.time).getTime();
            const now = new Date().getTime();
            const diff = Math.floor((now - orderTime) / 1000);

            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            
            // Tambahkan warna merah jika lebih dari 10 menit
            if (minutes >= 10) {
                el.classList.add('text-red-600');
            }

            el.textContent = `${minutes}m ${seconds < 10 ? '0' : ''}${seconds}s lalu`;
        });
    }

    // Update timer setiap detik
    setInterval(updateTimers, 1000);
    updateTimers(); // Panggil sekali saat load
});
</script>

</body>
</html>