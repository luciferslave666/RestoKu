<?php
session_start();
include '../includes/db_connect.php';

ob_start();


// 2. VALIDASI PARAMETER
$allowed_types = ['daily', 'monthly'];
$allowed_formats = ['pdf', 'excel'];

$report_type = $_GET['type'] ?? 'daily';
$format = $_GET['format'] ?? null;

if (!in_array($report_type, $allowed_types)) $report_type = 'daily';
if ($format && !in_array($format, $allowed_formats)) $format = null;

$report_title = ($report_type === 'daily') ? 'Laporan Penjualan Harian' : 'Laporan Penjualan Bulanan';
$report_data = [];

// 3. QUERY DATA
$sql = '';
if ($report_type === 'daily') {
    $sql = "SELECT DATE(transaction_date) AS period, SUM(total_amount) AS total_sales, COUNT(DISTINCT order_id) AS total_orders
            FROM transactions
            WHERE transaction_date >= CURDATE() - INTERVAL 30 DAY
            GROUP BY DATE(transaction_date)
            ORDER BY period DESC";
} else {
    $sql = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS period, SUM(total_amount) AS total_sales, COUNT(DISTINCT order_id) AS total_orders
            FROM transactions
            WHERE transaction_date >= DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, '%Y-%m-01')
            GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
            ORDER BY period DESC";
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

// 4. EXPORT TO PDF
if ($format === 'pdf') {
    require('../fpdf/fpdf.php');

    $pdf = new FPDF();
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    // Judul
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 0, 128);
    $pdf->Cell(0, 10, $report_title, 0, 1, 'C');
    $pdf->Ln(5);

    // Header Tabel
    $pdf->SetFillColor(200, 220, 255);
    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(80, 10, 'Periode', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Total Penjualan', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Jumlah Pesanan', 1, 1, 'C', true);

    // Isi Tabel
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetFillColor(245, 245, 245);
    $fill = false;

    foreach ($report_data as $row) {
        $period_display = ($report_type === 'daily')
            ? date('d F Y', strtotime($row['period']))
            : date('F Y', strtotime($row['period'] . '-01'));

        $pdf->Cell(80, 8, $period_display, 1, 0, 'L', $fill);
        $pdf->Cell(50, 8, 'Rp ' . number_format($row['total_sales'], 0, ',', '.'), 1, 0, 'R', $fill);
        $pdf->Cell(50, 8, number_format($row['total_orders'], 0, ',', '.'), 1, 1, 'R', $fill);
        $fill = !$fill;
    }

    ob_end_clean();
    $pdf->Output('D', $report_title . '.pdf');
    exit();
}

// 5. EXPORT TO EXCEL
elseif ($format === 'excel') {
    ob_end_clean();
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$report_title.xls\"");

    echo "$report_title\n\n";
    echo "Periode\tTotal Penjualan\tJumlah Pesanan\n";

    foreach ($report_data as $row) {
        $period_display = ($report_type === 'daily')
            ? date('d F Y', strtotime($row['period']))
            : date('F Y', strtotime($row['period'] . '-01'));

        $sales = 'Rp ' . number_format($row['total_sales'], 0, ',', '.');
        $orders = number_format($row['total_orders'], 0, ',', '.');

        echo "$period_display\t$sales\t$orders\n";
    }
    exit();
}

// 6. REDIRECT JIKA FORMAT TIDAK VALID
ob_end_flush();
header("Location: reports.php?type=" . $report_type);
exit();
?>
