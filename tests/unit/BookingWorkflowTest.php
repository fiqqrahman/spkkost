<?php

use App\Libraries\BookingWorkflow;
use CodeIgniter\Test\CIUnitTestCase;

final class BookingWorkflowTest extends CIUnitTestCase
{
    public function testBookingAndTerminationLifecycle(): void
    {
        $workflow = new BookingWorkflow();
        $booking = $workflow->ownerBookingAction('pending', 'approve');
        $this->assertSame('approved', $booking['status']);

        $termination = $workflow->tenantRequestsTermination($booking['status'], 'Pindah kerja');
        $this->assertSame('termination_requested', $termination['status']);
        $this->assertSame('terminated', $workflow->ownerTerminationAction($termination['status'], 'approve')['status']);
    }

    public function testInvalidTransitionsAndShortTerminationReasonAreRejected(): void
    {
        $workflow = new BookingWorkflow();

        $this->assertNull($workflow->ownerBookingAction('approved', 'reject'));
        $this->assertNull($workflow->ownerBookingAction('pending', 'unknown'));
        $this->assertNull($workflow->tenantRequestsTermination('pending', 'Alasan panjang'));
        $this->assertNull($workflow->tenantRequestsTermination('approved', '   a '));
        $this->assertNull($workflow->ownerTerminationAction('approved', 'approve'));
    }
}
