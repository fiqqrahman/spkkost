<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;

final class MigratePrivateUploads extends BaseCommand
{
    protected $group       = 'Security';
    protected $name        = 'app:migrate-private-uploads';
    protected $description = 'Move legacy identity and payment uploads outside the public web root.';
    protected $usage       = 'app:migrate-private-uploads';

    public function run(array $params)
    {
        $directories = [
            ROOTPATH . 'public/uploads/identities' => WRITEPATH . 'uploads/identities',
            ROOTPATH . 'public/uploads/payments'   => WRITEPATH . 'uploads/payments',
        ];
        $moved = 0;

        foreach ($directories as $source => $destination) {
            if (! is_dir($source)) {
                continue;
            }

            if (! is_dir($destination) && ! mkdir($destination, 0750, true) && ! is_dir($destination)) {
                throw new RuntimeException('Could not create the private upload directory.');
            }

            foreach (new \DirectoryIterator($source) as $file) {
                if ($file->isDot() || $file->getFilename() === 'index.html' || $file->getFilename() === '.htaccess') {
                    continue;
                }
                if ($file->isLink() || ! $file->isFile()) {
                    throw new RuntimeException('Unexpected non-file entry found in a legacy upload directory.');
                }

                $sourcePath      = $file->getPathname();
                $destinationPath = $destination . DIRECTORY_SEPARATOR . $file->getFilename();
                if (file_exists($destinationPath)) {
                    $sourceHash      = hash_file('sha256', $sourcePath);
                    $destinationHash = hash_file('sha256', $destinationPath);
                    if (! is_string($sourceHash) || ! is_string($destinationHash) || ! hash_equals($sourceHash, $destinationHash)) {
                        throw new RuntimeException('A private upload already exists with different contents.');
                    }
                    if (! unlink($sourcePath)) {
                        throw new RuntimeException('Could not remove an already migrated public copy.');
                    }
                    $moved++;
                    continue;
                }

                if (! @rename($sourcePath, $destinationPath)) {
                    $copied = copy($sourcePath, $destinationPath);
                    $sourceHash = $copied ? hash_file('sha256', $sourcePath) : false;
                    $destinationHash = $copied ? hash_file('sha256', $destinationPath) : false;
                    if (! is_string($sourceHash) || ! is_string($destinationHash)
                        || ! hash_equals($sourceHash, $destinationHash) || ! unlink($sourcePath)) {
                        if (is_file($destinationPath)) {
                            unlink($destinationPath);
                        }
                        throw new RuntimeException('Could not safely migrate a legacy upload.');
                    }
                }

                $moved++;
            }
        }

        CLI::write("Migrated {$moved} private upload file(s).", 'green');
    }
}
