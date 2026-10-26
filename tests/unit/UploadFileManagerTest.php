<?php

use App\Libraries\UploadFileManager;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class UploadFileManagerTest extends CIUnitTestCase
{
    private string $directory;
    private $dbConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spkkost-upload-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);
        $this->dbConnection = Database::connect('tests');
        $this->dbConnection->query('CREATE TABLE IF NOT EXISTS db_upload_refs (file_name VARCHAR(255) NOT NULL)');
        $this->dbConnection->table('upload_refs')->emptyTable();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    private function uploadedFile(): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'spkkost-src-');
        file_put_contents($temp, 'proof');

        return new class($temp, 'proof.png', 'image/png', 5, UPLOAD_ERR_OK) extends UploadedFile {
            public function isValid(): bool
            {
                return is_file($this->path) && ! $this->hasMoved();
            }

            public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
            {
                $destination = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
                if (! rename($this->path, $destination)) {
                    throw new RuntimeException('Could not move test upload.');
                }
                $this->path = $destination;
                $this->name = $name;
                $this->hasMoved = true;

                return true;
            }
        };
    }

    public function testDatabaseFailureRollsBackReferenceAndRemovesStoredFile(): void
    {
        $upload = $this->uploadedFile();
        $manager = new UploadFileManager();

        try {
            $manager->storeWithTransaction($upload, $this->directory, $this->dbConnection, function (string $fileName): bool {
                $this->dbConnection->table('upload_refs')->insert(['file_name' => $fileName]);
                return false;
            });
            $this->fail('Expected the persistence failure to be thrown.');
        } catch (RuntimeException) {
            $this->assertSame(0, $this->dbConnection->table('upload_refs')->countAllResults());
            $this->assertSame([], glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: []);
        }
    }

    public function testSuccessfulPersistenceKeepsFileAndDatabaseReference(): void
    {
        $upload = $this->uploadedFile();
        $manager = new UploadFileManager();

        $fileName = $manager->storeWithTransaction($upload, $this->directory, $this->dbConnection, fn (string $name) => $this->dbConnection->table('upload_refs')->insert(['file_name' => $name]));

        $this->assertFileExists($this->directory . DIRECTORY_SEPARATOR . $fileName);
        $this->assertSame(1, $this->dbConnection->table('upload_refs')->countAllResults());
    }
}
