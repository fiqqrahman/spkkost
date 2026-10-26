<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression checks for the security hardening routes and filter configuration.
 *
 * These tests intentionally inspect the application's declared security
 * contract without connecting to or mutating the application database.
 *
 * @internal
 */
final class SecurityRegressionTest extends CIUnitTestCase
{
    private function source(string $relativePath): string
    {
        $path = ROOTPATH . $relativePath;

        $this->assertFileExists($path, 'Expected source file was not found: ' . $relativePath);

        return (string) file_get_contents($path);
    }

    public function testCsrfFilterIsEnabledGloballyBeforeRequests(): void
    {
        $filters = $this->source('app/Config/Filters.php');

        $this->assertStringContainsString("'before' => ['csrf']", $filters);
    }

    public function testLogoutIsRegisteredAsPostOnly(): void
    {
        $routes = $this->source('app/Config/Routes.php');

        $this->assertMatchesRegularExpression(
            "/\$routes->post\('logout',\s*'Auth::logout'\);/",
            $routes
        );
        $this->assertDoesNotMatchRegularExpression(
            "/\$routes->get\('logout',\s*'Auth::logout'\);/",
            $routes
        );
    }

    public function testTenantAndOwnerRoutesDeclareExpectedRoleFilters(): void
    {
        $routes = $this->source('app/Config/Routes.php');

        $this->assertStringContainsString("['filter' => 'auth_satpam:tenant']", $routes);
        $this->assertStringContainsString("['filter' => 'auth_satpam:owner']", $routes);
        $this->assertStringContainsString("$routes->get('tenant/dashboard', 'Tenant::index');", $routes);
        $this->assertStringContainsString("$routes->get('dashboard', 'Adminkost::index');", $routes);
    }

    public function testMutationRoutesUsePostRatherThanGet(): void
    {
        $routes = $this->source('app/Config/Routes.php');

        $mutationRoutes = [
            'save',
            'update/(:num)',
            'delete/(:num)',
            'toggle-status/(:num)',
            'booking/handle/(:num)/(:segment)',
            'payment/handle/(:num)/(:segment)',
            'booking/termination/(:num)/(:segment)',
        ];

        foreach ($mutationRoutes as $route) {
            $this->assertStringContainsString(
                "$routes->post('" . $route . "'",
                $routes,
                'Expected POST mutation route is missing: ' . $route
            );
            $this->assertStringNotContainsString(
                "$routes->get('" . $route . "'",
                $routes,
                'Mutation route must not be registered as GET: ' . $route
            );
        }

        $tenantMutations = [
            'booking/submit',
            'tenant/payment/upload',
            'tenant/termination/request',
        ];

        foreach ($tenantMutations as $route) {
            $this->assertStringContainsString(
                "$routes->post('" . $route . "'",
                $routes,
                'Expected tenant POST mutation route is missing: ' . $route
            );
            $this->assertStringNotContainsString(
                "$routes->get('" . $route . "'",
                $routes,
                'Tenant mutation route must not be registered as GET: ' . $route
            );
        }
    }

    public function testAuthFilterRejectsInvalidSessionsAndEnforcesExpectedRole(): void
    {
        $filter = $this->source('app/Filters/AuthFilter.php');

        $this->assertStringContainsString("$session->get('is_logged_in')", $filter);
        $this->assertStringContainsString("in_array($role, ['owner', 'tenant'], true)", $filter);
        $this->assertStringContainsString("$expectedRole !== null && $role !== $expectedRole", $filter);
        $this->assertStringContainsString("$session->destroy()", $filter);
        $this->assertStringContainsString("'/login'", $filter);
        $this->assertStringContainsString("'/tenant/dashboard'", $filter);
        $this->assertStringContainsString("'/owner/dashboard'", $filter);
    }

    public function testLogoutDestroysTheSession(): void
    {
        $auth = $this->source('app/Controllers/Auth.php');

        $this->assertMatchesRegularExpression(
            '/function\s+logout\s*\([^)]*\).*?session\(\)->destroy\(\);/s',
            $auth
        );
    }
}
