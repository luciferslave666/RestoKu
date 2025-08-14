<?php
session_start();
include '../includes/db_connect.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'kasir'])) {
    header("Location: ../login.php");
    exit();
}
$user_role = $_SESSION['user_role'];
$username = $_SESSION['username']; // Ambil username untuk sidebar
$current_page = 'reports'; // Tandai halaman saat ini untuk sidebar aktif

// 2. LOGIKA PENGAMBILAN DATA LAPORAN
$report_type = $_GET['type'] ?? 'daily';
$report_data = [];
$report_title = '';
$grand_total_sales = 0;
$grand_total_orders = 0;

$sql = '';
if ($report_type == 'daily') {
    $report_title = 'Laporan Penjualan Harian';
    $sql = "SELECT DATE(transaction_date) AS period, SUM(total_amount) AS total_sales, COUNT(DISTINCT order_id) AS total_orders FROM transactions WHERE transaction_date >= CURDATE() - INTERVAL 30 DAY GROUP BY DATE(transaction_date) ORDER BY period DESC";
} elseif ($report_type == 'monthly') {
    $report_title = 'Laporan Penjualan Bulanan';
    $sql = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS period, SUM(total_amount) AS total_sales, COUNT(DISTINCT order_id) AS total_orders FROM transactions WHERE transaction_date >= DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, '%Y-%m-01') GROUP BY DATE_FORMAT(transaction_date, '%Y-%m') ORDER BY period DESC";
}

if ($sql) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        $report_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);

// 3. PENGOLAHAN DATA UNTUK GRAFIK & RINGKASAN
$chart_labels = [];
$chart_data = [];
if (!empty($report_data)) {
    $chart_ready_data = array_reverse($report_data);
    foreach ($chart_ready_data as $data) {
        if ($report_type == 'daily') {
            $chart_labels[] = date('d M', strtotime($data['period']));
        } else {
            $chart_labels[] = date('M Y', strtotime($data['period'] . '-01'));
        }
        $chart_data[] = $data['total_sales'];
    }
    $grand_total_sales = array_sum(array_column($report_data, 'total_sales'));
    $grand_total_orders = array_sum(array_column($report_data, 'total_orders'));
}
$average_sales = count($report_data) > 0 ? $grand_total_sales / count($report_data) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?> - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">

<div class="flex h-screen bg-slate-800">
    
    <?php include '../includes/sidebar.php'; // MEMANGGIL SIDEBAR TERPUSAT ?>

    <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Laporan Penjualan</h1>
            <div class="flex items-center gap-2">
                <div class="flex items-center bg-slate-200 p-1 rounded-lg">
                    <a href="?type=daily" class="px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $report_type == 'daily' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-800'; ?>">Harian</a>
                    <a href="?type=monthly" class="px-4 py-1.5 text-sm font-semibold rounded-md transition <?php echo $report_type == 'monthly' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-800'; ?>">Bulanan</a>
                </div>
                <div class="flex items-center gap-2">
                     <a href="export.php?format=pdf&type=<?php echo $report_type; ?>" target="_blank" class="px-3 py-1.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg flex items-center gap-2 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                        PDF
                    </a>
                     <a href="export.php?format=excel&type=<?php echo $report_type; ?>" class="px-3 py-1.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg flex items-center gap-2 transition">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2-2H4a2 2 0 01-2-2V6zm14 4a2 2 0 00-2-2H4a2 2 0 00-2 2v2a2 2 0 002 2h12a2 2 0 002-2v-2z" /></svg>
                        Excel
                    </a>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-4"><div class="bg-blue-100 text-blue-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Total Pendapatan</h3><p class="text-2xl font-bold text-slate-800 mt-1">Rp <?php echo number_format($grand_total_sales, 0, ',', '.'); ?></p></div></div>
            <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-4"><div class="bg-green-100 text-green-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Total Pesanan</h3><p class="text-2xl font-bold text-slate-800 mt-1"><?php echo number_format($grand_total_orders, 0, ',', '.'); ?></p></div></div>
            <div class="bg-white p-6 rounded-xl shadow-md flex items-center gap-4"><div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg></div><div><h3 class="text-sm font-medium text-slate-500">Rata-rata Penjualan</h3><p class="text-2xl font-bold text-slate-800 mt-1">Rp <?php echo number_format($average_sales, 0, ',', '.'); ?></p></div></div>
        </div>
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md mb-8"><h2 class="text-xl font-bold text-slate-800 mb-4">Grafik Penjualan <?php echo ucfirst($report_type); ?></h2><div><canvas id="salesChart" style="height: 300px;"></canvas></div></div>
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md"><h2 class="text-xl font-bold text-slate-800 mb-4">Detail Laporan</h2><div class="overflow-x-auto"><table class="min-w-full text-sm text-left text-slate-500"><thead class="text-xs text-slate-700 uppercase bg-slate-50"><tr><th scope="col" class="px-6 py-3">Periode</th><th scope="col" class="px-6 py-3 text-right">Total Penjualan</th><th scope="col" class="px-6 py-3 text-right">Jumlah Pesanan</th></tr></thead><tbody><?php if (empty($report_data)): ?><tr class="bg-white"><td colspan="3" class="text-center py-10 text-slate-500">Tidak ada data untuk ditampilkan pada periode ini.</td></tr><?php else: ?><?php foreach ($report_data as $data): ?><tr class="bg-white border-b hover:bg-slate-50 transition"><td class="px-6 py-4 font-medium text-slate-900"><?php if ($report_type == 'daily') { echo date('d F Y', strtotime($data['period'])); } else { echo date('F Y', strtotime($data['period'] . '-01')); } ?></td><td class="px-6 py-4 text-right">Rp <?php echo number_format($data['total_sales'], 0, ',', '.'); ?></td><td class="px-6 py-4 text-right"><?php echo htmlspecialchars($data['total_orders']); ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => { const ctx = document.getElementById('salesChart'); if (ctx) { const chartLabels = <?php echo json_encode($chart_labels); ?>; const chartData = <?php echo json_encode($chart_data); ?>; const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300); gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)'); gradient.addColorStop(1, 'rgba(59, 130, 246, 0)'); new Chart(ctx, { type: 'line', data: { labels: chartLabels, datasets: [{ label: 'Penjualan (Rp)', data: chartData, borderColor: 'rgba(59, 130, 246, 1)', backgroundColor: gradient, borderWidth: 2, pointBackgroundColor: 'rgba(59, 130, 246, 1)', pointBorderColor: '#fff', pointHoverRadius: 6, pointRadius: 4, tension: 0.3, fill: true }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); } } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(context) { let label = context.dataset.label || ''; if (label) { label += ': '; } if (context.parsed.y !== null) { label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y); } return label; } } } } } }); } });
</script>

</body>
</html>