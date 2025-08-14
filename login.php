<?php
session_start();
include 'includes/db_connect.php'; // Pastikan path ini benar

$error_message = '';

// --- CATATAN PENTING ---
// Kode di bawah ini membandingkan password sebagai teks biasa (plaintext).
// Ini SANGAT TIDAK AMAN untuk aplikasi produksi.
// Sebaiknya gunakan password_hash() saat registrasi dan password_verify() saat login.
// Contoh: if (password_verify($password, $user['password_hash'])) { ... }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Gunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        // Membandingkan password (contoh tidak aman)
        if ($password == $user['password']) {
            // Regenerate session ID untuk mencegah session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Username atau password yang Anda masukkan salah.";
        }
    } else {
        $error_message = "Username atau password yang Anda masukkan salah.";
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RestoKU Modern</title>
    <link href="./assets/css/output.css" rel="stylesheet">
    <style>
        /* Anda bisa menambahkan font kustom di sini jika perlu */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <main class="w-full max-w-md mx-auto p-6 md:p-8">
        <div class="bg-white p-8 rounded-2xl shadow-xl">
            <div class="text-center mb-8">
                <div class="inline-block bg-blue-100 text-blue-600 p-3 rounded-full mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-slate-800">Selamat Datang</h1>
                <p class="text-slate-500 mt-2">Silakan masuk untuk melanjutkan ke RestoKU</p>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-600 mb-2">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username Anda"
                           class="mt-1 block w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required placeholder="Masukkan password Anda"
                               class="mt-1 block w-full px-4 py-3 pr-12 bg-slate-50 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <button type="button" onclick="togglePasswordVisibility()"
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-500 hover:text-slate-700">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.943-9.543-7a10.056 10.056 0 012.501-4.144M6.219 6.219A9.96 9.96 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.242 5.004M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                    <p class="font-medium">Gagal Login</p>
                    <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
                <?php endif; ?>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white 
                                   bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                   transform hover:scale-105 transition-transform duration-200">
                        Login
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }
    </script>
</body>
</html>