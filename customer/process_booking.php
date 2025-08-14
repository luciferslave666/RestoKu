<?php
session_start();
include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form (sekarang ada table_id)
    $table_id = $_POST['table_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $booking_time = $_POST['booking_time'] ?? '';
    $number_of_people = (int)($_POST['number_of_people'] ?? 0);

    // Validasi penting
    if (empty($table_id) || empty($booking_date) || empty($customer_name) || empty($booking_time) || $number_of_people <= 0) {
        // Redirect dengan pesan error
        header("Location: booking.php?status=error&message=" . urlencode("Semua data harus diisi dengan benar."));
        exit;
    }

    // CEK ULANG untuk menghindari double booking (race condition)
    $sql_check = "SELECT booking_id FROM bookings WHERE table_id = ? AND booking_date = ? AND status != 'cancelled'";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "ss", $table_id, $booking_date);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // Meja ternyata sudah dibooking orang lain
        header("Location: booking.php?status=error&message=" . urlencode("Maaf, meja $table_id sudah dipesan orang lain. Silakan pilih meja lain."));
        exit;
    }
    mysqli_stmt_close($stmt_check);


    // Jika aman, lakukan INSERT
    $sql_insert = "INSERT INTO bookings (customer_name, customer_phone, booking_date, booking_time, number_of_people, table_id, status) 
                   VALUES (?, ?, ?, ?, ?, ?, 'confirmed')";
    
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    mysqli_stmt_bind_param($stmt_insert, "ssssis", $customer_name, $customer_phone, $booking_date, $booking_time, $number_of_people, $table_id);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        header("Location: booking.php?status=success&message=" . urlencode("Reservasi untuk meja $table_id berhasil!"));
    } else {
        header("Location: booking.php?status=error&message=" . urlencode("Terjadi kesalahan teknis."));
    }
    
    mysqli_stmt_close($stmt_insert);
    mysqli_close($conn);
    exit;
}
?>