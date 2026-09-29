<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\PaymentModel;

class Tenant extends BaseController
{
    // Koneksi database utama
    protected \CodeIgniter\Database\BaseConnection $db;

    // Instance model untuk entitas pemesanan
    private BookingModel $bookingModel;

    // Instance model untuk entitas pembayaran
    private PaymentModel $paymentModel;

    // Konstruktor controller untuk menginisialisasi model dan koneksi database
    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
    }

    // Menampilkan halaman dashboard utama untuk penyewa kost
    public function index(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        // Validasi hak akses session penyewa kost
        if (!session()->get('is_logged_in') || session()->get('role') !== 'tenant') {
            return redirect()->to(base_url('/login'))->with('error', 'Akses khusus penyewa kost.');
        }

        // Ambil ID user dari session aktif
        $userId = (int)session()->get('user_id');

        // Ambil daftar booking aktif milik penyewa
        $myBookings = $this->db->table('bookings')
            ->select('bookings.*, kosts.name as kost_name, kosts.price as kost_price, kosts.image as kost_image')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('bookings.user_id', $userId)
            ->whereNotIn('bookings.status', ['terminated', 'rejected'])
            ->orderBy('bookings.id', 'DESC')
            ->get()
            ->getResultArray();

        // Ambil riwayat pembayaran untuk tiap booking
        foreach ($myBookings as &$b) {
            $bId = (int)$b['id'];
            $b['payments'] = $this->paymentModel
                ->where('booking_id', $bId)
                ->orderBy('id', 'DESC')
                ->findAll();
        }
        unset($b);

        // Rendisi view dashboard penyewa membawa data pemesanan
        return view('tenant_dashboard', [
            'myBookings' => $myBookings
        ]);
    }

    // Mengunggah berkas bukti pembayaran sewa/DP
    public function uploadPayment(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Pengecekan otorisasi session penyewa
        if (!session()->get('is_logged_in') || session()->get('role') !== 'tenant') {
            return redirect()->to(base_url('/login'))->with('error', 'Akses ditolak.');
        }

        // Aturan validasi input formulir pembayaran
        $rules = [
            'booking_id'  => 'required|numeric',
            'amount'      => 'required|numeric|greater_than[0]',
            'proof_image' => [
                'rules' => 'uploaded[proof_image]'
                    . '|is_image[proof_image]'
                    . '|mime_in[proof_image,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|max_size[proof_image,2048]',
                'errors' => [
                    'uploaded' => 'Harap sertakan foto resi/bukti transfer.',
                    'is_image' => 'File bukti bayar harus berupa gambar valid.',
                    'mime_in'  => 'Ekstensi gambar yang diizinkan: JPG, JPEG, PNG, WEBP.',
                    'max_size' => 'Ukuran file maksimal 2MB.'
                ]
            ]
        ];

        // Jalankan validasi input formulir
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        // Tangkap data dari request POST
        $userId    = (int)session()->get('user_id');
        $bookingId = (int)$this->request->getPost('booking_id');
        $amount    = (float)$this->request->getPost('amount');

        // Tangkap data dari request POST
        $booking = $this->bookingModel->where('id', $bookingId)->where('user_id', $userId)->first();
        if (!$booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Transaksi pembayaran tidak valid atau booking belum disetujui.');
        }

        // Memproses pengungahan file bukti bayar
        $file = $this->request->getFile('proof_image');
        $fileName = '';
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads/payments/', $fileName);
        } else {
            return redirect()->back()->with('error', 'Gagal memproses berkas gambar.');
        }

        // Simpan data pembayaran ke database
        $this->paymentModel->insert([
            'booking_id'   => $bookingId,
            'amount'       => $amount,
            'proof_image'  => $fileName,
            'payment_date' => date('Y-m-d H:i:s'),
            'status'       => 'pending'
        ]);

        return redirect()->to(base_url('/tenant/dashboard'))->with('success', 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi pemilik kost.');
    }

    // Mengajukan penghentian sewa unit kost
    public function requestTermination(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Pengecekan otorisasi session penyewa
        if (!session()->get('is_logged_in') || session()->get('role') !== 'tenant') {
            return redirect()->to(base_url('/login'))->with('error', 'Akses ditolak.');
        }

        // Aturan validasi pengajuan berhenti sewa
        $rules = [
            'booking_id'         => 'required|numeric',
            'termination_reason' => 'required|min_length[5]'
        ];

        // Jalankan validasi input
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        // Tangkap data input dari POST request
        $userId             = (int)session()->get('user_id');
        $bookingId          = (int)$this->request->getPost('booking_id');
        $terminationReason = (string)$this->request->getPost('termination_reason');

        // Validasi keberadaan hunian aktif
        $booking = $this->bookingModel->where('id', $bookingId)->where('user_id', $userId)->first();
        if (!$booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Pengajuan berhenti sewa hanya dapat dilakukan untuk hunian aktif.');
        }

        // Perbarui status pemesanan ke pengajuan berhenti sewa
        $this->bookingModel->update($bookingId, [
            'status'             => 'termination_requested',
            'termination_reason' => $terminationReason
        ]);

        return redirect()->to(base_url('/tenant/dashboard'))->with('success', 'Pengajuan berhenti sewa berhasil dikirimkan. Menunggu konfirmasi pemilik kost.');
    }
}
