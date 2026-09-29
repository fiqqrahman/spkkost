<?php

namespace App\Controllers;

use App\Models\KostModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;

class Adminkost extends BaseController
{
    // Koneksi database utama
    protected \CodeIgniter\Database\BaseConnection $db;

    // Instance model untuk entitas kost
    private KostModel $kostModel;

    // Instance model untuk entitas pemesanan
    private BookingModel $bookingModel;

    // Instance model untuk entitas pembayaran
    private PaymentModel $paymentModel;

    // Konstruktor controller untuk inisialisasi modul dan koneksi DB
    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->kostModel    = new KostModel();
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
    }

    // Menampilkan halaman utama dashboard owner kost
    public function index(): string
    {
        // Ambil ID user dari session aktif
        $userId  = (int)session()->get('user_id');

        // Ambil daftar kost yang dimiliki oleh user
        $myKosts = $this->kostModel->where('user_id', $userId)->findAll();

        if (!empty($myKosts)) {
            // Ekstrak seluruh ID kost milik user
            $kostIds = array_column($myKosts, 'id');

            // Query join untuk mengambil fasilitas dari daftar kost terkait
            $featuresRaw = $this->db->table('kost_features')
                ->join('features', 'features.id = kost_features.feature_id')
                ->select('kost_features.kost_id, features.id as feature_id, features.name as feature_name')
                ->whereIn('kost_features.kost_id', $kostIds)
                ->get()
                ->getResultArray();

            // Peta ID fasilitas berdasarkan ID kost
            $kostFeaturesMap     = [];

            // Peta nama fasilitas berdasarkan ID kost
            $kostFeatureNamesMap = [];

            // Petakan fasilitas ke array pendukung berdasarkan ID kost
            foreach ($featuresRaw as $fr) {
                $kId = (int)$fr['kost_id'];
                $kostFeaturesMap[$kId][]     = (int)$fr['feature_id'];
                $kostFeatureNamesMap[$kId][] = $fr['feature_name'];
            }

            // Gabungkan fasilitas yang dipetakan ke dalam array data kost
            foreach ($myKosts as &$kost) {
                $kost['selected_features'] = $kostFeaturesMap[$kost['id']] ?? [];
                $kost['feature_names']     = $kostFeatureNamesMap[$kost['id']] ?? [];
            }
            unset($kost);

            // Ambil daftar pemesanan masuk untuk seluruh kost milik user
            $incomingBookings = $this->db->table('bookings')
                ->select('bookings.*, kosts.name as kost_name, users.username as tenant_name, users.email as tenant_email')
                ->join('kosts', 'kosts.id = bookings.kost_id')
                ->join('users', 'users.id = bookings.user_id')
                ->whereIn('bookings.kost_id', $kostIds)
                ->whereNotIn('bookings.status', ['terminated', 'rejected'])
                ->orderBy('bookings.id', 'DESC')
                ->get()
                ->getResultArray();

            // Lampirkan riwayat pembayaran untuk setiap item pemesanan
            foreach ($incomingBookings as &$b) {
                $b['payments'] = $this->paymentModel
                    ->where('booking_id', (int)$b['id'])
                    ->orderBy('id', 'DESC')
                    ->findAll();
            }
            unset($b);
        } else {
            // Tetapkan array kosong jika owner tidak memiliki kost
            $incomingBookings = [];
        }

        // Rendisi view dashboard dengan data yang diproses
        return view('adminkost', [
            'myKosts'          => $myKosts,
            'incomingBookings' => $incomingBookings
        ]);
    }

    // Menyimpan data kost baru ke database
    public function save(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Aturan validasi input formulir
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

        // Jalankan pemeriksaan validasi input
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        // Tangkap data yang dikirim via method POST
        $userId    = (int)session()->get('user_id');
        $name      = $this->request->getPost('name');
        $price     = $this->request->getPost('price');
        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $features  = $this->request->getPost('features') ?? [];

        // Array penampung file gambar yang berhasil diunggah
        $uploadedImages = [];
        $imageFiles     = $this->request->getFileMultiple('images');

        // Memproses pengunggahan file gambar
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

        // Mulai transaksi database untuk proses penyimpanan
        $this->db->transStart();

        // Data kost yang siap dimasukkan
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
        $kost   = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();

        if ($kost) {
            $newStatus = ((int)$kost['is_full'] === 1) ? 0 : 1;
            $this->kostModel->update($id, ['is_full' => $newStatus]);
        } else {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ditolak.');
        }

        return redirect()->to(base_url('/owner/dashboard'));
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');
        $kost   = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();

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

        $name           = $this->request->getPost('name');
        $price          = $this->request->getPost('price');
        $latitude       = $this->request->getPost('latitude');
        $longitude      = $this->request->getPost('longitude');
        $features       = $this->request->getPost('features') ?? [];
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
        $kost   = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();

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


    public function handleBooking(int $id, string $action): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int)session()->get('user_id');
        $booking = $this->db->table('bookings')
            ->select('bookings.*')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('bookings.id', $id)
            ->where('kosts.user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$booking) {
            return redirect()->back()->with('error', 'Akses ditolak atau data pengajuan tidak ditemukan.');
        }

        if ($action === 'approve') {
            $this->bookingModel->update($id, ['status' => 'approved']);
            return redirect()->back()->with('success', 'Pengajuan sewa telah disetujui.');
        } elseif ($action === 'reject') {
            $note = (string)$this->request->getPost('rejection_note');
            $this->bookingModel->update($id, [
                'status'         => 'rejected',
                'rejection_note' => $note
            ]);
            return redirect()->back()->with('success', 'Pengajuan sewa telah ditolak.');
        }

        return redirect()->back();
    }

    public function handleTermination(int $id, string $action): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int)session()->get('user_id');
        $booking = $this->db->table('bookings')
            ->select('bookings.*')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('bookings.id', $id)
            ->where('kosts.user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$booking) {
            return redirect()->back()->with('error', 'Akses ditolak atau data pengajuan tidak ditemukan.');
        }

        if ($action === 'approve') {
            $this->bookingModel->update($id, ['status' => 'terminated']);
            return redirect()->back()->with('success', 'Pengajuan berhenti sewa telah disetujui. Unit resmi dikosongkan.');
        } elseif ($action === 'reject') {
            $this->bookingModel->update($id, ['status' => 'approved']);
            return redirect()->back()->with('success', 'Pengajuan berhenti sewa ditolak. Status kembali aktif.');
        }

        return redirect()->back();
    }

    public function handlePayment(int $id, string $action): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int)session()->get('user_id');
        $payment = $this->db->table('payments')
            ->select('payments.*')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('payments.id', $id)
            ->where('kosts.user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$payment) {
            return redirect()->back()->with('error', 'Akses ditolak atau transaksi tidak ditemukan.');
        }

        $newStatus = ($action === 'verify') ? 'verified' : 'rejected';
        $this->paymentModel->update($id, ['status' => $newStatus]);

        return redirect()->back()->with('success', 'Status transaksi pembayaran berhasil diperbarui.');
    }
}
