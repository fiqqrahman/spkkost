<!DOCTYPE html>
<?php
$myKosts          = $myKosts ?? [];
$incomingBookings = $incomingBookings ?? [];
?>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manajemen Pemilik Kost</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        * {
            font-family: 'Lexend', sans-serif !important;
        }

        .custom-campus-marker,
        .custom-kost-marker {
            background: none;
            border: none;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col antialiased">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
                    P
                </div>
                <div>
                    <span class="font-bold text-slate-900 tracking-tight text-base block leading-none">Portal Pemilik Kost</span>
                    <span class="text-[10px] text-slate-500 font-medium tracking-wider">Dashboard Mitra</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-700 font-semibold"><?= esc(session()->get('username')) ?></span>
                    <span class="text-slate-400 font-normal">(<?= esc(session()->get('email')) ?>)</span>
                </div>
                <a href="<?= base_url('/') ?>" class="text-xs font-semibold text-slate-700 hover:text-indigo-600 bg-slate-100 hover:bg-slate-200/70 border border-slate-200 px-3.5 py-2 rounded-lg transition">
                    Beranda
                </a>
                <a href="<?= base_url('/logout') ?>" class="text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-3.5 py-2 rounded-lg transition">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm p-4 rounded-xl font-medium flex items-center gap-2">
                <span>✓</span> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm p-4 rounded-xl font-medium flex items-center gap-2">
                <span>⚠</span> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <section class="relative bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-2xl p-8 sm:p-10 shadow-sm overflow-hidden border border-indigo-950">
            <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-indigo-500/10 pointer-events-none transform skew-x-12"></div>
            <div class="relative z-10 max-w-2xl space-y-3">
                <span class="inline-flex items-center gap-1.5 bg-indigo-500/20 text-indigo-200 border border-indigo-400/30 text-xs font-semibold px-3 py-1 rounded-full">
                    Property Owner Center
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Dashboard Pemilik Kost
                </h1>
                <p class="text-indigo-100/90 text-sm sm:text-base leading-relaxed font-normal">
                    Registrasikan properti, tinjau berkas pemesanan dari penyewa, serta verifikasi bukti transfer transaksi.
                </p>
            </div>
        </section>

        <!-- Form Tambah Kost & Map -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <section class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4 flex flex-col justify-between h-full">
                <div>
                    <div class="border-b border-slate-100 pb-3 mb-4">
                        <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            Form Pendaftaran Kost
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Isi rincian informasi dan spesifikasi properti</p>
                    </div>

                    <form id="saveForm" method="POST" action="<?= base_url('/owner/save') ?>" enctype="multipart/form-data" class="space-y-4">
                        <?= csrf_field() ?>
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Nama Kost</label>
                            <input type="text" id="name" name="name" required placeholder="Contoh: Kost Kahayan Permai"
                                class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                        </div>

                        <div>
                            <label for="price" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Harga Sewa Bulanan (Rp)</label>
                            <input type="number" id="price" name="price" required placeholder="Contoh: 850000" min="0"
                                class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Fasilitas Properti</label>
                            <div class="space-y-2 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs">
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="features[]" value="1" class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span>Parkir Motor Berkanopi</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="features[]" value="2" class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span>Lahan Parkir Mobil Luas</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="features[]" value="3" class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span>Pagar Pengaman</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="features[]" value="4" class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span>Kamera CCTV Aktif 24 Jam</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                                    <input type="checkbox" name="features[]" value="5" class="rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span>Satpam / Penjaga Kost</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="images" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Upload Foto Kost (Maks 5 Foto)</label>
                            <input type="file" name="images[]" id="images" accept="image/*" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700 text-xs focus:outline-none transition" multiple required>
                        </div>

                        <input type="hidden" id="latitude" name="latitude" required>
                        <input type="hidden" id="longitude" name="longitude" required>
                    </form>
                </div>

                <button type="submit" form="saveForm" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition shadow-xs cursor-pointer mt-4">
                    Simpan & Daftarkan Kost
                </button>
            </section>

            <section class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between h-full space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        Titik Koordinat Alamat Kost
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Cari lokasi properti di peta, lalu <b>Klik Kiri</b> tepat di atas bangunan untuk mengunci koordinat.</p>
                </div>

                <div id="map-picker" class="flex-1 min-h-[320px] w-full rounded-xl border border-slate-200 shadow-inner z-10"></div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <span class="text-slate-500 block text-[10px] uppercase font-semibold mb-0.5">Latitude Terkunci</span>
                        <span id="display-lat" class="text-slate-800 font-bold">Belum Dipilih</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <span class="text-slate-500 block text-[10px] uppercase font-semibold mb-0.5">Longitude Terkunci</span>
                        <span id="display-lng" class="text-slate-800 font-bold">Belum Dipilih</span>
                    </div>
                </div>
            </section>
        </div>

        <!-- Tabel Kost Milik Pemilik -->
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Properti Kost yang Telah Didaftarkan</h2>
                    <p class="text-xs text-slate-500">Kelola status ketersediaan dan informasi kost</p>
                </div>
                <span class="text-xs font-semibold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-600">
                    Total: <?= count($myKosts) ?> Properti
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[720px]">
                    <thead>
                        <tr class="bg-slate-100/70 text-slate-600 uppercase text-[11px] tracking-wider font-semibold border-b border-slate-200">
                            <th class="px-6 py-3.5">Nama Properti & Fasilitas</th>
                            <th class="px-6 py-3.5 text-right w-44">Harga Sewa / Bulan</th>
                            <th class="px-6 py-3.5 text-center w-40">Status Hunian</th>
                            <th class="px-6 py-3.5 text-center w-64">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        <?php if (!empty($myKosts)): ?>
                            <?php foreach ($myKosts as $kost): ?>
                                <tr class="hover:bg-slate-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 text-base mb-1.5"><?= esc($kost['name']) ?></div>
                                        <div class="flex flex-wrap gap-1">
                                            <?php if (!empty($kost['feature_names'])): ?>
                                                <?php foreach ($kost['feature_names'] as $featureName): ?>
                                                    <span class="inline-flex items-center bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] font-medium px-2 py-0.5 rounded">
                                                        <?= esc($featureName) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[11px] italic">Belum ada fasilitas dipilih</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-slate-900">
                                        Rp <?= number_format($kost['price'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <?php if (isset($kost['is_full']) && $kost['is_full'] == 1): ?>
                                            <span class="bg-rose-100 text-rose-700 border border-rose-200 px-2.5 py-0.5 rounded text-[10px] font-semibold">Sudah Penuh</span>
                                        <?php else: ?>
                                            <span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2.5 py-0.5 rounded text-[10px] font-semibold">Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap space-x-1.5">
                                        <button type="button"
                                            onclick='openEditModal(<?= json_encode($kost) ?>)'
                                            class="inline-block text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition shadow-xs cursor-pointer">
                                            Edit
                                        </button>
                                        <a href="<?= base_url('/owner/toggle-status/' . $kost['id']) ?>"
                                            class="inline-block text-xs font-semibold px-3 py-1.5 rounded-lg transition shadow-xs <?= (isset($kost['is_full']) && $kost['is_full'] == 1) ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-500 hover:bg-amber-600 text-white' ?>">
                                            <?= (isset($kost['is_full']) && $kost['is_full'] == 1) ? 'Buka Kamar' : 'Set Jadi Penuh' ?>
                                        </a>
                                        <a href="<?= base_url('/owner/delete/' . $kost['id']) ?>"
                                            onclick="return confirm('Tindakan ini akan menghapus properti kost secara permanen. Apakah antum yakin ingin melanjutkan?');"
                                            class="inline-block text-xs font-semibold px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition shadow-xs">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">
                                    Belum ada data properti yang didaftarkan. Silakan isi form di atas.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SEKSI BARU: Kelola Pengajuan Sewa & Pembayaran Masuk -->
        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Pengajuan Sewa & Verifikasi Pembayaran</h2>
                    <p class="text-xs text-slate-500">Tinjau calon penyewa dan konfirmasi transaksi masuk</p>
                </div>
                <span class="text-xs font-semibold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-600">
                    Total: <?= count($incomingBookings) ?> Pengajuan
                </span>
            </div>

            <div class="p-6 space-y-6">
                <?php if (!empty($incomingBookings)): ?>
                    <?php foreach ($incomingBookings as $b): ?>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900"><?= esc($b['tenant_name']) ?> <span class="text-xs font-normal text-slate-500">(<?= esc($b['tenant_email']) ?>)</span></h3>
                                    <p class="text-xs text-slate-500">
                                        Mengajukan sewa: <b class="text-slate-800"><?= esc($b['kost_name']) ?></b> |
                                        Instansi: <span class="text-slate-700"><?= esc($b['campus_name']) ?></span> (<?= $b['occupant_count'] ?> Orang)
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="<?= base_url('uploads/identities/' . $b['identity_doc']) ?>" target="_blank" class="text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
                                        Lihat KTP/KTM
                                    </a>

                                    <?php if ($b['status'] === 'pending'): ?>
                                        <a href="<?= base_url('/owner/booking/handle/' . $b['id'] . '/approve') ?>" class="text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded-lg transition">
                                            Setujui
                                        </a>
                                        <button type="button" onclick="openRejectModal(<?= $b['id'] ?>)" class="text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 px-3 py-1.5 rounded-lg transition cursor-pointer">
                                            Tolak
                                        </button>
                                    <?php elseif ($b['status'] === 'approved'): ?>
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 rounded-full text-xs font-bold">Disetujui</span>
                                    <?php else: ?>
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 px-3 py-1 rounded-full text-xs font-bold">Ditolak</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Bukti Pembayaran Penyewa -->
                            <div class="pt-1">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Bukti Bayar Masuk:</h4>
                                <?php if (!empty($b['payments'])): ?>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs bg-white border border-slate-200 rounded-lg overflow-hidden">
                                            <thead class="bg-slate-100 text-slate-600 font-semibold border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-2">Tanggal Upload</th>
                                                    <th class="px-4 py-2 text-right">Nominal</th>
                                                    <th class="px-4 py-2 text-center">Struk Transfer</th>
                                                    <th class="px-4 py-2 text-center">Status Transaksi</th>
                                                    <th class="px-4 py-2 text-center">Aksi Verifikasi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <?php foreach ($b['payments'] as $p): ?>
                                                    <tr>
                                                        <td class="px-4 py-2 text-slate-600"><?= esc($p['payment_date']) ?></td>
                                                        <td class="px-4 py-2 text-right font-bold text-slate-800">Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                                                        <td class="px-4 py-2 text-center">
                                                            <a href="<?= base_url('uploads/payments/' . $p['proof_image']) ?>" target="_blank" class="text-indigo-600 underline font-semibold">
                                                                Cek Resi
                                                            </a>
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <?php if ($p['status'] === 'pending'): ?>
                                                                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-bold">Perlu Verifikasi</span>
                                                            <?php elseif ($p['status'] === 'verified'): ?>
                                                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-[10px] font-bold">Verified / Sah</span>
                                                            <?php else: ?>
                                                                <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded text-[10px] font-bold">Ditolak</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-4 py-2 text-center space-x-1">
                                                            <?php if ($p['status'] === 'pending'): ?>
                                                                <a href="<?= base_url('/owner/payment/handle/' . $p['id'] . '/verify') ?>" class="bg-emerald-600 text-white px-2.5 py-1 rounded text-[10px] font-semibold hover:bg-emerald-700 transition">Sahkan</a>
                                                                <a href="<?= base_url('/owner/payment/handle/' . $p['id'] . '/reject') ?>" class="bg-rose-600 text-white px-2.5 py-1 rounded text-[10px] font-semibold hover:bg-rose-700 transition">Tolak</a>
                                                            <?php else: ?>
                                                                <span class="text-slate-400 font-italic text-[10px]">Selesai</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-xs text-slate-400 italic">Belum ada struk pembayaran yang dikirimkan oleh penyewa ini.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-slate-400 italic">
                        Belum ada pengajuan sewa masuk dari penyewa.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Modal Edit Kost -->
    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-2xl w-full p-6 shadow-xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        Edit Informasi Properti Kost
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Perbarui rincian, fasilitas, dan titik koordinat kost antum</p>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold px-2 py-1 cursor-pointer">&times;</button>
            </div>

            <form id="editForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Nama Kost</label>
                        <input type="text" id="edit_name" name="name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                    </div>
                    <div>
                        <label for="edit_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Harga Sewa Bulanan (Rp)</label>
                        <input type="number" id="edit_price" name="price" required min="0" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Fasilitas Properti</label>
                    <div class="space-y-2 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                            <input type="checkbox" name="features[]" value="1" class="edit-feature-cb rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            <span>Parkir Motor Berkanopi</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                            <input type="checkbox" name="features[]" value="2" class="edit-feature-cb rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            <span>Lahan Parkir Mobil Luas</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                            <input type="checkbox" name="features[]" value="3" class="edit-feature-cb rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            <span>Pagar Pengaman</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                            <input type="checkbox" name="features[]" value="4" class="edit-feature-cb rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            <span>Kamera CCTV Aktif 24 Jam</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                            <input type="checkbox" name="features[]" value="5" class="edit-feature-cb rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            <span>Satpam / Penjaga Kost</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="edit_images" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Ganti Foto Kost (Opsional - Maks 5 Foto)</label>
                    <input type="file" name="images[]" id="edit_images" accept="image/*" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700 text-xs focus:outline-none transition" multiple>
                    <p class="text-[10px] text-slate-400 mt-1">* Biarkan kosong jika tidak ingin mengganti foto yang sudah ada.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Ubah Titik Lokasi Peta</label>
                    <div id="map-picker-edit" class="h-52 rounded-xl border border-slate-200 shadow-inner z-10"></div>
                </div>

                <input type="hidden" id="edit_latitude" name="latitude" required>
                <input type="hidden" id="edit_longitude" name="longitude" required>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 rounded-lg transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Penolakan Booking -->
    <div id="rejectModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Tolak Pengajuan Sewa</h2>
                <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold px-2 py-1 cursor-pointer">&times;</button>
            </div>

            <form id="rejectForm" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label for="rejection_note" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Alasan Penolakan</label>
                    <textarea id="rejection_note" name="rejection_note" required rows="3" placeholder="Contoh: Kamar sudah terisi offline atau identitas kurang jelas."
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg p-3 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 rounded-lg transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition shadow-xs cursor-pointer">
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-white border-t border-slate-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            &copy; <?= date('Y') ?> SPK Rekomendasi Kost Palangka Raya. All rights reserved.
        </div>
    </footer>

    <script>
        const initialLat = -2.22623400;
        const initialLng = 113.92423100;
        const map = L.map('map-picker').setView([initialLat, initialLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        setTimeout(() => {
            map.invalidateSize();
        }, 300);

        let currentMarker = null;
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            if (currentMarker) {
                currentMarker.setLatLng(e.latlng);
            } else {
                currentMarker = L.marker(e.latlng).addTo(map);
                currentMarker.bindPopup('<b class="text-slate-900 text-xs">Lokasi Target Kost</b>').openPopup();
            }
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
            document.getElementById('display-lat').innerText = lat.toFixed(8);
            document.getElementById('display-lng').innerText = lng.toFixed(8);
            document.getElementById('display-lat').className = "text-indigo-600 font-bold";
            document.getElementById('display-lng').className = "text-indigo-600 font-bold";
        });

        let editMap = null;
        let editMarker = null;

        function openEditModal(kost) {
            document.getElementById('editForm').action = '<?= base_url('/owner/update/') ?>' + kost.id;
            document.getElementById('edit_name').value = kost.name;
            document.getElementById('edit_price').value = kost.price;
            document.getElementById('edit_latitude').value = kost.latitude;
            document.getElementById('edit_longitude').value = kost.longitude;

            const selectedFeatures = kost.selected_features || [];
            document.querySelectorAll('.edit-feature-cb').forEach(cb => {
                cb.checked = selectedFeatures.includes(parseInt(cb.value));
            });

            document.getElementById('editModal').classList.remove('hidden');

            const lat = parseFloat(kost.latitude) || initialLat;
            const lng = parseFloat(kost.longitude) || initialLng;

            setTimeout(() => {
                if (!editMap) {
                    editMap = L.map('map-picker-edit').setView([lat, lng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.fr/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(editMap);

                    editMarker = L.marker([lat, lng]).addTo(editMap);

                    editMap.on('click', function(e) {
                        const newLat = e.latlng.lat;
                        const newLng = e.latlng.lng;
                        editMarker.setLatLng(e.latlng);
                        document.getElementById('edit_latitude').value = newLat.toFixed(8);
                        document.getElementById('edit_longitude').value = newLng.toFixed(8);
                    });
                } else {
                    editMap.setView([lat, lng], 15);
                    editMarker.setLatLng([lat, lng]);
                }
                editMap.invalidateSize();
            }, 200);
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openRejectModal(bookingId) {
            document.getElementById('rejectForm').action = '<?= base_url('/owner/booking/handle/') ?>' + bookingId + '/reject';
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</body>

</html>