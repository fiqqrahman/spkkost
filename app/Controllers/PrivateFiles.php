<?php

namespace App\Controllers;

use App\Libraries\UploadFileManager;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\ResponseInterface;

final class PrivateFiles extends BaseController
{
    private \CodeIgniter\Database\BaseConnection $db;

    private UploadFileManager $uploadFileManager;

    public function __construct()
    {
        $this->db                = \Config\Database::connect();
        $this->uploadFileManager = new UploadFileManager();
    }

    public function ownerIdentity(int $bookingId): ResponseInterface
    {
        if (session()->get('role') !== 'owner') {
            return $this->notFound();
        }

        $row = $this->db->table('bookings')
            ->select('bookings.identity_doc')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('bookings.id', $bookingId)
            ->where('kosts.user_id', (int) session()->get('user_id'))
            ->get()
            ->getRowArray();

        return $this->downloadPrivateFile(
            $row['identity_doc'] ?? null,
            WRITEPATH . 'uploads/identities/',
            ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'],
            'identity-document',
        );
    }

    public function ownerPayment(int $paymentId): ResponseInterface
    {
        if (session()->get('role') !== 'owner') {
            return $this->notFound();
        }

        $row = $this->db->table('payments')
            ->select('payments.proof_image')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->join('kosts', 'kosts.id = bookings.kost_id')
            ->where('payments.id', $paymentId)
            ->where('kosts.user_id', (int) session()->get('user_id'))
            ->get()
            ->getRowArray();

        return $this->downloadPrivateFile(
            $row['proof_image'] ?? null,
            WRITEPATH . 'uploads/payments/',
            ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'],
            'payment-proof',
        );
    }

    public function tenantPayment(int $paymentId): ResponseInterface
    {
        if (session()->get('role') !== 'tenant') {
            return $this->notFound();
        }

        $row = $this->db->table('payments')
            ->select('payments.proof_image')
            ->join('bookings', 'bookings.id = payments.booking_id')
            ->where('payments.id', $paymentId)
            ->where('bookings.user_id', (int) session()->get('user_id'))
            ->get()
            ->getRowArray();

        return $this->downloadPrivateFile(
            $row['proof_image'] ?? null,
            WRITEPATH . 'uploads/payments/',
            ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'],
            'payment-proof',
        );
    }

    /** @param array<string, string> $allowedMimes */
    private function downloadPrivateFile(?string $fileName, string $directory, array $allowedMimes, string $downloadName): ResponseInterface
    {
        if ($fileName === null) {
            return $this->notFound();
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (! isset($allowedMimes[$extension])) {
            return $this->notFound();
        }

        $path = $this->uploadFileManager->resolve($directory, $fileName);
        if ($path === null) {
            return $this->notFound();
        }

        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($fileInfo->file($path) !== $allowedMimes[$extension]) {
            return $this->notFound();
        }

        $response = $this->response->download($path, null, true);
        if (! $response instanceof DownloadResponse) {
            return $this->notFound();
        }

        return $response
            ->setFileName($downloadName . '.' . $extension)
            ->setHeader('X-Content-Type-Options', 'nosniff');
    }

    private function notFound(): ResponseInterface
    {
        return $this->response->setStatusCode(404)->setBody('Not found.');
    }
}
