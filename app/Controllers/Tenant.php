<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Libraries\UploadFileManager;
use App\Libraries\PaymentUploadPolicy;
use App\Libraries\BookingWorkflow;
use Throwable;

class Tenant extends BaseController
{
    // Koneksi database utama
    protected \CodeIgniter\Database\BaseConnection $db;

    // Instance model untuk entitas pemesanan
    private BookingModel $bookingModel;

    // Instance model untuk entitas pembayaran
    private PaymentModel $paymentModel;

    private UploadFileManager $uploadFileManager;

    // Konstruktor controller untuk menginisialisasi model dan koneksi database
    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
        $this->uploadFileManager = new UploadFileManager();
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
        $rules = PaymentUploadPolicy::rules();

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
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Gagal memproses berkas gambar.');
        }

        $uploadDirectory = WRITEPATH . 'uploads/payments/';
        try {
            $this->uploadFileManager->storeWithTransaction($file, $uploadDirectory, $this->db, fn (string $fileName) => $this->paymentModel->insert([
                'booking_id'   => $bookingId,
                'amount'       => $amount,
                'proof_image'  => $fileName,
                'payment_date' => date('Y-m-d H:i:s'),
                'status'       => 'pending',
            ]));
        } catch (Throwable $exception) {
            log_message('error', 'Payment upload failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Pembayaran gagal disimpan. Silakan coba lagi.');
        }

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

        $transition = (new BookingWorkflow())->tenantRequestsTermination($booking['status'], $terminationReason);
        if ($transition === null) {
            return redirect()->back()->with('error', 'Status booking tidak mengizinkan pengajuan berhenti sewa.');
        }

        try {
            if ($this->bookingModel->update($bookingId, $transition) === false) {
                throw new \RuntimeException('Could not update the booking record.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Termination request failed: {message}', ['message' => $exception->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'Pengajuan berhenti sewa gagal disimpan. Silakan coba lagi.');
        }

        return redirect()->to(base_url('/tenant/dashboard'))->with('success', 'Pengajuan berhenti sewa berhasil dikirimkan. Menunggu konfirmasi pemilik kost.');
    }
}
