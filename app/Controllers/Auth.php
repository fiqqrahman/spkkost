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
            return redirect()->to(base_url('/owner/dashboard'));
        }
        return view('login');
    }
    public function attemptLogin(): \CodeIgniter\HTTP\RedirectResponse
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $user = $this->userModel->where('email', $email)->first();

        if ($user && password_verify((string)$password, (string)$user['password'])) {
            session()->regenerate();
            session()->set([
                'user_id'      => (int)$user['id'],
                'username'     => $user['username'],
                'email'        => $user['email'],
                'is_logged_in' => true
            ]);
            return redirect()->to(base_url('/owner/dashboard'));
        }
        return redirect()->back()->with('error', 'Kombinasi Email atau Password tidak sesuai!');
    }
    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        session()->destroy();
        return redirect()->to(base_url('/login'));
    }
}
