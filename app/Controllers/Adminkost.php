<?php

namespace App\Controllers;

use App\Models\KostModel;

class Adminkost extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private KostModel $kostModel;

    public function __construct()
    {
        $this->db        = \Config\Database::connect();
        $this->kostModel = new KostModel();
    }
    public function index(): string
    {
        $userId  = (int)session()->get('user_id');
        $myKosts = $this->kostModel->where('user_id', $userId)->findAll();

        if (!empty($myKosts)) {
            $kostIds = array_column($myKosts, 'id');
            $featuresRaw = $this->db->table('kost_features')
                ->join('features', 'features.id = kost_features.feature_id')
                ->select('kost_features.kost_id, features.id as feature_id, features.name as feature_name')
                ->whereIn('kost_features.kost_id', $kostIds)
                ->get()
                ->getResultArray();
            $kostFeaturesMap     = [];
            $kostFeatureNamesMap = [];

            foreach ($featuresRaw as $fr) {
                $kId = (int)$fr['kost_id'];
                $kostFeaturesMap[$kId][]     = (int)$fr['feature_id'];
                $kostFeatureNamesMap[$kId][] = $fr['feature_name'];
            }

            foreach ($myKosts as &$kost) {
                $kost['selected_features'] = $kostFeaturesMap[$kost['id']] ?? [];
                $kost['feature_names']     = $kostFeatureNamesMap[$kost['id']] ?? [];
            }
            unset($kost);
        }

        return view('adminkost', [
            'myKosts' => $myKosts
        ]);
    }
    public function save(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = [
            'name'      => 'required|min_length[3]|max_length[150]',
            'price'     => 'required|numeric|greater_than_equal_to[0]',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'images'    => [
                'rules' => 'uploaded[images]'
                    . '|is_image[images]'
                    . '|mime_in[images,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|max_size[images,2048]',
                'errors' => [
                    'uploaded' => 'Harap unggah minimal satu foto.',
                    'is_image' => 'File yang diunggah harus berupa gambar valid.',
                    'mime_in'  => 'Ekstensi gambar yang diizinkan hanya JPG, JPEG, PNG, dan WEBP.',
                    'max_size' => 'Ukuran maksimal foto 2MB.'
                ]
            ]
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }
        $userId    = (int)session()->get('user_id');
        $name      = $this->request->getPost('name');
        $price     = $this->request->getPost('price');
        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $features  = $this->request->getPost('features') ?? [];
        $uploadedImages = [];
        $imageFiles     = $this->request->getFileMultiple('images');
        if ($imageFiles) {
            $allowedMimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp'];

            foreach ($imageFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $serverMime = $file->getMimeType();

                    if (in_array($serverMime, $allowedMimes, true)) {
                        $encryptedName = $file->getRandomName();
                        $file->move(ROOTPATH . 'public/uploads/kosts/', $encryptedName);
                        $uploadedImages[] = $encryptedName;
                    }
                }
            }
        }
        $this->db->transStart();
        $kostData = [
            'user_id'   => $userId,
            'name'      => $name,
            'price'     => (float)$price,
            'latitude'  => (float)$latitude,
            'longitude' => (float)$longitude,
            'is_active' => 1,
            'is_full'   => 0,
            'image'     => !empty($uploadedImages) ? json_encode($uploadedImages) : null
        ];
        $this->kostModel->insert($kostData);
        $kostId = $this->kostModel->getInsertID();
        if (!empty($features) && is_array($features)) {
            $builder   = $this->db->table('kost_features');
            $batchData = [];
            foreach ($features as $featureId) {
                $batchData[] = [
                    'kost_id'    => (int)$kostId,
                    'feature_id' => (int)$featureId
                ];
            }
            $builder->insertBatch($batchData);
        }
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan internal saat menyimpan data.');
        }
        return redirect()->to(base_url('/owner/dashboard'))->with('success', 'Kost berhasil didaftarkan.');
    }

    public function toggleStatus(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');
        $kost = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();
        if ($kost) {
            $newStatus = ((int)$kost['is_full'] === 1) ? 0 : 1;
            $this->kostModel->update($id, [
                'is_full' => $newStatus
            ]);
        } else {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ditolak.');
        }
        return redirect()->to(base_url('/owner/dashboard'));
    }
    
    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');
        $kost = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$kost) {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ilegal! Properti tidak ditemukan.');
        }

        $rules = [
            'name'      => 'required|min_length[3]|max_length[150]',
            'price'     => 'required|numeric|greater_than_equal_to[0]',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'images'    => [
                'rules' => 'permit_empty'
                    . '|uploaded[images]'
                    . '|is_image[images]'
                    . '|mime_in[images,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|max_size[images,2048]',
                'errors' => [
                    'is_image' => 'File yang diunggah harus berupa gambar valid.',
                    'mime_in'  => 'Ekstensi gambar yang diizinkan hanya JPG, JPEG, PNG, dan WEBP.',
                    'max_size' => 'Ukuran maksimal foto 2MB.'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $name      = $this->request->getPost('name');
        $price     = $this->request->getPost('price');
        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $features  = $this->request->getPost('features') ?? [];
        $uploadedImages = [];
        $imageFiles     = $this->request->getFileMultiple('images');
        $hasNewImages   = false;

        if ($imageFiles) {
            $allowedMimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp'];
            foreach ($imageFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $serverMime = $file->getMimeType();
                    if (in_array($serverMime, $allowedMimes, true)) {
                        $encryptedName = $file->getRandomName();
                        $file->move(ROOTPATH . 'public/uploads/kosts/', $encryptedName);
                        $uploadedImages[] = $encryptedName;
                        $hasNewImages     = true;
                    }
                }
            }
        }

        if ($hasNewImages && !empty($kost['image'])) {
            $oldImages = json_decode($kost['image'], true);
            if (is_array($oldImages)) {
                foreach ($oldImages as $oldFile) {
                    $physicalPath = ROOTPATH . 'public/uploads/kosts/' . $oldFile;
                    if (file_exists($physicalPath)) {
                        unlink($physicalPath);
                    }
                }
            }
        }

        $this->db->transStart();

        $updateData = [
            'name'      => $name,
            'price'     => (float)$price,
            'latitude'  => (float)$latitude,
            'longitude' => (float)$longitude,
        ];

        if ($hasNewImages) {
            $updateData['image'] = json_encode($uploadedImages);
        }

        $this->kostModel->update($id, $updateData);
        $this->db->table('kost_features')->where('kost_id', $id)->delete();
        if (!empty($features) && is_array($features)) {
            $builder   = $this->db->table('kost_features');
            $batchData = [];
            foreach ($features as $featureId) {
                $batchData[] = [
                    'kost_id'    => (int)$id,
                    'feature_id' => (int)$featureId
                ];
            }
            $builder->insertBatch($batchData);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui data.');
        }

        return redirect()->to(base_url('/owner/dashboard'))->with('success', 'Data properti kost berhasil diperbarui.');
    }
    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');
        $kost = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$kost) {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ilegal! Properti tidak ditemukan.');
        }
        if (!empty($kost['image'])) {
            $imagesArray = json_decode($kost['image'], true);
            if (is_array($imagesArray)) {
                foreach ($imagesArray as $fileName) {
                    $physicalPath = ROOTPATH . 'public/uploads/kosts/' . $fileName;
                    if (file_exists($physicalPath)) {
                        unlink($physicalPath);
                    }
                }
            }
        }
        $this->db->transStart();
        $this->db->table('kost_features')->where('kost_id', $id)->delete();
        $this->kostModel->delete($id);
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal memproses penghapusan data.');
        }
        return redirect()->to(base_url('/owner/dashboard'))->with('success', 'Aset kost berhasil dihapus.');
    }
}
