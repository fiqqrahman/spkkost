<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\PaymentModel;

class Tenant extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private BookingModel $bookingModel;
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'tenant') {
            return redirect()->to(base_url('/login'))->with('error', 'Akses khusus penyewa kost.');
        }

        $userId = (int)session()->get('user_id');

        // Ambil booking milik tenant beserta detail kost
        $myBookings = $this->db->table('bookings')
            ->select('bookings.*, kosts.name as kost_name, kosts.price as kost_price, kosts.image as kost_image')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('bookings.user_id', $userId)
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

        return view('tenant_dashboard', [
            'myBookings' => $myBookings
        ]);
    }

    public function uploadPayment(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'tenant') {
            return redirect()->to(base_url('/login'))->with('error', 'Akses ditolak.');
        }

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

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $userId    = (int)session()->get('user_id');
        $bookingId = (int)$this->request->getPost('booking_id');
        $amount    = (float)$this->request->getPost('amount');

        // Validasi kepemilikan booking
        $booking = $this->bookingModel->where('id', $bookingId)->where('user_id', $userId)->first();
        if (!$booking || $booking['status'] !== 'approved') {
            return redirect()->back()->with('error', 'Transaksi pembayaran tidak valid atau booking belum disetujui.');
        }

        $file = $this->request->getFile('proof_image');
        $fileName = '';
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads/payments/', $fileName);
        } else {
            return redirect()->back()->with('error', 'Gagal memproses berkas gambar.');
        }

        $this->paymentModel->insert([
            'booking_id'   => $bookingId,
            'amount'       => $amount,
            'proof_image'  => $fileName,
            'payment_date' => date('Y-m-d H:i:s'),
            'status'       => 'pending'
        ]);

        return redirect()->to(base_url('/tenant/dashboard'))->with('success', 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi pemilik kost.');
    }
}
