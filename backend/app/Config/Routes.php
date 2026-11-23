<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// Public routes (no auth required)
$routes->group('api', static function ($routes) {
    $routes->get('/', static function () {
        return service('response')->setJSON([
            'status'  => true,
            'message' => 'PSS WMS API aktif',
            'version' => '1.0.0',
            'time'    => date('c'),
        ]);
    });

    $routes->options('(.*)', static function () {
        $response = service('response');
        $allowedOrigins = ['http://localhost:5173', 'http://localhost:5174', 'http://localhost:5176'];
        $origin = service('request')->getHeaderLine('Origin');
        $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : $allowedOrigins[0];
        $response->setStatusCode(204);
        $response->setHeader('Access-Control-Allow-Origin', $allowOrigin);
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-TOKEN');
        $response->setHeader('Access-Control-Max-Age', '7200');
        return $response;
    });
    $routes->get('csrf-token', 'Api\CsrfController::index');
    $routes->post('login', 'Auth\LoginController::login');
});

// Protected routes (auth required)
$routes->group('api', ['filter' => 'auth'], static function ($routes) {

    $routes->post('logout', 'Auth\LoginController::logout');
    $routes->get('me', 'Auth\LoginController::me');

    // ==================== DASHBOARD ====================
    $routes->get('dashboard/summary', 'Dashboard\DashboardController::summary');
    $routes->get('dashboard/chart', 'Dashboard\DashboardController::chart');
    $routes->get('dashboard/top-outbound', 'Dashboard\DashboardController::topOutbound');
    $routes->get('dashboard/expired-products', 'Dashboard\DashboardController::expiredProducts');

    // ==================== PRODUCTS ====================
    $routes->get('products/statistic', 'Product\ProductController::statistic');
    $routes->get('products', 'Product\ProductController::index');
    $routes->get('products/(:segment)', 'Product\ProductController::show/$1');
    $routes->post('products', 'Product\ProductController::store');
    $routes->put('products/(:segment)', 'Product\ProductController::update/$1');
    $routes->delete('products/(:segment)', 'Product\ProductController::delete/$1');

    // ==================== BATCHES (READ-ONLY MONITORING) ====================
    $routes->get('batches/statistic', 'Api\BatchController::statistic');
    $routes->get('batches', 'Api\BatchController::index');
    $routes->get('batches/(:segment)', 'Api\BatchController::show/$1');

    // ==================== SUPPLIERS ====================
    $routes->get('suppliers', 'Api\SupplierController::index');
    $routes->get('suppliers/(:segment)', 'Api\SupplierController::show/$1');
    $routes->post('suppliers', 'Api\SupplierController::store');
    $routes->put('suppliers/(:segment)', 'Api\SupplierController::update/$1');
    $routes->delete('suppliers/(:segment)', 'Api\SupplierController::delete/$1');

    // ==================== CATEGORIES (Full CRUD) ====================
    $routes->get('categories', 'Api\CategoryController::index');
    $routes->get('categories/all', 'Api\CategoryController::all');
    $routes->get('categories/next-code', 'Api\CategoryController::nextCode');
    $routes->get('categories/(:num)', 'Api\CategoryController::show/$1');
    $routes->post('categories', 'Api\CategoryController::create');
    $routes->put('categories/(:num)', 'Api\CategoryController::update/$1');
    $routes->delete('categories/(:num)', 'Api\CategoryController::delete/$1');

    // ==================== MASTER DATA ====================
    $routes->get('master/suppliers', 'Api\MasterController::suppliers');
    $routes->get('master/categories', 'Api\MasterController::categories');
    $routes->get('master/rack-locations', 'Api\MasterController::rackLocations');

    // ==================== RACK / LOKASI ====================
    $routes->get('rack', 'Api\RackController::index');
    $routes->get('rack/(:num)', 'Api\RackController::show/$1');
    $routes->post('rack', 'Api\RackController::store');
    $routes->put('rack/(:num)', 'Api\RackController::update/$1');
    $routes->delete('rack/(:num)', 'Api\RackController::delete/$1');

    // ==================== INBOUND (BARANG MASUK) ====================
    $routes->get('inbound', 'Api\InboundController::index');
    $routes->get('inbound/products-by-supplier/(:segment)', 'Api\InboundController::productsBySupplier/$1');
    $routes->get('inbound/(:segment)', 'Api\InboundController::show/$1');
    $routes->post('inbound', 'Api\InboundController::store');
    $routes->put('inbound/(:segment)', 'Api\InboundController::update/$1');
    $routes->delete('inbound/(:segment)', 'Api\InboundController::delete/$1');
    $routes->post('inbound/(:segment)/complete', 'Api\InboundController::complete/$1');

    // ==================== OUTBOUND (BARANG KELUAR) ====================
    $routes->get('outbound', 'Api\OutboundController::index');
    $routes->get('outbound/(:segment)', 'Api\OutboundController::show/$1');
    $routes->post('outbound', 'Api\OutboundController::store');
    $routes->put('outbound/(:segment)', 'Api\OutboundController::update/$1');
    $routes->delete('outbound/(:segment)', 'Api\OutboundController::delete/$1');
    $routes->post('outbound/(:segment)/complete', 'Api\OutboundController::complete/$1');

    // ==================== STOCK MANAGEMENT ====================
    $routes->get('stock/realtime', 'Api\StockController::realtime');
    $routes->get('stock/mutations', 'Api\StockController::mutations');
    $routes->get('stock/low-stock', 'Api\StockController::lowStock');
    $routes->get('stock/adjustments', 'Api\StockController::adjust');
    $routes->get('stock/adjustments/(:segment)', 'Api\StockController::showAdjustment/$1');
    $routes->post('stock/adjustments', 'Api\StockController::storeAdjustment');
    $routes->post('stock/adjustments/(:segment)/approve', 'Api\StockController::approve/$1');
    $routes->post('stock/adjustments/(:segment)/reject', 'Api\StockController::reject/$1');

    // ==================== STOCK OPNAME ====================
    $routes->get('opname/sessions', 'Api\OpnameController::sessions');
    $routes->get('opname/sessions/(:segment)', 'Api\OpnameController::showSession/$1');
    $routes->post('opname/sessions', 'Api\OpnameController::createSession');
    $routes->post('opname/details', 'Api\OpnameController::addDetail');
    $routes->post('opname/sessions/(:segment)/complete', 'Api\OpnameController::completeSession/$1');
    $routes->delete('opname/sessions/(:segment)', 'Api\OpnameController::deleteSession/$1');

    // ==================== MONITORING EXPIRED ====================
    $routes->get('expired', 'Api\ExpiredController::index');
    $routes->get('expired/near-expiry', 'Api\ExpiredController::nearExpiry');
    $routes->get('expired/today', 'Api\ExpiredController::expiredToday');

    // ==================== LAPORAN ====================
    $routes->get('laporan/barang-masuk', 'Api\LaporanController::barangMasuk');
    $routes->get('laporan/barang-keluar', 'Api\LaporanController::barangKeluar');
    $routes->get('laporan/stok', 'Api\LaporanController::stok');
    $routes->get('laporan/stok-opname', 'Api\LaporanController::stokOpname');
    $routes->get('laporan/adjustments', 'Api\LaporanController::adjustments');
    $routes->get('laporan/expired', 'Api\LaporanController::expired');
    $routes->get('laporan/export-pdf', 'Api\LaporanController::exportPdf');

    // ==================== NOTIFICATIONS ====================
    $routes->get('notifications/summary', 'Api\NotificationController::summary');
    $routes->get('notifications', 'Api\NotificationController::index');
    $routes->get('notifications/unread', 'Api\NotificationController::unread');
    $routes->put('notifications/(:segment)/read', 'Api\NotificationController::markRead/$1');
    $routes->put('notifications/read-all', 'Api\NotificationController::markAllRead');
    $routes->post('notifications/generate', 'Api\NotificationController::generate');

    // ==================== USER MANAGEMENT (Superadmin & Admin only) ====================
    $routes->group('users', ['filter' => 'auth:admin'], static function ($routes) {
        $routes->get('', 'Api\UserController::index');
        $routes->get('(:segment)', 'Api\UserController::show/$1');
        $routes->post('', 'Api\UserController::store');
        $routes->put('(:segment)', 'Api\UserController::update/$1');
        $routes->delete('(:segment)', 'Api\UserController::delete/$1');
        $routes->post('(:segment)/change-password', 'Api\UserController::changePassword/$1');
    });
});
