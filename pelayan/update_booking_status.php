<?php
include '../includes/db_connect.php';

// Asumsikan ada logika autentikasi dan otorisasi di sini untuk peran 'pelayan'

if (isset($_GET['id']) && isset($_GET['status'])) {
    $booking_id = $_GET['id'];
    $new_status = $_GET['status'];

    // Pastikan status yang diberikan valid
    $allowed_statuses = ['used', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        header("Location: booking_management.php?status=error&message=Invalid_status");
        exit();
    }

    $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = ? WHERE booking_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $booking_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: booking_management.php?status=success");
    } else {
        header("Location: booking_management.php?status=error&message=Gagal_mengupdate");
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    header("Location: booking_management.php");
    exit();
}
?>