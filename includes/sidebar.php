<?php
// File ini mengasumsikan variabel $user_role, $username, dan $current_page sudah didefinisikan
// di halaman yang memanggilnya.

// Definisikan semua kemungkinan link navigasi
$all_links = [
    'admin' => [
        ['href' => 'admin/menu_management.php', 'page' => 'menu_management', 'label' => 'Manajemen Menu', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>'],
        ['href' => 'admin/user_management.php', 'page' => 'user_management', 'label' => 'Manajemen Pengguna', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm-3 2a5 5 0 00-5 5v1a1 1 0 001 1h8a1 1 0 001-1v-1a5 5 0 00-5-5zM17 6a3 3 0 11-6 0 3 3 0 016 0zm-3 2a5 5 0 00-4.545 3.372A3.999 3.999 0 0115 11a4 4 0 014 4v1a1 1 0 01-1 1h-2a1 1 0 01-1-1v-1a2 2 0 00-2-2z" /></svg>'],
        ['href' => 'admin/reports.php', 'page' => 'reports', 'label' => 'Laporan Penjualan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>'],
    ],
    'kasir' => [
        ['href' => 'kasir/payment_processing.php', 'page' => 'payment_processing', 'label' => 'Proses Pembayaran', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" /><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>'],
        ['href' => 'admin/reports.php', 'page' => 'reports', 'label' => 'Laporan Penjualan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" /></svg>'],
    ],
    'pelayan' => [
        ['href' => 'pelayan/booking_management.php', 'page' => 'booking_management', 'label' => 'Manajemen Booking', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>'],
        ['href' => 'pelayan/order_taking.php', 'page' => 'order_taking', 'label' => 'Input Pesanan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /><path d="M6 3a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2V3zm5 9a1 1 0 11-2 0 1 1 0 012 0z" /><path d="M7 13a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" /></svg>'],
        ['href' => 'pelayan/order_status_view.php', 'page' => 'order_status_view', 'label' => 'Status Pesanan', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C3.732 5.943 7.523 3 12 3c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7zM12 15a5 5 0 100-10 5 5 0 000 10z" clip-rule="evenodd" /></svg>'],
    ],
    'chef' => [
        ['href' => 'chef/order_processing.php', 'page' => 'order_processing', 'label' => 'Kelola Pesanan Dapur', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>']
    ]
];

// Tentukan path relatif berdasarkan lokasi file yang memanggil
$path_prefix = (basename(dirname($_SERVER['SCRIPT_NAME'])) == 'RestoKU') ? '' : '../';
$navigation_links = $all_links[$user_role] ?? [];
?>
<aside class="w-64 flex-shrink-0 flex flex-col p-4 text-white">
    <a href="<?php echo $path_prefix; ?>dashboard.php" class="flex items-center gap-3 px-2 mb-8">
        <div class="bg-orange-500 p-2 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></div>
        <span class="text-xl font-bold">RestoKU</span>
    </a>
    <nav class="flex-1 space-y-2">
        <a href="<?php echo $path_prefix; ?>dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?php echo ($current_page == 'dashboard') ? 'bg-slate-700 font-semibold' : 'hover:bg-slate-700'; ?> transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
            Dashboard
        </a>
        
        <?php if (!empty($navigation_links)): ?>
        <div class="border-t border-slate-700 my-4"></div>
        <h3 class="px-4 mt-4 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Aplikasi</h3>
        <?php foreach ($navigation_links as $link): ?>
            <a href="<?php echo $path_prefix . $link['href']; ?>" class="flex items-center gap-3 px-4 py-2.5 rounded-lg <?php echo ($current_page == $link['page']) ? 'bg-slate-700 font-semibold' : 'hover:bg-slate-700'; ?> transition-colors">
                <?php echo $link['icon']; ?>
                <?php echo htmlspecialchars($link['label']); ?>
            </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>
    <div class="mt-auto border-t border-slate-700 pt-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center font-bold"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div>
                <p class="font-semibold"><?php echo htmlspecialchars($username); ?></p>
                <p class="text-sm text-slate-400"><?php echo ucfirst($user_role); ?></p>
            </div>
        </div>
        <a href="<?php echo $path_prefix; ?>logout.php" class="flex items-center justify-center gap-2 w-full mt-4 px-4 py-2 bg-slate-700 hover:bg-red-600 rounded-lg transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" /></svg>Logout</a>
    </div>
</aside>