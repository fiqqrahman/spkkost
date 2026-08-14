<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Jika status sesi login tidak ditemukan, tendang paksa ke gerbang login
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('/login'))->with('error', 'Akses diblokir! Silakan login terlebih dahulu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak diperlukan tindakan susulan
    }
}
