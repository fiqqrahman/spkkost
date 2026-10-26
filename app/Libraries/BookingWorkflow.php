<?php

namespace App\Libraries;

final class BookingWorkflow
{
    /** @return array{status: string, rejection_note?: string}|null */
    public function ownerBookingAction(string $status, string $action, string $rejectionNote = ''): ?array
    {
        if ($status !== 'pending') {
            return null;
        }

        return match ($action) {
            'approve' => ['status' => 'approved'],
            'reject'  => ['status' => 'rejected', 'rejection_note' => $rejectionNote],
            default   => null,
        };
    }

    /** @return array{status: string, termination_reason: string}|null */
    public function tenantRequestsTermination(string $status, string $reason): ?array
    {
        if ($status !== 'approved' || mb_strlen(trim($reason)) < 5) {
            return null;
        }

        return [
            'status'             => 'termination_requested',
            'termination_reason' => $reason,
        ];
    }

    /** @return array{status: string}|null */
    public function ownerTerminationAction(string $status, string $action): ?array
    {
        if ($status !== 'termination_requested') {
            return null;
        }

        return match ($action) {
            'approve' => ['status' => 'terminated'],
            'reject'  => ['status' => 'approved'],
            default   => null,
        };
    }
}
