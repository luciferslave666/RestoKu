<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

// Konfigurasi jumlah total setiap tipe meja
$table_limits = [
    'table_4' => 6,  // 6 meja untuk 4 orang
    'table_6' => 4,  // 4 meja untuk 6 orang
    'table_12' => 3, // 3 meja untuk 12 orang
];

// Ambil tanggal dan jumlah orang dari request GET
$date = $_GET['date'] ?? '';
$people = (int)($_GET['people'] ?? 0);

// Validasi input dasar
if (empty($date) || $people <= 0) {
    echo json_encode(['error' => 'Input tidak valid.']);
    exit;
}

// Tentukan tipe meja yang dibutuhkan berdasarkan jumlah orang
$required_table_type = '';
if ($people >= 1 && $people <= 4) {
    $required_table_type = 'table_4';
} elseif ($people >= 5 && $people <= 6) {
    $required_table_type = 'table_6';
} elseif ($people >= 7 && $people <= 12) {
    $required_table_type = 'table_12';
} else {
    echo json_encode(['error' => 'Jumlah orang melebihi kapasitas (maks 12).']);
    exit;
}

// Cek jumlah meja yang sudah dipesan untuk tipe dan tanggal tersebut
$sql = "SELECT COUNT(booking_id) AS total_bookings 
        FROM bookings 
        WHERE booking_date = ? AND table_type = ? AND status != 'cancelled'";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['error' => 'Gagal mempersiapkan query.']);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $date, $required_table_type);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$booked_count = (int)($row['total_bookings'] ?? 0);

mysqli_stmt_close($stmt);
mysqli_close($conn);

// Bandingkan jumlah yang sudah dipesan dengan batas total meja
$is_full = ($booked_count >= $table_limits[$required_table_type]);

// Kirim respons dalam format JSON
echo json_encode([
    'is_full' => $is_full,
    'booked' => $booked_count,
    'limit' => $table_limits[$required_table_type],
    'required_table' => $required_table_type
]);