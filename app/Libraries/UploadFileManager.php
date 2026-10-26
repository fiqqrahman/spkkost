<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/** Keeps uploaded files and their database references in sync on failure. */
final class UploadFileManager
{
    /** Store a file and persist its database reference as one recoverable operation. */
    public function storeWithTransaction(UploadedFile $file, string $directory, BaseConnection $db, callable $persist): string
    {
        $fileName = $this->store($file, $directory);
        $transactionOpen = false;

        try {
            if (! $db->transBegin()) {
                throw new RuntimeException('Could not start the upload transaction.');
            }
            $transactionOpen = true;

            if ($persist($fileName) === false || $db->transStatus() === false) {
                throw new RuntimeException('Could not persist the upload reference.');
            }
            if (! $db->transCommit()) {
                throw new RuntimeException('Could not commit the upload transaction.');
            }
            $transactionOpen = false;

            return $fileName;
        } catch (Throwable $exception) {
            if ($transactionOpen) {
                $db->transRollback();
            }
            $this->deleteMany($directory, [$fileName]);
            throw $exception;
        }
    }

    public function store(UploadedFile $file, string $directory): string
    {
        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('The uploaded file is not ready to be stored.');
        }

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('The upload directory could not be created.');
        }

        $fileName = $file->getRandomName();

        try {
            $file->move($directory, $fileName);
        } catch (Throwable $exception) {
            if ($file->hasMoved()) {
                $this->delete($directory, $fileName);
            }

            throw $exception;
        }

        if (! $file->hasMoved() || ! is_file($directory . $fileName)) {
            if ($file->hasMoved()) {
                $this->delete($directory, $fileName);
            }

            throw new RuntimeException('The uploaded file could not be stored.');
        }

        return $fileName;
    }

    /** @param list<string> $fileNames */
    public function deleteMany(string $directory, array $fileNames): void
    {
        foreach (array_unique($fileNames) as $fileName) {
            if (! is_string($fileName) || ! $this->delete($directory, $fileName)) {
                log_message('error', 'Could not safely remove an uploaded file.');
            }
        }
    }

    public function delete(string $directory, string $fileName): bool
    {
        if ($fileName === '' || basename($fileName) !== $fileName || str_contains($fileName, "\0")) {
            return false;
        }

        $root = realpath($directory);
        if ($root === false) {
            return true;
        }

        $path = $root . DIRECTORY_SEPARATOR . $fileName;
        if (! file_exists($path) && ! is_link($path)) {
            return true;
        }
        if (is_link($path)) {
            return false;
        }

        $realPath = realpath($path);
        if ($realPath === false || dirname($realPath) !== $root || ! is_file($realPath)) {
            return false;
        }

        try {
            return unlink($realPath);
        } catch (Throwable) {
            return false;
        }
    }

    public function resolve(string $directory, string $fileName): ?string
    {
        if ($fileName === '' || basename($fileName) !== $fileName || str_contains($fileName, "\0")) {
            return null;
        }

        $root = realpath($directory);
        if ($root === false) {
            return null;
        }

        $candidate = $root . DIRECTORY_SEPARATOR . $fileName;
        if (is_link($candidate)) {
            return null;
        }

        $path = realpath($candidate);
        if ($path === false || dirname($path) !== $root || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return $path;
    }
}
