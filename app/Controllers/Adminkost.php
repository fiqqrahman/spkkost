<?php

namespace App\Controllers;

use App\Models\KostModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Libraries\UploadFileManager;
use Throwable;

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

    private UploadFileManager $uploadFileManager;

    // Konstruktor controller untuk inisialisasi modul dan koneksi DB
    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->kostModel    = new KostModel();
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
        $this->uploadFileManager = new UploadFileManager();
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

        $uploadedImages = [];
        $imageFiles     = $this->request->getFileMultiple('images');
        $uploadDirectory = ROOTPATH . 'public/uploads/kosts/';
        $transactionOpen = false;

        try {
            $allowedMimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp'];
            foreach ($imageFiles ?? [] as $file) {
                if (! $file->isValid() || $file->hasMoved()) {
                    throw new \RuntimeException('An uploaded kost image is invalid.');
                }
                if (! in_array($file->getMimeType(), $allowedMimes, true)) {
                    throw new \RuntimeException('An uploaded kost image has an unsupported MIME type.');
                }
                $uploadedImages[] = $this->uploadFileManager->store($file, $uploadDirectory);
            }

            if ($uploadedImages === []) {
                throw new \RuntimeException('At least one kost image is required.');
            }

            if (! $this->db->transBegin()) {
                throw new \RuntimeException('Could not start the kost transaction.');
            }
            $transactionOpen = true;

            $kostData = [
                'user_id'   => $userId,
                'name'      => $name,
                'price'     => (float)$price,
                'latitude'  => (float)$latitude,
                'longitude' => (float)$longitude,
                'is_active' => 1,
                'is_full'   => 0,
                'image'     => json_encode($uploadedImages),
            ];

            if ($this->kostModel->insert($kostData) === false) {
                throw new \RuntimeException('Could not insert the kost record.');
            }
            $kostId = (int)$this->kostModel->getInsertID();

            if (! empty($features) && is_array($features)) {
                $batchData = [];
                foreach ($features as $featureId) {
                    $batchData[] = [
                        'kost_id'    => $kostId,
                        'feature_id' => (int)$featureId,
                    ];
                }

                if ($this->db->table('kost_features')->insertBatch($batchData) === false) {
                    throw new \RuntimeException('Could not save the kost features.');
                }
            }

            if ($this->db->transStatus() === false || ! $this->db->transCommit()) {
                throw new \RuntimeException('Could not commit the kost transaction.');
            }
            $transactionOpen = false;
        } catch (Throwable $exception) {
            if ($transactionOpen) {
                $this->db->transRollback();
            }
            $this->uploadFileManager->deleteMany($uploadDirectory, $uploadedImages);
            log_message('error', 'Kost creation failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }

        return redirect()->to(base_url('/owner/dashboard'))->with('success', 'Kost berhasil didaftarkan.');
    }

    // Mengubah status ketersediaan kamar kost (Penuh / Tersedia)
    public function toggleStatus(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');

        // Validasi kepemilikan aset kost
        $kost   = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();

        if ($kost) {

            // Balikkan status ketersedian kost
            $newStatus = ((int)$kost['is_full'] === 1) ? 0 : 1;
            if (! $this->performModelUpdate(
                fn () => $this->kostModel->update($id, ['is_full' => $newStatus]),
                'Kost availability update failed',
            )) {
                return redirect()->back()->with('error', 'Status ketersediaan kost gagal diperbarui.');
            }
        } else {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ditolak.');
        }

        return redirect()->to(base_url('/owner/dashboard'));
    }

    // Memperbarui data properti kost yang sudah ada
    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');

        // Pastikan properti memang milik user yang sedang login
        $kost   = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$kost) {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ilegal! Properti tidak ditemukan.');
        }

        // Aturan validasi pembaruan data
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

        // Jalankan validasi input
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $name           = $this->request->getPost('name');
        $price          = $this->request->getPost('price');
        $latitude       = $this->request->getPost('latitude');
        $longitude      = $this->request->getPost('longitude');
        $features       = $this->request->getPost('features') ?? [];
        $uploadedImages  = [];
        $imageFiles      = $this->request->getFileMultiple('images');
        $uploadDirectory = ROOTPATH . 'public/uploads/kosts/';
        $oldImages       = $this->decodeKostImageNames($kost['image'] ?? null);
        $transactionOpen = false;

        try {
            $allowedMimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp'];
            foreach ($imageFiles ?? [] as $file) {
                if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if (! $file->isValid() || $file->hasMoved()) {
                    throw new \RuntimeException('An uploaded kost image is invalid.');
                }
                if (! in_array($file->getMimeType(), $allowedMimes, true)) {
                    throw new \RuntimeException('An uploaded kost image has an unsupported MIME type.');
                }
                $uploadedImages[] = $this->uploadFileManager->store($file, $uploadDirectory);
            }

            if (! $this->db->transBegin()) {
                throw new \RuntimeException('Could not start the kost update transaction.');
            }
            $transactionOpen = true;

            $updateData = [
                'name'      => $name,
                'price'     => (float)$price,
                'latitude'  => (float)$latitude,
                'longitude' => (float)$longitude,
            ];

            if ($uploadedImages !== []) {
                $updateData['image'] = json_encode($uploadedImages);
            }

            if ($this->kostModel->update($id, $updateData) === false) {
                throw new \RuntimeException('Could not update the kost record.');
            }

            if ($this->db->table('kost_features')->where('kost_id', $id)->delete() === false) {
                throw new \RuntimeException('Could not replace the kost features.');
            }

            if (! empty($features) && is_array($features)) {
                $batchData = [];
                foreach ($features as $featureId) {
                    $batchData[] = [
                        'kost_id'    => (int)$id,
                        'feature_id' => (int)$featureId,
                    ];
                }

                if ($this->db->table('kost_features')->insertBatch($batchData) === false) {
                    throw new \RuntimeException('Could not save the updated kost features.');
                }
            }

            if ($this->db->transStatus() === false || ! $this->db->transCommit()) {
                throw new \RuntimeException('Could not commit the kost update transaction.');
            }
            $transactionOpen = false;
        } catch (Throwable $exception) {
            if ($transactionOpen) {
                $this->db->transRollback();
            }
            $this->uploadFileManager->deleteMany($uploadDirectory, $uploadedImages);
            log_message('error', 'Kost update failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui data.');
        }

        if ($uploadedImages !== []) {
            $this->uploadFileManager->deleteMany($uploadDirectory, $oldImages);
        }

        return redirect()->to(base_url('/owner/dashboard'))->with('success', 'Data properti kost berhasil diperbarui.');
    }

    // Menghapus data kost beserta seluruh file fisik gambarnya
    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int)session()->get('user_id');

        // Pastikan hak akses pemilik kost 
        $kost   = $this->kostModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$kost) {
            return redirect()->to(base_url('/owner/dashboard'))->with('error', 'Akses ilegal! Properti tidak ditemukan.');
        }

        $uploadDirectory = ROOTPATH . 'public/uploads/kosts/';
        $oldImages       = $this->decodeKostImageNames($kost['image'] ?? null);
        $transactionOpen = false;
        $identityFiles   = [];
        $paymentFiles    = [];

        try {
            if (! $this->db->transBegin()) {
                throw new \RuntimeException('Could not start the kost deletion transaction.');
            }
            $transactionOpen = true;

            // Lock the parent row so new child records cannot race the cascade cleanup.
            $kostTable = $this->db->protectIdentifiers('kosts');
            $lockedKost = $this->db->query(
                'SELECT id FROM ' . $kostTable . ' WHERE id = ? AND user_id = ? FOR UPDATE',
                [$id, $userId],
            )->getRowArray();
            if (! $lockedKost) {
                throw new \RuntimeException('The kost record is no longer available to this owner.');
            }

            $bookingRows = $this->db->table('bookings')
                ->select('id, identity_doc')
                ->where('kost_id', $id)
                ->get()
                ->getResultArray();
            $bookingIds    = array_map('intval', array_column($bookingRows, 'id'));
            $identityFiles = array_values(array_filter(array_column($bookingRows, 'identity_doc'), 'is_string'));

            if ($bookingIds !== []) {
                $paymentRows = $this->db->table('payments')
                    ->select('proof_image')
                    ->whereIn('booking_id', $bookingIds)
                    ->get()
                    ->getResultArray();
                $paymentFiles = array_values(array_filter(array_column($paymentRows, 'proof_image'), 'is_string'));
            }

            if ($this->db->table('kost_features')->where('kost_id', $id)->delete() === false) {
                throw new \RuntimeException('Could not delete the kost feature links.');
            }
            if ($this->kostModel->delete($id) === false) {
                throw new \RuntimeException('Could not delete the kost record.');
            }
            if ($this->db->transStatus() === false || ! $this->db->transCommit()) {
                throw new \RuntimeException('Could not commit the kost deletion transaction.');
            }
            $transactionOpen = false;
        } catch (Throwable $exception) {
            if ($transactionOpen) {
                $this->db->transRollback();
            }
            log_message('error', 'Kost deletion failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->with('error', 'Gagal memproses penghapusan data.');
        }

        // Hapus berkas hanya setelah transaksi database berhasil.
        $this->uploadFileManager->deleteMany($uploadDirectory, $oldImages);
        $this->uploadFileManager->deleteMany(WRITEPATH . 'uploads/identities/', $identityFiles);
        $this->uploadFileManager->deleteMany(WRITEPATH . 'uploads/payments/', $paymentFiles);

        return redirect()->to(base_url('/owner/dashboard'))->with('success', 'Aset kost berhasil dihapus.');
    }

    // Memproses persetujuan atau penolakan pengajuan sewa (Booking)
    public function handleBooking(int $id, string $action): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int)session()->get('user_id');

        // Verifikasi keberadaan booking dan hak akses owner
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

        // Proses aksi persetujuan atau penolakan
        if ($action === 'approve') {
            if (! $this->performModelUpdate(
                fn () => $this->bookingModel->update($id, ['status' => 'approved']),
                'Booking approval failed',
            )) {
                return redirect()->back()->with('error', 'Pengajuan sewa gagal diperbarui.');
            }
            return redirect()->back()->with('success', 'Pengajuan sewa telah disetujui.');
        } elseif ($action === 'reject') {
            $note = (string)$this->request->getPost('rejection_note');
            if (! $this->performModelUpdate(
                fn () => $this->bookingModel->update($id, [
                    'status'         => 'rejected',
                    'rejection_note' => $note,
                ]),
                'Booking rejection failed',
            )) {
                return redirect()->back()->with('error', 'Pengajuan sewa gagal diperbarui.');
            }
            return redirect()->back()->with('success', 'Pengajuan sewa telah ditolak.');
        }

        return redirect()->back();
    }

    // Memproses pengajuan penghentian/terminasi sewa oleh penyewa
    public function handleTermination(int $id, string $action): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int)session()->get('user_id');

        // Verifikasi kepemilikan dan hak akses owener yang sesuai atas transaksi terminasi
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

        // Setujui atau tolak terminasi penyewaan
        if ($action === 'approve') {
            if (! $this->performModelUpdate(
                fn () => $this->bookingModel->update($id, ['status' => 'terminated']),
                'Termination approval failed',
            )) {
                return redirect()->back()->with('error', 'Pengajuan berhenti sewa gagal diperbarui.');
            }
            return redirect()->back()->with('success', 'Pengajuan berhenti sewa telah disetujui. Unit resmi dikosongkan.');
        } elseif ($action === 'reject') {
            if (! $this->performModelUpdate(
                fn () => $this->bookingModel->update($id, ['status' => 'approved']),
                'Termination rejection failed',
            )) {
                return redirect()->back()->with('error', 'Pengajuan berhenti sewa gagal diperbarui.');
            }
            return redirect()->back()->with('success', 'Pengajuan berhenti sewa ditolak. Status kembali aktif.');
        }

        return redirect()->back();
    }

    // Melakukan verifikasi transaksi pembayaran dari penyewa
    public function handlePayment(int $id, string $action): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int)session()->get('user_id');

        // Verifikasi hak akses dan data pembayaran yang terhubung dengan unit milik owner
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

        // Perbarui status verifikasi pembayaran
        $newStatus = ($action === 'verify') ? 'verified' : 'rejected';
        if (! $this->performModelUpdate(
            fn () => $this->paymentModel->update($id, ['status' => $newStatus]),
            'Payment verification update failed',
        )) {
            return redirect()->back()->with('error', 'Status transaksi gagal diperbarui.');
        }

        return redirect()->back()->with('success', 'Status transaksi pembayaran berhasil diperbarui.');
    }

    /** @return list<string> */
    private function decodeKostImageNames(?string $storedImages): array
    {
        if ($storedImages === null || $storedImages === '') {
            return [];
        }

        $images = json_decode($storedImages, true);
        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(
            $images,
            static fn ($fileName): bool => is_string($fileName) && $fileName !== '',
        ));
    }

    private function performModelUpdate(callable $update, string $context): bool
    {
        try {
            if ($update() !== false) {
                return true;
            }
        } catch (Throwable $exception) {
            log_message('error', '{context}: {message}', [
                'context' => $context,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        log_message('error', '{context}: the model update returned false.', ['context' => $context]);

        return false;
    }
}
