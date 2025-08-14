<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$date = $_GET['date'] ?? date('Y-m-d');

if (empty($date)) {
    echo json_encode(['error' => 'Tanggal tidak valid.']);
    exit;
}

$sql = "
    SELECT 
        t.table_id, 
        t.table_type, 
        t.description,
        CASE 
            WHEN t.table_type = 'table_2' THEN 2 -- INI BARIS BARU
            WHEN t.table_type = 'table_4' THEN 4
            WHEN t.table_type = 'table_6' THEN 6
            WHEN t.table_type = 'table_12' THEN 12
            ELSE 0
        END AS capacity,
        CASE 
            WHEN b.booking_id IS NOT NULL THEN 'booked' 
            ELSE 'available' 
        END AS status
    FROM 
        tables t
    LEFT JOIN 
        bookings b ON t.table_id = b.table_id AND b.booking_date = ? AND b.status != 'cancelled'
    ORDER BY t.table_id ASC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tables = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tables[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($tables);