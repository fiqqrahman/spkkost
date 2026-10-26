<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Tolak sesi yang tidak lengkap dan pastikan role sesuai dengan area yang diakses.
        $session = session();
        $role = $session->get('role');
        $expectedRole = $arguments[0] ?? null;

        if (!$session->get('is_logged_in') || !in_array($role, ['owner', 'tenant'], true)) {
            $session->destroy();
            return redirect()->to(base_url('/login'))->with('error', 'Sesi tidak valid. Silakan login kembali.');
        }

        if ($expectedRole !== null && $role !== $expectedRole) {
            $target = $role === 'tenant' ? '/tenant/dashboard' : '/owner/dashboard';
            return redirect()->to(base_url($target))->with('error', 'Akses ditolak.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak diperlukan tindakan susulan
    }
}
