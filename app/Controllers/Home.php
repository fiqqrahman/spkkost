<?php

namespace App\Controllers;

use App\Models\CampusModel;
use App\Models\CriteriaModel;
use App\Models\KostModel;
use App\Libraries\SawCalculator;

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

        $finalRankings = (new SawCalculator())->rank($criterias, $alternatives, $weights);

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
