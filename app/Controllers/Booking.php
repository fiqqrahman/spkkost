<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\KostModel;

class Booking extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private BookingModel $bookingModel;
    private KostModel $kostModel;

    public function __construct()
    {
        $this->db           = \Config\Database::connect();
        $this->bookingModel = new BookingModel();
        $this->kostModel    = new KostModel();
    }

    public function submit(): \CodeIgniter\HTTP\RedirectResponse
    {
        // 1. Pengecekan status login
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('/login'))->with('error', 'Silakan login terlebih dahulu untuk melakukan pengajuan booking sewa.');
        }

        // 2. Cek Role, hanya Tenant yang boleh ajukan sewa
        if (session()->get('role') !== 'tenant') {
            return redirect()->back()->with('error', 'Hanya akun pencari/penyewa kost yang dapat mengajukan pemesanan kamar.');
        }

        // 3. Validasi Form & File Upload
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

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $userId    = (int)session()->get('user_id');
        $kostId    = (int)$this->request->getPost('kost_id');
        $occupants = (int)$this->request->getPost('occupant_count');
        $campus    = (string)$this->request->getPost('campus_name');

        // Check ketersediaan kost
        $kost = $this->kostModel->find($kostId);
        if (!$kost || (isset($kost['is_full']) && (int)$kost['is_full'] === 1)) {
            return redirect()->back()->with('error', 'Maaf, unit kost ini sedang penuh atau tidak menerima hunian baru.');
        }

        // Handle upload berkas identitas
        $docFile = $this->request->getFile('identity_doc');
        $docName = '';

        if ($docFile && $docFile->isValid() && !$docFile->hasMoved()) {
            $docName = $docFile->getRandomName();
            $docFile->move(ROOTPATH . 'public/uploads/identities/', $docName);
        } else {
            return redirect()->back()->with('error', 'Gagal memproses unggahan berkas identitas.');
        }

        // Simpan booking
        $this->bookingModel->insert([
            'user_id'        => $userId,
            'kost_id'        => $kostId,
            'occupant_count' => $occupants,
            'campus_name'    => $campus,
            'identity_doc'   => $docName,
            'status'         => 'pending'
        ]);

        return redirect()->to(base_url('/tenant/dashboard'))->with('success', 'Pengajuan booking sewa berhasil dikirimkan! Silakan pantau status pengajuan antum di Dashboard Tenant.');
    }
}
