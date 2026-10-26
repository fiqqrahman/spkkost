<?php

use App\Filters\AuthFilter;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;

final class SecurityBehaviorTest extends CIUnitTestCase
{
    public function testAuthFilterRedirectsLoggedInTenantAwayFromOwnerArea(): void
    {
        $session = session();
        $session->set(['is_logged_in' => true, 'role' => 'tenant']);

        $response = (new AuthFilter())->before(service('request'), ['owner']);

        $this->assertInstanceOf(CodeIgniter\HTTP\RedirectResponse::class, $response);
        $this->assertStringContainsString('/tenant/dashboard', $response->getHeaderLine('Location'));
        $session->destroy();
    }

    public function testAuthFilterRejectsIncompleteSessionAndAllowsMatchingRole(): void
    {
        $session = session();
        $filter = new AuthFilter();
        $request = service('request');

        $session->destroy();
        $this->assertInstanceOf(CodeIgniter\HTTP\RedirectResponse::class, $filter->before($request, ['tenant']));

        $session->set(['is_logged_in' => true, 'role' => 'owner']);
        $this->assertNull($filter->before($request, ['owner']));
        $session->destroy();
    }

    public function testTenantDashboardEscapesUntrustedBookingFields(): void
    {
        $html = view('tenant_dashboard', ['myBookings' => [[
            'id' => 4,
            'kost_name' => '<script>alert(1)</script>',
            'kost_price' => 100000,
            'campus_name' => '<img src=x onerror=alert(1)>',
            'occupant_count' => 1,
            'status' => 'rejected',
            'rejection_note' => '<svg onload=alert(1)>',
            'payments' => [],
        ]]]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringNotContainsString('<svg onload=alert(1)>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $html);
    }

    public function testCsrfFilterRejectsPostWithoutToken(): void
    {
        $request = clone service('request');
        $request->setMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $this->expectException(SecurityException::class);
        (new CSRF())->before($request);
    }
}
