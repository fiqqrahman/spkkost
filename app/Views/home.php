<!DOCTYPE html>
<html lang="id">

<?php
$campuses          = $campuses ?? [];
$results           = $results ?? [];
$selectedCampusId  = $selectedCampusId ?? 0;
$currentCampus     = $currentCampus ?? null;
$selectedLifestyle = $selectedLifestyle ?? $lifestyle ?? 'default';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rekomendasi Kost Palangka Raya</title>
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
                    <span class="font-bold text-slate-900 tracking-tight text-base block leading-none">Pencari Kost</span>
                    <span class="text-[10px] text-slate-500 font-medium tracking-wider">Kota Palangka Raya</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="<?= base_url('/login') ?>" class="text-xs font-semibold text-slate-700 hover:text-indigo-600 bg-slate-100 hover:bg-slate-200/70 border border-slate-200 px-4 py-2 rounded-lg transition">
                    Portal Pemilik Kost
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <section class="relative bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white rounded-2xl p-8 sm:p-10 shadow-sm overflow-hidden border border-indigo-950">
            <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-indigo-500/10 pointer-events-none transform skew-x-12"></div>

            <div class="relative z-10 max-w-2xl space-y-3">
                <span class="inline-flex items-center gap-1.5 bg-indigo-500/20 text-indigo-200 border border-indigo-400/30 text-xs font-semibold px-3 py-1 rounded-full">
                    Sistem Pendukung Keputusan
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Rekomendasi Kost Mahasiswa
                </h1>
                <p class="text-indigo-100/90 text-sm sm:text-base leading-relaxed font-normal">
                    Temukan pilihan kost ideal di Palangka Raya yang sesuai budget dan dekat dengan kampus kita.
                </p>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
            <!-- Section Header inside Card -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        Lokasi dan Kriteria
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Atur lokasi kampus sasaran dan preset pembobotan kriteria di bawah ini</p>
                </div>
            </div>

            <form method="GET" action="<?= base_url('/') ?>" class="bg-slate-50 border border-slate-200 p-4 rounded-xl grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label for="campus_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Patokan Kampus</label>
                    <select name="campus_id" id="campus_id" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition">
                        <?php foreach ($campuses as $campus): ?>
                            <option value="<?= $campus['id'] ?>" <?= $campus['id'] == $selectedCampusId ? 'selected' : '' ?>>
                                <?= esc($campus['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-4">
                    <label for="lifestyle" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Kriteria</label>
                    <select name="lifestyle" id="lifestyle" class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-slate-800 text-sm focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition">
                        <option value="default" <?= $selectedLifestyle === 'default' ? 'selected' : '' ?>>Standar Berimbang Sistem</option>
                        <option value="mendang_mending" <?= $selectedLifestyle === 'mendang_mending' ? 'selected' : '' ?>>Terjangkau dan Terdekat</option>
                        <option value="anak_sultan" <?= $selectedLifestyle === 'anak_sultan' ? 'selected' : '' ?>>Anak Sultan</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition shadow-xs cursor-pointer">
                        Terapkan Filter
                    </button>
                </div>
            </form>

            <div class="relative">
                <div id="map" class="h-96 rounded-xl border border-slate-200 shadow-inner z-10"></div>
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Peringkat Hasil Pencarian</h2>
                    <p class="text-xs text-slate-500">Urutan kelayakan kost teratas</p>
                </div>
                <span class="text-xs font-semibold bg-white border border-slate-200 px-3 py-1 rounded-full text-slate-600">
                    Total: <?= count($results) ?> Ditemukan
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[720px]">
                    <thead>
                        <tr class="bg-slate-100/70 text-slate-600 uppercase text-[11px] tracking-wider font-semibold border-b border-slate-200">
                            <th class="px-6 py-3.5 text-center w-20">Peringkat</th>
                            <th class="px-6 py-3.5">Informasi Kost & Fasilitas</th>
                            <th class="px-6 py-3.5 text-right w-40">Sewa / Bulan</th>
                            <th class="px-6 py-3.5 text-center w-36">Jarak Kampus</th>
                            <th class="px-6 py-3.5 text-center w-32 bg-indigo-50/40 text-indigo-900 font-bold border-l border-slate-200">Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-sm">
                        <?php if (!empty($results)): ?>
                            <?php $rank = 1;
                            foreach ($results as $row): ?>
                                <tr class="hover:bg-slate-50 transition duration-150">
                                    <td class="px-6 py-4 text-center font-bold">
                                        <?php if ($rank === 1): ?>
                                            <span class="inline-flex items-center bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-0.5 rounded text-[11px] font-bold">#1 Top</span>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-sm">#<?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-base">
                                                <?= esc($row['name']) ?>
                                            </span>
                                            <?= ($row['is_full'] == 1) ?
                                                '<span class="bg-rose-100 text-rose-700 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-semibold">Penuh</span>' :
                                                '<span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-semibold">Tersedia</span>' ?>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            <?php if (!empty($row['features'])): ?>
                                                <?php foreach ($row['features'] as $feature): ?>
                                                    <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] px-2 py-0.5 rounded font-medium">✓ <?= esc($feature) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-xs italic">Tanpa data fasilitas</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-slate-900">
                                        Rp <?= number_format($row['price'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium text-slate-600">
                                        <?= $row['distance'] ?> Km
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold bg-indigo-50/20 text-indigo-700 text-base border-l border-slate-200">
                                        <?= $row['final_score'] ?>
                                    </td>
                                </tr>
                            <?php $rank++;
                            endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Data kost tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-slate-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            &copy; <?= date('Y') ?> SPK Rekomendasi Kost Palangka Raya. All rights reserved.
        </div>
    </footer>

    <script>
        const campusInfo = <?= json_encode($currentCampus) ?>;
        const kostLocations = <?= json_encode($results) ?>;
        if (campusInfo && campusInfo.latitude && campusInfo.longitude) {
            const map = L.map('map').setView([campusInfo.latitude, campusInfo.longitude], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            const campusCustomIcon = L.divIcon({
                html: `<div class="flex items-center justify-center w-8 h-8 bg-rose-600 rounded-full shadow border-2 border-white text-white transform -translate-x-1/2 -translate-y-1/2"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg></div>`,
                className: 'custom-campus-marker',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
            L.marker([campusInfo.latitude, campusInfo.longitude], {
                    icon: campusCustomIcon
                })
                .addTo(map)
                .bindPopup(`<div class="text-slate-900 p-1"><b class="text-xs block uppercase text-rose-600">${campusInfo.name}</b></div>`)
                .openPopup();

            if (kostLocations.length > 0) {
                kostLocations.forEach(function(kost, index) {
                    if (kost.latitude && kost.longitude) {
                        const kostCustomIcon = L.divIcon({
                            html: `<div class="flex items-center justify-center w-7 h-7 bg-indigo-600 rounded-full shadow border-2 border-white text-white transform -translate-x-1/2 -translate-y-1/2 hover:scale-110 transition cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg></div>`,
                            className: 'custom-kost-marker',
                            iconSize: [28, 28],
                            iconAnchor: [14, 14]
                        });
                        const marker = L.marker([kost.latitude, kost.longitude], {
                            icon: kostCustomIcon
                        }).addTo(map);
                        let photoList = [];
                        if (kost.images) {
                            photoList = Array.isArray(kost.images) ? kost.images : JSON.parse(kost.images);
                        }

                        let popupGalleryHtml = '';
                        if (photoList && photoList.length > 0) {
                            popupGalleryHtml = '<div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:6px; margin-bottom:8px; max-height:120px; overflow-y:auto;">';
                            photoList.forEach(function(img) {
                                if (img) {
                                    popupGalleryHtml += `<img src="${'<?= base_url('uploads/kosts/') ?>' + img}" style="width:100%; height:55px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;" alt="${kost.name}">`;
                                }
                            });
                            popupGalleryHtml += '</div>';
                        }

                        const popupContent = `
                            <div class="text-slate-800 text-xs" style="min-width:180px; max-width:200px;">
                                ${popupGalleryHtml}
                                <b class="text-sm block text-indigo-700 border-b border-slate-200 pb-1 mb-1">${kost.name}</b>
                                <div style="margin-bottom:3px;"><b>Rank</b>: <span style="background:#e0e7ff; color:#3730a3; font-weight:bold; padding:1px 5px; border-radius:3px;">#${index + 1}</span></div>
                                <b>Jarak</b>: <span class="text-slate-900 font-bold">${kost.distance} Km</span><br>
                                <b>Skor</b>: <span class="text-indigo-600 font-bold">${kost.final_score}</span>
                            </div>
                        `;
                        marker.bindPopup(popupContent);
                    }
                });
            }
        }
    </script>
</body>

</html>