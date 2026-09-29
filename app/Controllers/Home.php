<?php

namespace App\Controllers;

use App\Models\CampusModel;
use App\Models\CriteriaModel;
use App\Models\KostModel;

class Home extends BaseController
{
    // Koneksi database utama
    protected \CodeIgniter\Database\BaseConnection $db;

    // Instance model untuk data kampus
    private CampusModel $campusModel;

    // Instance model untuk data kriteria SPK
    private CriteriaModel $criteriaModel;

    // Instance model untuk data kost
    private KostModel $kostModel;

    // Konstruktor controller untuk menginisialisasi model dan koneksi database
    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->campusModel   = new CampusModel();
        $this->criteriaModel = new CriteriaModel();
        $this->kostModel     = new KostModel();
    }

    // Menampilkan halaman utama beserta kalkulasi rekomendasi kost (Metode SAW)
    public function index(): string
    {
        // Ambil daftar seluruh kampus dari database
        $campuses = $this->campusModel->findAll();

        // Ambil ID kampus terpilih dari URL atau gunakan default kampus pertama
        $selectedCampusId = $this->request->getGet('campus_id') ?? ($campuses[0]['id'] ?? null);

        // Ambil filter gaya hidup dari URL atau tetapkan default
        $lifestyle = $this->request->getGet('lifestyle') ?? 'default';

        // Variable penampung data kampus terpilih
        $currentCampus = null;

        // Cari data kampus yang sesuai dengan ID terpilih
        foreach ($campuses as $campus) {
            if ($campus['id'] == $selectedCampusId) {
                $currentCampus = $campus;
                break;
            }
        }

        // Ambil seluruh data kriteria penilaian dari database
        $criterias = $this->criteriaModel->findAll();

        // Array penampung bobot kriteria dasar
        $weights = [];

        // Petakan bobot kriteria default berdasarkan kode kriteria
        foreach ($criterias as $crit) {
            $weights[$crit['code']] = (float)$crit['weight'];
        }

        // Sesuaikan bobot jika penyewa memilih gaya hidup 'mendang_mending'
        if ($lifestyle === 'mendang_mending') {
            $weights['C1'] = 0.50;
            $weights['C2'] = 0.40;
            $weights['C3'] = 0.05;
            $weights['C4'] = 0.05;

            // Sesuaikan bobot jika penyewa memilih gaya hidup 'premium'
        } elseif ($lifestyle === 'anak_sultan') {
            $weights['C1'] = 0.10;
            $weights['C2'] = 0.10;
            $weights['C3'] = 0.30;
            $weights['C4'] = 0.50;
        }

        // Ambil alternatif kost beserta fitur-fiturnya
        $alternatives = $this->kostModel->getKostsWithFeatures();

        // Kembalikan view langsung jika data alternatif atau kampus kosong
        if (empty($alternatives) || empty($currentCampus)) {
            return view('home', [
                'campuses'          => $campuses,
                'results'           => [],
                'selectedCampusId'  => $selectedCampusId,
                'currentCampus'     => $currentCampus,
                'selectedLifestyle' => $lifestyle
            ]);
        }

        // Hitung jarak Haversine tiap kost terhadap kampus terpilih
        foreach ($alternatives as &$alt) {
            $alt['distance'] = $this->calculateHaversine(
                (float)$currentCampus['latitude'],
                (float)$currentCampus['longitude'],
                (float)$alt['latitude'],
                (float)$alt['longitude']
            );
        }
        unset($alt);

        // Array penampung nilai min/max tiap kriteria untuk normalisasi
        $minMax = [];

        // Evaluasi nilai min/max untuk setiap kriteria penilaian
        foreach ($criterias as $crit) {
            $cId = (int)$crit['id'];
            $cCode = $crit['code'];
            $values = [];

            // Kumpulkan nilai mentah kriteria dari seluruh alternatif kost
            foreach ($alternatives as $alt) {
                if ($cCode === 'C1') {
                    $values[] = (float)$alt['price'];
                } elseif ($cCode === 'C2') {
                    $values[] = (float)$alt['distance'];
                } else {
                    $values[] = (float)($alt['feature_scores'][$cId] ?? 0.0);
                }
            }

            // Tentukan nilai ekstrem min (untuk cost) atau max (untuk benefit)
            if (!empty($values)) {
                $minMax[$cId] = ($crit['type'] === 'cost') ? min($values) : max($values);
            } else {
                $minMax[$cId] = 0.0;
            }
        }

        // Array penampung hasil akhir pemeringkatan
        $finalRankings = [];

        // Hitung skor normalisasi terbobot untuk tiap alternatif kost
        foreach ($alternatives as $alt) {
            $totalScore = 0.0;

            // Kalkulasi skor per kriteria berdasarkan tipe dan bobotnya
            foreach ($criterias as $crit) {
                $cId = (int)$crit['id'];
                $cCode = $crit['code'];
                $weight = $weights[$cCode] ?? 0.0;

                // Tentukan nilai atribut mentah alternatif
                $score = ($cCode === 'C1') ? (float)$alt['price'] : (($cCode === 'C2') ? (float)$alt['distance'] : (float)($alt['feature_scores'][$cId] ?? 0.0));

                // Abaikan jika pembagi minMax atau nilai mentah bernilai nol
                if (!isset($minMax[$cId]) || $minMax[$cId] == 0.0 || $score == 0.0) {
                    continue;
                }

                // Rumus normalisasi SAW: Cost (min/score) vs Benefit (score/max)
                $normalized = ($crit['type'] === 'cost') ? ($minMax[$cId] / $score) : ($score / $minMax[$cId]);

                // Akumulasikan skor terbobot ke skor total
                $totalScore += $normalized * $weight;
            }

            // Susun struktur data hasil kalkulasi untuk dikirim ke view
            $finalRankings[] = [
                'id'          => $alt['id'],
                'name'        => $alt['name'],
                'price'       => $alt['price'],
                'distance'    => round($alt['distance'], 2),
                'final_score' => round($totalScore, 4),
                'latitude'    => $alt['latitude'],
                'longitude'   => $alt['longitude'],
                'features'    => $alt['feature_names'],
                'is_full'     => (int)($alt['is_full'] ?? 0),
                'images'      => json_decode($alt['image'] ?? '[]', true)
            ];
        }

        // Urutkan hasil rekomendasi dari skor tertinggi ke terendah
        usort($finalRankings, fn(array $a, array $b): int => $b['final_score'] <=> $a['final_score']);

        // Render view halaman utama membawa data hasil pemeringkatan SAW
        return view('home', [
            'campuses'          => $campuses,
            'results'           => $finalRankings,
            'selectedCampusId'  => $selectedCampusId,
            'currentCampus'     => $currentCampus,
            'selectedLifestyle' => $lifestyle
        ]);
    }

    // Menghitung jarak antara dua titik koordinat spasial menggunakan rumus Haversine (dalam kilometer)
    private function calculateHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Jari-jari konstan bumi dalam satuan kilometer
        $earthRadius = 6371.0;

        // Konversi selisih derajat latitudo ke radian
        $dLat = deg2rad($lat2 - $lat1);

        // Konversi selisih derajat longitudo ke radian
        $dLon = deg2rad($lon2 - $lon1);

        // Kalkulasi komponen sinus seperdua sudut tengah Haversine
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        // Hitung jarak sudut dalam radian dengan fungsi atan2
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
