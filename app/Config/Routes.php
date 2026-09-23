<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ==========================================
// 1. PUBLIC & AUTHENTICATION ROUTES
// ==========================================
$routes->get('/', 'Home::index');

$routes->get('login', 'Auth::login');
$routes->post('auth/attempt', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');

$routes->get('register', 'Auth::register');
$routes->post('auth/register', 'Auth::attemptRegister');

// ==========================================
// 2. TENANT (PENYEWA) ROUTES
// ==========================================
$routes->get('tenant/dashboard', 'Tenant::index');
$routes->post('booking/submit', 'Booking::submit');
$routes->post('tenant/payment/upload', 'Tenant::uploadPayment');
$routes->post('tenant/termination/request', 'Tenant::requestTermination');

// ==========================================
// 3. OWNER (PEMILIK KOST) ROUTES GROUP
// ==========================================
$routes->group('owner', ['filter' => 'auth_satpam'], static function (RouteCollection $routes): void {

    // Halaman Utama & Dashboard Owner
    $routes->get('/', 'Adminkost::index');
    $routes->get('dashboard', 'Adminkost::index');

    // Pengurusan Properti Kost
    $routes->post('save', 'Adminkost::save');
    $routes->post('update/(:num)', 'Adminkost::update/$1');
    $routes->get('delete/(:num)', 'Adminkost::delete/$1');
    $routes->get('toggle-status/(:num)', 'Adminkost::toggleStatus/$1');

    // Pengurusan Kelulusan Booking & Verifikasi Pembayaran
    $routes->get('booking/handle/(:num)/(:segment)', 'Adminkost::handleBooking/$1/$2');
    $routes->post('booking/handle/(:num)/reject', 'Adminkost::handleBooking/$1/reject');
    $routes->get('payment/handle/(:num)/(:segment)', 'Adminkost::handlePayment/$1/$2');

    // Pengurusan Berhenti Sewa (Termination)
    $routes->get('booking/termination/(:num)/(:segment)', 'Adminkost::handleTermination/$1/$2');
});
