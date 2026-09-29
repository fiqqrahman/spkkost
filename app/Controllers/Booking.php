<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\KostModel;

class Booking extends BaseController
{
    // Koneksi database utama
    protected \CodeIgniter\Database\BaseConnection $db;

    // Instance model untuk entitas pemesanan/booking
    private BookingModel $bookingModel;

    // Instance model untuk entitas kost
    private KostModel $kostModel;

    // Konstruktor controller untuk menginisialisasi model dan koneksi database
    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->bookingModel = new BookingModel();
        $this->kostModel    = new KostModel();
    }

    // Memproses pengajuan booking sewa kost dari penyewa
    public function submit(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Validasi status login pengguna
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('/login'))->with('error', 'Silakan login terlebih dahulu untuk melakukan pengajuan booking sewa.');
        }

        // Validasi role pengguna harus sebagai tenant (penyewa)
        if (session()->get('role') !== 'tenant') {
            return redirect()->back()->with('error', 'Hanya akun pencari/penyewa kost yang dapat mengajukan pemesanan kamar.');
        }

        // Aturan validasi input formulir booking
        $rules = [
            'kost_id'        => 'required|numeric',
            'occupant_count' => 'required|numeric|greater_than[0]',
            'campus_name'    => 'required|min_length[3]|max_length[150]',
            'identity_doc'   => [
                'rules' => 'uploaded[identity_doc]'
                    . '|is_image[identity_doc]'
                    . '|mime_in[identity_doc,image/jpg,image/jpeg,image/png,image/webp,application/pdf]'
                    . '|max_size[identity_doc,2048]',
                'errors' => [
                    'uploaded' => 'Harap unggah bukti dokumen identitas (KTP/KTM).',
                    'is_image' => 'Dokumen identitas harus berupa gambar valid atau PDF.',
                    'mime_in'  => 'Format dokumen yang diizinkan: JPG, JPEG, PNG, WEBP, atau PDF.',
                    'max_size' => 'Ukuran berkas identitas maksimal 2MB.'
                ]
            ]
        ];

        // Jalankan pemeriksaan validasi input
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        // Tangkap data dari session dan request POST
        $userId    = (int)session()->get('user_id');
        $kostId    = (int)$this->request->getPost('kost_id');
        $occupants = (int)$this->request->getPost('occupant_count');
        $campus    = (string)$this->request->getPost('campus_name');

        // Check ketersediaan unit kost dari database
        $kost = $this->kostModel->find($kostId);
        if (!$kost || (isset($kost['is_full']) && (int)$kost['is_full'] === 1)) {
            return redirect()->back()->with('error', 'Maaf, unit kost ini sedang penuh atau tidak menerima hunian baru.');
        }

        // Memproses berkas unggahan dokumen identitas (KTP/KTM)
        $docFile = $this->request->getFile('identity_doc');
        $docName = '';

        if ($docFile && $docFile->isValid() && !$docFile->hasMoved()) {
            $docName = $docFile->getRandomName();
            $docFile->move(ROOTPATH . 'public/uploads/identities/', $docName);
        } else {
            return redirect()->back()->with('error', 'Gagal memproses unggahan berkas identitas.');
        }

        // Simpan data pengajuan booking ke database dengan status pending
        $this->bookingModel->insert([
            'user_id'        => $userId,
            'kost_id'        => $kostId,
            'occupant_count' => $occupants,
            'campus_name'    => $campus,
            'identity_doc'   => $docName,
            'status'         => 'pending'
        ]);

        // Redirect kembali ke dashboard tenant membawa pesan sukses
        return redirect()->to(base_url('/tenant/dashboard'))->with('success', 'Pengajuan booking sewa berhasil dikirimkan! Silakan pantau status pengajuan antum di Dashboard Tenant.');
    }
}
