<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');

$routes->get('login', 'Auth::login');
$routes->post('auth/attempt', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');

$routes->group('owner', ['filter' => 'auth_satpam'], static function (RouteCollection $routes): void {

    // Halaman utama 
    $routes->get('/', 'Adminkost::index');

    // Dash admin
    $routes->get('dashboard', 'Adminkost::index');

    // Simpan data (POST) -> localhost/owner/save
    $routes->post('save', 'Adminkost::save');

    // Togle status kost
    $routes->get('toggle-status/(:num)', 'Adminkost::toggleStatus/$1');

    // Delete kost
    $routes->get('delete/(:num)', 'Adminkost::delete/$1');

    // Update kost
    $routes->post('update/(:num)', 'Adminkost::update/$1');
});
