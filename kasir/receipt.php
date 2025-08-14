<?php
session_start();
include '../includes/db_connect.php';

// Otentikasi & Otorisasi
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['kasir', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

// Validasi ID pesanan dari URL
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("ID Pesanan tidak valid.");
}
$order_id = (int)$_GET['order_id'];

// Ambil detail pesanan
$stmt_order = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_id = ?");
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$order_details = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_order));
mysqli_stmt_close($stmt_order);

if (!$order_details) {
    die("Pesanan tidak ditemukan.");
}

// Ambil item-item pesanan
$stmt_items = mysqli_prepare($conn, "SELECT oi.quantity, oi.price_at_order, m.name FROM order_items oi JOIN menu m ON oi.menu_id = m.menu_id WHERE oi.order_id = ?");
mysqli_stmt_bind_param($stmt_items, "i", $order_id);
mysqli_stmt_execute($stmt_items);
$order_items = mysqli_fetch_all(mysqli_stmt_get_result($stmt_items), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_items);

// Ambil detail transaksi jika ada
$stmt_trans = mysqli_prepare($conn, "SELECT payment_method, transaction_date FROM transactions WHERE order_id = ?");
mysqli_stmt_bind_param($stmt_trans, "i", $order_id);
mysqli_stmt_execute($stmt_trans);
$transaction_details = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_trans));
mysqli_stmt_close($stmt_trans);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan #<?php echo $order_id; ?></title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; background-color: #fff; }
        }
    </style>
</head>
<body class="bg-slate-100 flex justify-center items-start min-h-screen p-4 md:p-8">
    
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-lg">
        <div id="receipt-content">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold">RestoKU</h1>
                <p class="text-sm text-slate-500">Jl. Restoran No. 123, Kota Kuliner</p>
                <p class="text-sm text-slate-500">Telp: (021) 123-4567</p>
            </div>
            <div class="border-t-2 border-dashed border-slate-300 my-4"></div>
            <div class="flex justify-between text-sm text-slate-600 mb-2">
                <span>No. Pesanan: #<?php echo htmlspecialchars($order_details['order_id']); ?></span>
                <span>Kasir: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="flex justify-between text-sm text-slate-600 mb-4">
                 <span>Tanggal: <?php echo date('d/m/Y H:i', strtotime($transaction_details['transaction_date'] ?? $order_details['order_date'])); ?></span>
            </div>

            <div class="space-y-2 border-t border-slate-200 pt-4">
                 <?php $grand_total = 0; foreach ($order_items as $item): $subtotal = $item['quantity'] * $item['price_at_order']; $grand_total += $subtotal; ?>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($item['name']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo $item['quantity']; ?> x @ Rp <?php echo number_format($item['price_at_order'], 0, ',', '.'); ?></p>
                    </div>
                    <p class="font-semibold text-slate-800">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></p>
                </div>
                <?php endforeach; ?>
            </div>

             <div class="border-t-2 border-dashed border-slate-300 my-4"></div>
             <div class="space-y-2">
                <div class="flex justify-between font-semibold">
                    <span>Total</span>
                    <span>Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></span>
                </div>
                 <?php if($transaction_details): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Metode Bayar</span>
                    <span class="text-slate-800"><?php echo htmlspecialchars($transaction_details['payment_method']); ?></span>
                </div>
                <?php endif; ?>
             </div>
             <div class="text-center mt-8 text-sm text-slate-500">
                <p>Terima kasih atas kunjungan Anda!</p>
             </div>
        </div>

        <div class="mt-6 text-center no-print">
            <button onclick="window.print()" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition">
                Cetak Struk
            </button>
            <button onclick="window.close()" class="w-full bg-slate-200 text-slate-800 font-bold py-3 px-4 rounded-lg hover:bg-slate-300 transition mt-2">
                Tutup
            </button>
        </div>
    </div>

</body>
</html>