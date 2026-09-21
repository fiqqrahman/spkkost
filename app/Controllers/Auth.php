<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        if (session()->get('is_logged_in')) {
            $role = session()->get('role');
            return redirect()->to(base_url($role === 'tenant' ? '/tenant/dashboard' : '/owner/dashboard'));
        }
        return view('login');
    }

    public function register(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        if (session()->get('is_logged_in')) {
            $role = session()->get('role');
            return redirect()->to(base_url($role === 'tenant' ? '/tenant/dashboard' : '/owner/dashboard'));
        }
        return view('register');
    }

    public function attemptRegister(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $userData = [
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => password_hash((string)$this->request->getPost('password'), PASSWORD_BCRYPT),
            'role'     => 'tenant'
        ];

        $this->userModel->insert($userData);

        return redirect()->to(base_url('/login'))->with('success', 'Pendaftaran akun pencari kost berhasil. Silakan login!');
    }

    public function attemptLogin(): \CodeIgniter\HTTP\RedirectResponse
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $this->userModel->where('email', $email)->first();

        if ($user && password_verify((string)$password, (string)$user['password'])) {
            session()->regenerate();

            $userRole = $user['role'] ?? 'owner';

            session()->set([
                'user_id'      => (int)$user['id'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'role'         => $userRole,
                'is_logged_in' => true
            ]);

            $targetUrl = ($userRole === 'tenant') ? '/tenant/dashboard' : '/owner/dashboard';
            return redirect()->to(base_url($targetUrl));
        }

        return redirect()->back()->with('error', 'Kombinasi Email atau Password tidak sesuai!');
    }

    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        session()->destroy();
        return redirect()->to(base_url('/login'));
    }
}
