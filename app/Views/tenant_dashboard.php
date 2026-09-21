<!DOCTYPE html>
<?php $myBookings = $myBookings ?? []; ?>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penyewa Kost</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        * {
            font-family: 'Lexend', sans-serif !important;
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
                    <span class="font-bold text-slate-900 tracking-tight text-base block leading-none">Portal Penyewa Kost</span>
                    <span class="text-[10px] text-slate-500 font-medium tracking-wider">Dashboard Tenant</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-lg text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-700 font-semibold"><?= esc(session()->get('username')) ?></span>
                    <span class="text-slate-400 font-normal">(<?= esc(session()->get('email')) ?>)</span>
                </div>
                <a href="<?= base_url('/') ?>" class="text-xs font-semibold text-slate-700 hover:text-indigo-600 bg-slate-100 hover:bg-slate-200/70 border border-slate-200 px-3.5 py-2 rounded-lg transition">
                    Beranda Utama
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
                    Tenant Control Panel
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Pengajuan & Pembayaran Kost
                </h1>
                <p class="text-indigo-100/90 text-sm sm:text-base leading-relaxed font-normal">
                    Pantau status verifikasi pemesanan sewa kamar dan upload berkas konfirmasi pembayaran bulanan.
                </p>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Riwayat Pengajuan Sewa</h2>
                    <p class="text-xs text-slate-500">Daftar unit kost yang pernah diajukan</p>
                </div>
                <span class="text-xs font-semibold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-600">
                    Total: <?= count($myBookings) ?> Pengajuan
                </span>
            </div>

            <div class="p-6 space-y-6">
                <?php if (!empty($myBookings)): ?>
                    <?php foreach ($myBookings as $b): ?>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900"><?= esc($b['kost_name']) ?></h3>
                                    <p class="text-xs text-slate-500">
                                        Tarif Sewa: <b class="text-slate-800">Rp <?= number_format($b['kost_price'], 0, ',', '.') ?> / bulan</b> |
                                        Instansi: <span class="text-slate-700"><?= esc($b['campus_name']) ?></span> (<?= $b['occupant_count'] ?> Orang)
                                    </p>
                                </div>
                                <div>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 px-3 py-1 rounded-full text-xs font-bold">Menunggu Persetujuan Pemilik</span>
                                    <?php elseif ($b['status'] === 'approved'): ?>
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 rounded-full text-xs font-bold">Pengajuan Disetujui</span>
                                    <?php else: ?>
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 px-3 py-1 rounded-full text-xs font-bold">Pengajuan Ditolak</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($b['status'] === 'rejected' && !empty($b['rejection_note'])): ?>
                                <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs p-3 rounded-lg">
                                    <b>Catatan Penolakan Pemilik:</b> <?= esc($b['rejection_note']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($b['status'] === 'approved'): ?>
                                <div class="flex items-center justify-between bg-indigo-50/50 border border-indigo-100 p-3 rounded-lg text-xs">
                                    <span class="text-indigo-900 font-medium">Kamar siap dihuni! Silakan unggah bukti pembayaran awal / DP untuk penguncian unit.</span>
                                    <button type="button"
                                        onclick='openPaymentModal(<?= json_encode($b) ?>)'
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-3.5 py-1.5 rounded-lg transition shadow-xs cursor-pointer">
                                        Bayar / Upload Struk
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Sub-Tabel Riwayat Pembayaran -->
                            <div class="pt-2">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Riwayat Pembayaran Unit Ini:</h4>
                                <?php if (!empty($b['payments'])): ?>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs bg-white border border-slate-200 rounded-lg overflow-hidden">
                                            <thead class="bg-slate-100 text-slate-600 font-semibold border-b border-slate-200">
                                                <tr>
                                                    <th class="px-4 py-2">Tanggal Upload</th>
                                                    <th class="px-4 py-2 text-right">Nominal Bayar</th>
                                                    <th class="px-4 py-2 text-center">Berkas Struk</th>
                                                    <th class="px-4 py-2 text-center">Status Verifikasi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <?php foreach ($b['payments'] as $p): ?>
                                                    <tr>
                                                        <td class="px-4 py-2 text-slate-600"><?= esc($p['payment_date']) ?></td>
                                                        <td class="px-4 py-2 text-right font-bold text-slate-800">Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                                                        <td class="px-4 py-2 text-center">
                                                            <a href="<?= base_url('uploads/payments/' . $p['proof_image']) ?>" target="_blank" class="text-indigo-600 underline font-semibold">
                                                                Lihat Resi
                                                            </a>
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <?php if ($p['status'] === 'pending'): ?>
                                                                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded text-[10px] font-bold">Proses Verifikasi</span>
                                                            <?php elseif ($p['status'] === 'verified'): ?>
                                                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-[10px] font-bold">Lunas / Diterima</span>
                                                            <?php else: ?>
                                                                <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded text-[10px] font-bold">Ditolak</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-xs text-slate-400 italic">Belum ada riwayat pembayaran yang diunggah.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-slate-400 italic">
                        Antum belum memiliki riwayat pengajuan sewa kost. Silakan cari kost di beranda utama!
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Modal Form Payment -->
    <div id="paymentModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        Form Pembayaran / Bukti Transfer
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5" id="modal-pay-kost-title">Target Unit Kost</p>
                </div>
                <button type="button" onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold px-2 py-1 cursor-pointer">&times;</button>
            </div>

            <form action="<?= base_url('/tenant/payment/upload') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" id="pay_booking_id" name="booking_id" required>

                <div>
                    <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Nominal Transfer (Rp)</label>
                    <input type="number" id="amount" name="amount" min="0" required placeholder="Contoh: 850000"
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                </div>

                <div>
                    <label for="proof_image" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Unggah Foto Struk Transfer / Resi</label>
                    <input type="file" name="proof_image" id="proof_image" accept="image/*" required
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700 text-xs focus:outline-none transition">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closePaymentModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 rounded-lg transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-xs cursor-pointer">
                        Kirim Bukti Bayar
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
        function openPaymentModal(b) {
            document.getElementById('pay_booking_id').value = b.id;
            document.getElementById('amount').value = b.kost_price;
            document.getElementById('modal-pay-kost-title').innerText = 'Unit: ' + b.kost_name;
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }
    </script>
</body>

</html>