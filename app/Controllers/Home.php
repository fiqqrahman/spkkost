<?php

namespace App\Controllers;

use App\Models\CampusModel;
use App\Models\CriteriaModel;
use App\Models\KostModel;

class Home extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private CampusModel $campusModel;
    private CriteriaModel $criteriaModel;
    private KostModel $kostModel;
    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->campusModel   = new CampusModel();
        $this->criteriaModel = new CriteriaModel();
        $this->kostModel     = new KostModel();
    }
    public function index(): string
    {
        $campuses = $this->campusModel->findAll();
        $selectedCampusId = $this->request->getGet('campus_id') ?? ($campuses[0]['id'] ?? null);
        $lifestyle = $this->request->getGet('lifestyle') ?? 'default';
        $currentCampus = null;
        foreach ($campuses as $campus) {
            if ($campus['id'] == $selectedCampusId) {
                $currentCampus = $campus;
                break;
            }
        }
        $criterias = $this->criteriaModel->findAll();
        $weights = [];
        foreach ($criterias as $crit) {
            $weights[$crit['code']] = (float)$crit['weight'];
        }
        if ($lifestyle === 'mendang_mending') {
            $weights['C1'] = 0.50;
            $weights['C2'] = 0.40;
            $weights['C3'] = 0.05;
            $weights['C4'] = 0.05;
        } elseif ($lifestyle === 'anak_sultan') {
            $weights['C1'] = 0.10;
            $weights['C2'] = 0.10;
            $weights['C3'] = 0.30;
            $weights['C4'] = 0.50;
        }
        $alternatives = $this->kostModel->getKostsWithFeatures();

        if (empty($alternatives) || empty($currentCampus)) {
            return view('home', [
                'campuses'          => $campuses,
                'results'           => [],
                'selectedCampusId'  => $selectedCampusId,
                'currentCampus'     => $currentCampus,
                'selectedLifestyle' => $lifestyle
            ]);
        }
        foreach ($alternatives as &$alt) {
            $alt['distance'] = $this->calculateHaversine(
                (float)$currentCampus['latitude'],
                (float)$currentCampus['longitude'],
                (float)$alt['latitude'],
                (float)$alt['longitude']
            );
        }
        unset($alt);
        $minMax = [];
        foreach ($criterias as $crit) {
            $cId = (int)$crit['id'];
            $cCode = $crit['code'];
            $values = [];
            foreach ($alternatives as $alt) {
                if ($cCode === 'C1') {
                    $values[] = (float)$alt['price'];
                } elseif ($cCode === 'C2') {
                    $values[] = (float)$alt['distance'];
                } else {
                    $values[] = (float)($alt['feature_scores'][$cId] ?? 0.0);
                }
            }
            if (!empty($values)) {
                $minMax[$cId] = ($crit['type'] === 'cost') ? min($values) : max($values);
            } else {
                $minMax[$cId] = 0.0;
            }
        }
        $finalRankings = [];
        foreach ($alternatives as $alt) {
            $totalScore = 0.0;
            foreach ($criterias as $crit) {
                $cId = (int)$crit['id'];
                $cCode = $crit['code'];
                $weight = $weights[$cCode] ?? 0.0;

                $score = ($cCode === 'C1') ? (float)$alt['price'] : (($cCode === 'C2') ? (float)$alt['distance'] : (float)($alt['feature_scores'][$cId] ?? 0.0));

                if (!isset($minMax[$cId]) || $minMax[$cId] == 0.0 || $score == 0.0) {
                    continue;
                }

                $normalized = ($crit['type'] === 'cost') ? ($minMax[$cId] / $score) : ($score / $minMax[$cId]);
                $totalScore += $normalized * $weight;
            }
            $finalRankings[] = [
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
        usort($finalRankings, fn(array $a, array $b): int => $b['final_score'] <=> $a['final_score']);

        return view('home', [
            'campuses'          => $campuses,
            'results'           => $finalRankings,
            'selectedCampusId'  => $selectedCampusId,
            'currentCampus'     => $currentCampus,
            'selectedLifestyle' => $lifestyle
        ]);
    }
    private function calculateHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
