<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    // Instance model untuk entitas pengguna (users)
    private UserModel $userModel;

    // Konstruktor controller untuk menginisialisasi model pengguna
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // Menampilkan halaman formulir login
    public function login(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        // Pengalihan otomatis jika pengguna sudah memiliki session aktif
        if (session()->get('is_logged_in')) {
            $role = session()->get('role');
            return redirect()->to(base_url($role === 'tenant' ? '/tenant/dashboard' : '/owner/dashboard'));
        }
        return view('login');
    }

    // Menampilkan halaman formulir registrasi akun baru
    public function register(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        // Pengalihan otomatis jika pengguna sudah memiliki session aktif
        if (session()->get('is_logged_in')) {
            $role = session()->get('role');
            return redirect()->to(base_url($role === 'tenant' ? '/tenant/dashboard' : '/owner/dashboard'));
        }
        return view('register');
    }

    // Memproses registrasi akun baru pencari kost (Tenant)
    public function attemptRegister(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Aturan validasi pendaftaran akun
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]'
        ];

        // Jalankan pemeriksaan validasi input
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        // Siapkan struktur data akun pengguna baru
        $userData = [
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => password_hash((string)$this->request->getPost('password'), PASSWORD_BCRYPT),
            'role'     => 'tenant'
        ];

        // Simpan data pengguna baru ke database
        $this->userModel->insert($userData);

        return redirect()->to(base_url('/login'))->with('success', 'Pendaftaran akun pencari kost berhasil. Silakan login!');
    }

    // Memproses otentikasi login pengguna
    public function attemptLogin(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Tangkap data kredensial dari request POST
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Cari data pengguna di database berdasarkan email
        $user = $this->userModel->where('email', $email)->first();

        // Verifikasi keberadaan akun dan keabsahan kata sandi
        if ($user && password_verify((string)$password, (string)$user['password'])) {
            session()->regenerate();

            // Tentukan role pengguna dari record database
            $userRole = $user['role'] ?? 'owner';

            session()->set([
                'user_id'      => (int)$user['id'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'role'         => $userRole,
                'is_logged_in' => true
            ]);

            // Arahkan lokasi redirect sesuai role pengguna
            $targetUrl = ($userRole === 'tenant') ? '/tenant/dashboard' : '/owner/dashboard';
            return redirect()->to(base_url($targetUrl));
        }

        return redirect()->back()->with('error', 'Kombinasi Email atau Password tidak sesuai!');
    }

    // Memproses keluar dari sistem dan penghancuran session
    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        // Hancurkan seluruh data session aktif
        session()->destroy();
        return redirect()->to(base_url('/login'));
    }
}
