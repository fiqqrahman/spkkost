<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal Pencari & Pemilik Kost</title>
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

<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between antialiased">
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden grid grid-cols-1 md:grid-cols-12">

            <div class="md:col-span-5 relative bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 text-white p-8 flex flex-col justify-between overflow-hidden border-b md:border-b-0 md:border-r border-indigo-950">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-xs">
                        P
                    </div>
                    <div>
                        <span class="font-bold text-white tracking-tight text-sm block leading-none">Pencari Kost</span>
                        <span class="text-[10px] text-indigo-200 font-medium tracking-wider">Palangka Raya</span>
                    </div>
                </div>

                <div class="relative z-10 my-8 space-y-3">
                    <span class="inline-flex items-center gap-1.5 bg-indigo-500/20 text-indigo-200 border border-indigo-400/30 text-[10px] font-semibold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        Portal Otentikasi
                    </span>
                    <h1 class="text-xl font-extrabold tracking-tight leading-snug">
                        SELAMAT DATANG
                    </h1>
                    <p class="text-indigo-100/80 text-xs leading-relaxed font-normal">
                        Masuk untuk mengelola properti kost milik anda atau memantau pengajuan sewa & pembayaran kamar.
                    </p>
                </div>

                <div class="relative z-10 text-[10px] text-indigo-300/70 font-medium">
                    SPK Rekomendasi Kost &copy; <?= date('Y') ?>
                </div>
            </div>

            <div class="md:col-span-7 p-8 sm:p-10 flex flex-col justify-center space-y-5 bg-white">
                <div class="space-y-1">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Otorisasi Masuk</h2>
                    <p class="text-slate-500 text-xs">Gunakan alamat email dan kata sandi terdaftar.</p>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3.5 rounded-xl font-medium flex items-center gap-2">
                        <span>✓</span> <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs p-3.5 rounded-xl font-medium flex items-center gap-2">
                        <span>⚠</span> <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('/auth/attempt') ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Email Terdaftar</label>
                        <input type="email" id="email" name="email" required placeholder="email@domain.com"
                            class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Kata Sandi</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••"
                            class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm transition shadow-xs cursor-pointer mt-2">
                        Masuk ke System
                    </button>
                </form>

                <div class="pt-3 text-center border-t border-slate-100 space-y-2">
                    <p class="text-xs text-slate-500">
                        Belum punya akun pencari kost?
                        <a href="<?= base_url('/register') ?>" class="text-indigo-600 hover:underline font-bold">Daftar Akun Baru</a>
                    </p>
                    <div>
                        <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                            ← Kembali ke Beranda Utama
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-4">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            &copy; <?= date('Y') ?> SPK Rekomendasi Kost Palangka Raya. All rights reserved.
        </div>
    </footer>
</body>

</html>