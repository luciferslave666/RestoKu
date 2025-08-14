<?php
include 'includes/db_connect.php';

// Menggunakan prepared statement untuk konsistensi dan keamanan
$menus = [];
$sql_menus = "SELECT menu_id, name, description, price, status, category, image_url FROM menu WHERE status = 'ada' ORDER BY category, name ASC";
$stmt = mysqli_prepare($conn, $sql_menus);
mysqli_stmt_execute($stmt);
$result_menus = mysqli_stmt_get_result($stmt);

if ($result_menus) {
    while ($row = mysqli_fetch_assoc($result_menus)) {
        $menus[] = $row;
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestoKU Soto Betawi - Kelezatan Tradisional Otentik</title>
    <link href="./assets/css/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Lora', serif; }
        .hero-section {
            background-image: url('https://images.unsplash.com/photo-1552566626-52f8b828add9?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
        .slick-slide {
            padding: 0 1rem; /* Jarak antar kartu menu */
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-300">

<header id="header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <nav class="container mx-auto flex justify-between items-center p-4">
        <a href="index.php" class="text-2xl font-bold text-white tracking-wider">RestoKU</a>
        <div class="hidden md:flex items-center space-x-8 text-lg">
            <a href="#beranda" class="text-white hover:text-amber-400 transition">Beranda</a>
            <a href="#tentang-kami" class="text-white hover:text-amber-400 transition">Tentang Kami</a>
            <a href="#menu" class="text-white hover:text-amber-400 transition">Menu</a>
            <a href="#kontak" class="text-white hover:text-amber-400 transition">Kontak</a>
        </div>
        <a href="customer/booking.php" class="hidden md:inline-block bg-amber-500 text-slate-900 font-bold py-2 px-6 rounded-full hover:bg-amber-400 transition transform hover:scale-105">Reservasi</a>
        <button id="menu-btn" class="md:hidden text-white z-50">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
    </nav>
    <div id="mobile-menu" class="hidden md:hidden bg-slate-800 bg-opacity-95 backdrop-blur-sm">
        <a href="#beranda" class="block py-3 px-6 text-lg text-white hover:bg-slate-700">Beranda</a>
        <a href="#tentang-kami" class="block py-3 px-6 text-lg text-white hover:bg-slate-700">Tentang Kami</a>
        <a href="#menu" class="block py-3 px-6 text-lg text-white hover:bg-slate-700">Menu</a>
        <a href="#kontak" class="block py-3 px-6 text-lg text-white hover:bg-slate-700">Kontak</a>
        <a href="customer/booking.php" class="block py-3 px-6 text-lg text-amber-400 font-bold hover:bg-slate-700">Reservasi</a>
    </div>
</header>

<main>
    <section id="beranda" class="hero-section h-screen flex items-center justify-center text-white">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="relative z-10 text-center p-4">
            <h1 class="text-5xl md:text-7xl font-bold leading-tight mb-4">Soto Betawi Otentik, <br>Rasa yang Merentang Generasi</h1>
            <p class="text-xl md:text-2xl text-slate-200 max-w-3xl mx-auto">Nikmati kekayaan rempah asli dalam semangkuk soto hangat yang disajikan dengan penuh cinta.</p>
            <a href="#menu" class="mt-8 inline-block bg-amber-500 text-slate-900 font-bold py-3 px-8 rounded-full text-lg shadow-lg transform hover:scale-105 transition">Lihat Menu</a>
        </div>
    </section>

    <section id="tentang-kami" class="py-20 md:py-32 bg-slate-800">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center px-4">
            <div class="order-2 md:order-1">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">Cerita di Balik Semangkuk Soto</h2>
                <p class="text-lg text-slate-400 leading-relaxed mb-4">RestoKU lahir dari kecintaan pada warisan kuliner Betawi. Kami berkomitmen menyajikan Soto Betawi dengan resep asli yang diwariskan turun-temurun, menggunakan daging pilihan dan santan segar setiap hari.</p>
                <p class="text-lg text-slate-400 leading-relaxed">Suasana hangat dan konsep lesehan kami hadirkan untuk menciptakan momen kebersamaan yang tak terlupakan bagi Anda dan keluarga.</p>
            </div>
            <div class="order-1 md:order-2">
                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=2070&auto=format&fit=crop" alt="Suasana Restoran" class="rounded-xl shadow-2xl w-full h-auto object-cover">
            </div>
        </div>
    </section>

    <section id="keunggulan" class="py-20 md:py-24 bg-slate-900">
        <div class="container mx-auto text-center px-4">
            <h2 class="text-4xl font-bold text-white mb-4">Mengapa Memilih Kami?</h2>
            <p class="text-lg text-slate-400 mb-12 max-w-2xl mx-auto">Kami tidak hanya menyajikan makanan, kami menawarkan sebuah pengalaman.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">
                <div class="bg-slate-800 p-8 rounded-xl"><div class="text-amber-400 mb-4"><svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.657 7.343A8 8 0 0117.657 18.657z" /></svg></div><h3 class="text-2xl font-bold text-white mb-2">Resep Asli</h3><p class="text-slate-400">Rasa otentik dari resep warisan yang dijaga kemurniannya.</p></div>
                <div class="bg-slate-800 p-8 rounded-xl"><div class="text-amber-400 mb-4"><svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg></div><h3 class="text-2xl font-bold text-white mb-2">Bahan Segar</h3><p class="text-slate-400">Daging dan rempah pilihan terbaik dari pemasok lokal terpercaya.</p></div>
                <div class="bg-slate-800 p-8 rounded-xl"><div class="text-amber-400 mb-4"><svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div><h3 class="text-2xl font-bold text-white mb-2">Suasana Nyaman</h3><p class="text-slate-400">Tempat yang ideal untuk berkumpul bersama keluarga dan teman.</p></div>
            </div>
        </div>
    </section>

    <section id="menu" class="py-20 md:py-32 bg-slate-800">
        <div class="container mx-auto text-center px-4">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-12">Hidangan Andalan Kami</h2>
             <?php if (count($menus) > 0): ?>
                <div class="menu-slider">
                    <?php foreach ($menus as $menu): ?>
                        <div class="bg-slate-900 rounded-xl overflow-hidden text-left">
                            <img src="<?php echo !empty($menu['image_url']) ? htmlspecialchars($menu['image_url']) : 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?q=80&w=1899&auto=format&fit=crop'; ?>" alt="<?php echo htmlspecialchars($menu['name']); ?>" class="w-full h-56 object-cover">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($menu['name']); ?></h3>
                                <p class="text-slate-400 mb-4 h-20 overflow-hidden"><?php echo htmlspecialchars($menu['description']); ?></p>
                                <p class="text-xl font-bold text-amber-400">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center py-8 text-slate-400">Belum ada menu tersedia.</p>
            <?php endif; ?>
        </div>
    </section>

    <section id="kontak" class="py-20 md:py-32 bg-slate-900">
        <div class="container mx-auto text-center px-4">
             <h2 class="text-4xl md:text-5xl font-bold text-white mb-12">Kunjungi & Hubungi Kami</h2>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-12 text-left">
                <div class="bg-slate-800 p-8 rounded-xl">
                    <h3 class="text-3xl font-bold text-white mb-4">Informasi Kontak</h3>
                    <div class="space-y-4 text-lg">
                        <p><strong class="text-amber-400">Alamat:</strong><br>Tamansari RT02/RW15, Kec. Tamansari, Kabupaten Bogor, Jawa Barat 16610</p>
                        <p><strong class="text-amber-400">WhatsApp:</strong><br>0811-9657-333</p>
                        <p><strong class="text-amber-400">Jam Buka:</strong><br>Senin - Jumat: 09:00 - 17:00<br>Sabtu - Minggu: 09:00 - 19:00</p>
                    </div>
                </div>
                <div>
                     <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3962.880925298516!2d106.77583627471208!3d-6.66128646513523!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69cfa3815f5e6d%3A0x6001511b88ab305e!2sSOTO%20BETAWI%20%26%20SOP%20%22IBU%20YANTI%22!5e0!3m2!1sen!2sid!4v1721998991206!5m2!1sen!2sid" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="rounded-xl"></iframe>
                </div>
             </div>
        </div>
    </section>
</main>

<footer class="bg-slate-900 border-t border-slate-800 py-8">
    <div class="container mx-auto text-center text-slate-400">
        <p>© <?php echo date("Y"); ?> RestoKU Soto Betawi. All Rights Reserved.</p>
        <div class="flex justify-center space-x-6 mt-4">
            <a href="#" class="hover:text-amber-400 transition">Facebook</a>
            <a href="#" class="hover:text-amber-400 transition">Instagram</a>
            <a href="#" class="hover:text-amber-400 transition">WhatsApp</a>
        </div>
        <a href="login.php" class="text-xs text-slate-500 hover:text-white transition mt-6 inline-block">Login Staf</a>
    </div>
</footer>

<script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<script>
$(document).ready(function(){
    // Inisialisasi Slick Carousel
    $('.menu-slider').slick({
        dots: true,
        infinite: false,
        speed: 300,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 640,
                settings: {
                    slidesToShow: 1,
                }
            }
        ]
    });

    // Toggle Mobile Menu
    $('#menu-btn').click(function(){
        $('#mobile-menu').slideToggle();
    });

    // Header menjadi solid saat di-scroll
    $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
            $('#header').addClass('bg-slate-900 shadow-lg').removeClass('bg-transparent');
        } else {
            $('#header').removeClass('bg-slate-900 shadow-lg').addClass('bg-transparent');
        }
    });
});
</script>
</body>
</html>