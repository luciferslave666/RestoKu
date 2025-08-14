<?php
session_start();
include '../includes/db_connect.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'pelayan') {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role']; // untuk sidebar
$username = $_SESSION['username']; // untuk sidebar

$message = '';
$message_type = '';

// Menampilkan pesan dari redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    // Asumsi redirect selalu sukses, bisa dibuat lebih kompleks
    $message_type = 'success'; 
}

// 2. LOGIKA UPDATE STATUS
if (isset($_GET['action']) && $_GET['action'] == 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $booking_id = $_GET['id'];
    $new_status = $_GET['status'];

    $allowed_statuses = ['confirmed', 'used', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $booking_id);
        if (mysqli_stmt_execute($stmt)) {
            $msg = 'Status booking berhasil diperbarui.';
            $type = 'success';
        } else {
            $msg = 'Gagal memperbarui status booking.';
            $type = 'error';
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = 'Status tidak valid.';
        $type = 'error';
    }
    header("Location: booking_management.php?message=" . urlencode($msg) . "&type=" . $type);
    exit();
}


// 3. LOGIKA READ DATA BOOKING DENGAN FILTER
$filter = $_GET['filter'] ?? 'all'; // Default 'all'
$bookings = [];
$sql_bookings = "SELECT * FROM bookings";
$params = [];
$types = "";

$allowed_filters = ['pending', 'confirmed', 'used', 'cancelled'];
if (in_array($filter, $allowed_filters)) {
    $sql_bookings .= " WHERE status = ?";
    $params[] = $filter;
    $types .= "s";
}

$sql_bookings .= " ORDER BY booking_date DESC, booking_time DESC";

// Menggunakan prepared statement untuk mengambil data
$stmt_bookings = mysqli_prepare($conn, $sql_bookings);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_bookings, $types, ...$params);
}
mysqli_stmt_execute($stmt_bookings);
$result_bookings = mysqli_stmt_get_result($stmt_bookings);
if ($result_bookings) {
    $bookings = mysqli_fetch_all($result_bookings, MYSQLI_ASSOC);
}
mysqli_stmt_close($stmt_bookings);

mysqli_close($conn);

// Helper function untuk badge status
function get_booking_status_badge($status) {
    $status_text = ucfirst(htmlspecialchars($status));
    $colors = [
        'pending'   => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-green-100 text-green-800',
        'used'      => 'bg-blue-100 text-blue-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
    $class = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-3 py-1 text-xs font-medium rounded-full $class'>$status_text</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Booking - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
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
        <nav class="flex-1 space-y-2">
            <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
                Dashboard
            </a>
            <div class="border-t border-slate-700 my-4"></div>
            <h3 class="px-4 mt-4 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Aplikasi</h3>
            <a href="booking_management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-slate-700 font-semibold transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                Manajemen Booking
            </a>
            <a href="order_taking.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2V3zm5 9a1 1 0 11-2 0 1 1 0 012 0z" /><path d="M7 13a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" /></svg>
                Input Pesanan
            </a>
            <a href="order_status_view.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-slate-700 transition-colors">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C3.732 5.943 7.523 3 12 3c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM12 15a5 5 0 100-10 5 5 0 000 10z" clip-rule="evenodd" /></svg>
                Status Pesanan
            </a>
        </nav>
        </aside>

    <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold text-slate-800 mb-6">Manajemen Booking Meja</h1>
        
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md">
            <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Daftar Booking</h2>
                <div class="flex items-center border border-slate-200 p-1 rounded-lg">
                    <a href="?filter=all" class="px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $filter == 'all' ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">Semua</a>
                    <a href="?filter=pending" class="px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $filter == 'pending' ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">Pending</a>
                    <a href="?filter=confirmed" class="px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $filter == 'confirmed' ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">Dikonfirmasi</a>
                    <a href="?filter=used" class="px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $filter == 'used' ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">Digunakan</a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-6" role="alert">
                <p><?php echo $message; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-slate-500">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Pelanggan</th>
                            <th scope="col" class="px-6 py-3">Detail Booking</th>
                            <th scope="col" class="px-6 py-3">Jumlah Orang</th>
                            <th scope="col" class="px-6 py-3 text-center">Status</th>
                            <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr class="bg-white"><td colspan="5" class="text-center py-10 text-slate-500">Tidak ada data booking untuk status "<?php echo ucfirst($filter); ?>".</td></tr>
                        <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="bg-white border-b hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                <div class="text-slate-500"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?php echo date('d F Y', strtotime($booking['booking_date'])); ?></div>
                                <div class="text-slate-500">Pukul <?php echo date('H:i', strtotime($booking['booking_time'])); ?></div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($booking['number_of_people']); ?> orang</td>
                            <td class="px-6 py-4 text-center"><?php echo get_booking_status_badge($booking['status']); ?></td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="?action=status&id=<?php echo $booking['booking_id']; ?>&status=confirmed" class="font-medium text-green-600 hover:text-green-800 transition">Konfirmasi</a>
                                    <a href="?action=status&id=<?php echo $booking['booking_id']; ?>&status=cancelled" onclick="return confirm('Anda yakin ingin membatalkan booking ini?');" class="font-medium text-red-600 hover:text-red-800 transition">Batalkan</a>
                                <?php elseif ($booking['status'] == 'confirmed'): ?>
                                    <a href="?action=status&id=<?php echo $booking['booking_id']; ?>&status=used" class="font-medium text-blue-600 hover:text-blue-800 transition">Tandai Digunakan</a>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                                </div>
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