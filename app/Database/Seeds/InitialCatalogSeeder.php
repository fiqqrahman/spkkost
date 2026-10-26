<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** Adds non-personal reference data to an otherwise empty catalog. */
class InitialCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            ['name' => 'Universitas Muhammadiyah Palangkaraya', 'latitude' => -2.21931818, 'longitude' => 113.92120648],
            ['name' => 'Universitas Palangka Raya', 'latitude' => -2.21584870, 'longitude' => 113.90123860],
            ['name' => 'Universitas Islam Negeri Palangka Raya', 'latitude' => -2.22877125, 'longitude' => 113.88868259],
        ];

        $campusTable = $this->db->table('campuses');
        if ($campusTable->countAllResults() === 0) {
            $campusTable->insertBatch($campuses);
        }

        $criteriaTable = $this->db->table('criterias');
        if ($criteriaTable->countAllResults() === 0) {
            $criteriaTable->insertBatch([
                ['code' => 'C1', 'name' => 'Harga', 'type' => 'cost', 'weight' => 0.40],
                ['code' => 'C2', 'name' => 'Jarak', 'type' => 'cost', 'weight' => 0.30],
                ['code' => 'C3', 'name' => 'Fasilitas Parkir', 'type' => 'benefit', 'weight' => 0.20],
                ['code' => 'C4', 'name' => 'Keamanan', 'type' => 'benefit', 'weight' => 0.10],
            ]);
        }

        $featureTable = $this->db->table('features');
        if ($featureTable->countAllResults() !== 0) {
            return;
        }

        $criteriaIds = $this->db->table('criterias')
            ->select('id, code')
            ->whereIn('code', ['C3', 'C4'])
            ->get()
            ->getResultArray();
        $criteriaIdByCode = array_column($criteriaIds, 'id', 'code');

        if (!isset($criteriaIdByCode['C3'], $criteriaIdByCode['C4'])) {
            return;
        }

        $featureTable->insertBatch([
            ['criteria_id' => $criteriaIdByCode['C3'], 'name' => 'Parkir Motor Berkanopi', 'point' => 40],
            ['criteria_id' => $criteriaIdByCode['C3'], 'name' => 'Lahan Parkir Mobil Luas', 'point' => 60],
            ['criteria_id' => $criteriaIdByCode['C4'], 'name' => 'Pagar Pengaman', 'point' => 20],
            ['criteria_id' => $criteriaIdByCode['C4'], 'name' => 'Kamera CCTV Aktif 24 Jam', 'point' => 30],
            ['criteria_id' => $criteriaIdByCode['C4'], 'name' => 'Satpam / Penjaga Kost', 'point' => 50],
        ]);
    }
}
