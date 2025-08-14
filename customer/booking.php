<?php
session_start();
$customer_name_prefill = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi & Denah Meja - RestoKU</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        h1, h2, h3 { font-family: 'Lora', serif; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.8); }

        .floor-plan-container { padding: 20px; background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; }
        .zone { margin-bottom: 25px; }
        .zone-title { font-size: 1.1rem; font-weight: bold; color: #cbd5e1; margin-bottom: 15px; border-bottom: 2px solid #334155; padding-bottom: 8px; }
        .table-group { display: flex; flex-wrap: wrap; gap: 15px; }

        .table-visual { display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; border-radius: 8px; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .table-visual.available { background-color: #166534; border: 2px solid #22c55e; }
        .table-visual.booked { background-color: #991b1b; border: 2px solid #ef4444; cursor: not-allowed; opacity: 0.7; }
        .table-visual.selected { transform: scale(1.1); box-shadow: 0 0 15px #f59e0b; border-color: #f59e0b; }
        
        /* Ukuran Meja Baru */
        .table-2 { width: 60px; height: 60px; }
        .table-4 { width: 65px; height: 65px; }
        .table-6 { width: 100px; height: 65px; }
        .table-12 { width: 160px; height: 65px; }
    </style>
</head>
<body class="text-slate-300">

<header class="bg-slate-900 shadow-lg"><nav class="container mx-auto flex justify-between items-center p-4"><a href="../index.php" class="text-2xl font-bold text-white tracking-wider">RestoKU</a></nav></header>

<main>
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">

                <div class="lg:col-span-3">
                    <div class="bg-slate-900 p-6 rounded-xl border border-slate-700">
                        <h2 class="text-3xl font-bold text-white mb-4">Pilih Meja dari Denah</h2>
                        <div class="flex items-center gap-4 mb-6">
                            <label for="check_date" class="font-semibold">Pilih Tanggal:</label>
                            <input type="date" id="check_date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white">
                        </div>
                        
                        <div class="floor-plan-container">
                            <div id="zone-12" class="zone"><h3 class="zone-title">Area VIP (12 Kursi)</h3><div class="table-group"></div></div>
                            <div id="zone-6" class="zone"><h3 class="zone-title">Area Sofa (6 Kursi)</h3><div class="table-group"></div></div>
                            <div id="zone-4" class="zone"><h3 class="zone-title">Area Reguler (4 Kursi)</h3><div class="table-group"></div></div>
                            <div id="zone-2" class="zone"><h3 class="zone-title">Area Pasangan (2 Kursi)</h3><div class="table-group"></div></div>
                            <div id="loading-indicator" class="text-slate-400 text-center hidden">Memuat denah...</div>
                        </div>

                        <div class="flex justify-end gap-4 mt-4 text-sm">
                            <span class="flex items-center gap-2"><div class="w-4 h-4 bg-green-500 rounded-full"></div>Tersedia</span>
                            <span class="flex items-center gap-2"><div class="w-4 h-4 bg-red-500 rounded-full"></div>Dipesan</span>
                            <span class="flex items-center gap-2"><div class="w-4 h-4 bg-amber-500 rounded-full"></div>Pilihan Anda</span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                     <div class="bg-slate-800 p-8 rounded-xl border border-slate-700 sticky top-8">
                        <h2 class="text-3xl font-bold text-white mb-6">Detail Reservasi</h2>
                        <form id="booking-form" action="process_booking.php" method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Meja Dipilih</label>
                                <input type="text" id="selected_table_display" readonly placeholder="Pilih meja dari denah" class="block w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-lg text-amber-400 font-bold">
                                <input type="hidden" id="selected_table_id" name="table_id">
                                <input type="hidden" id="booking_date_hidden" name="booking_date">
                                <input type="hidden" id="number_of_people_hidden" name="number_of_people">
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-300 mb-2">Nama Lengkap</label>
                                <input type="text" id="name" name="customer_name" required value="<?php echo htmlspecialchars($customer_name_prefill); ?>" class="block w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-300 mb-2">Nomor Telepon</label>
                                <input type="tel" id="phone" name="customer_phone" required class="block w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white">
                            </div>
                            <div>
                                <label for="time" class="block text-sm font-medium text-slate-300 mb-2">Waktu</label>
                                <input type="time" id="time" name="booking_time" required class="block w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg text-white">
                            </div>
                            <div>
                                <button type="submit" id="submit-button" disabled class="w-full bg-amber-700 text-slate-400 font-bold py-3 px-6 rounded-lg transition cursor-not-allowed">
                                    Pilih Meja Terlebih Dahulu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const datePicker = document.getElementById('check_date');
    const form = {
        display: document.getElementById('selected_table_display'),
        tableId: document.getElementById('selected_table_id'),
        bookingDate: document.getElementById('booking_date_hidden'),
        peopleHidden: document.getElementById('number_of_people_hidden'),
        submitButton: document.getElementById('submit-button')
    };
    const zones = {
        '12': document.querySelector('#zone-12 .table-group'),
        '6': document.querySelector('#zone-6 .table-group'),
        '4': document.querySelector('#zone-4 .table-group'),
        '2': document.querySelector('#zone-2 .table-group')
    };
    const loadingIndicator = document.getElementById('loading-indicator');

    async function fetchAndDrawFloorPlan() {
        loadingIndicator.classList.remove('hidden');
        form.submitButton.disabled = true;
        form.submitButton.textContent = 'Pilih Meja Terlebih Dahulu';
        form.submitButton.classList.add('bg-amber-700', 'text-slate-400', 'cursor-not-allowed');
        form.submitButton.classList.remove('bg-amber-500', 'text-slate-900');

        Object.values(zones).forEach(zone => zone.innerHTML = '');
        
        try {
            const response = await fetch(`get_table_status.php?date=${datePicker.value}`);
            const tables = await response.json();

            if (tables.error) throw new Error(tables.error);

            tables.forEach(table => {
                const tableEl = document.createElement('div');
                tableEl.className = `table-visual ${table.status}`;
                tableEl.classList.add(table.table_type.replace('_', '-'));
                tableEl.dataset.tableId = table.table_id;
                tableEl.textContent = table.table_id;

                if (table.status === 'available') {
                    tableEl.addEventListener('click', () => {
                        document.querySelectorAll('.table-visual.selected').forEach(el => el.classList.remove('selected'));
                        tableEl.classList.add('selected');
                        
                        form.display.value = `${table.table_id} (Maks: ${table.capacity} orang)`;
                        form.tableId.value = table.table_id;
                        form.bookingDate.value = datePicker.value;
                        form.peopleHidden.value = table.capacity; // Isi kapasitas ke input tersembunyi

                        form.submitButton.disabled = false;
                        form.submitButton.textContent = 'Kirim Reservasi';
                        form.submitButton.classList.remove('bg-amber-700', 'text-slate-400', 'cursor-not-allowed');
                        form.submitButton.classList.add('bg-amber-500', 'text-slate-900');
                    });
                }
                
                const zoneKey = table.table_type.split('_')[1];
                if (zones[zoneKey]) {
                    zones[zoneKey].appendChild(tableEl);
                }
            });

        } catch (error) {
            console.error('Error fetching floor plan:', error);
            // Tampilkan error di salah satu zona
            zones['4'].innerHTML = '<p class="text-red-500 w-full text-center">Gagal memuat denah.</p>';
        } finally {
            loadingIndicator.classList.add('hidden');
        }
    }

    datePicker.addEventListener('change', fetchAndDrawFloorPlan);
    fetchAndDrawFloorPlan();
});
</script>

</body>
</html>