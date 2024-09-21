<?php

use AMDarter\SimplyBackItUp\Service\TempZip;
use AMDarter\SimplyBackItUp\Validators\BackupValidator;
use AMDarter\SimplyBackItUp\Exceptions\InvalidBackupFileException;
use PHPUnit\Framework\TestCase;

/**
 * This is a PHPUnit test class for the TempZip service.
 * 
 * How to run the tests:
 * 1. Ensure PHPUnit is installed, either globally or within your project (via Composer).
 *    If using Composer, install PHPUnit by running:
 *      composer require --dev phpunit/phpunit
 * 
 * 2. Run the PHPUnit tests by executing the following command in your terminal:
 *      vendor/bin/phpunit --testdox .\tests\TempZipTest.php
 * 
 * 3. PHPUnit will output the test results, showing which tests passed and failed.
 * 
 * Important Notes:
 * - Temporary directories and files are created during the tests to simulate a WordPress directory.
 * - The `tearDown()` method ensures that all temporary files and directories created during the tests are cleaned up afterward.
 */

class TempZipTest extends TestCase
{
    private $tempZip;
    private $tempDir;
    private $wordpressDir;

    protected function setUp(): void
    {
        $this->tempZip = new TempZip();
        $this->tempDir = $this->tempZip->tempDir();

        // Create a temporary directory to simulate the WordPress directory
        $this->wordpressDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'WordpressTestDir';
        if (!is_dir($this->wordpressDir)) {
            mkdir($this->wordpressDir, 0755, true);
        }

        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->wordpressDir);
        }

        $wpContentDir = $this->wordpressDir . DIRECTORY_SEPARATOR . 'wp-content';
        $themesDir = $wpContentDir . DIRECTORY_SEPARATOR . 'themes';
        $twentytwentyDir = $themesDir . DIRECTORY_SEPARATOR . 'twentytwenty';

        if (!is_dir($wpContentDir)) {
            mkdir($wpContentDir, 0755, true);
        }
        if (!is_dir($themesDir)) {
            mkdir($themesDir, 0755, true);
        }
        if (!is_dir($twentytwentyDir)) {
            mkdir($twentytwentyDir, 0755, true);
        }

        // Create some dummy files in the WordPress directory for zipping
        touch($this->wordpressDir . DIRECTORY_SEPARATOR . 'wp-config.php');
        touch($this->wordpressDir . DIRECTORY_SEPARATOR . 'index.php');
        touch($this->wordpressDir . DIRECTORY_SEPARATOR . 'wp-load.php');
        touch($this->wordpressDir . DIRECTORY_SEPARATOR . 'wp-cron.php');
        touch($twentytwentyDir . DIRECTORY_SEPARATOR . 'style.css');
        touch($twentytwentyDir . DIRECTORY_SEPARATOR . 'screenshot.png');
    }

    protected function tearDown(): void
    {
        $backupFiles = glob($this->tempDir . DIRECTORY_SEPARATOR . '*');
        foreach ($backupFiles as $backupFile) {
            unlink($backupFile);
        }

        $this->deleteDirectory($this->wordpressDir);
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testCleanup()
    {
        $filename = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();
        touch($filename, time() - 3600); // create an old file

        $this->tempZip->cleanup();
        $this->assertFileDoesNotExist($filename);
    }

    public function testList()
    {
        $filename = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();
        touch($filename);

        $files = $this->tempZip->list();
        $this->assertContains($filename, $files);
    }

    public function testZipDir()
    {
        $tempBackupZipFile = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();

        $this->tempZip->zipDir(ABSPATH, $tempBackupZipFile);
        $this->assertFileExists($tempBackupZipFile);
    }

    public function testBackupValidationTooSmall(): void
    {
        $tempBackupZipFile = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();

        $this->tempZip->zipDir(ABSPATH, $tempBackupZipFile);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The backup file is too small. The zip is missing files.');

        $validator = new BackupValidator($tempBackupZipFile);
        $validator->validateAll();
    }

    public function testBackupValidationInvalidFileName(): void
    {
        $invalidFileName = '';

        $this->expectException(InvalidBackupFileException::class);
        $this->expectExceptionMessage('The backup file name is not a valid file name.');

        $validator = new BackupValidator($invalidFileName);
        $validator->validateFileName();
    }

    public function testBackupValidationFileDoesNotExist(): void
    {
        $nonExistentFile = $this->tempDir . DIRECTORY_SEPARATOR . 'non-existent-file.zip';

        $this->expectException(InvalidBackupFileException::class);
        $this->expectExceptionMessage('The backup file does not exist or is not a regular file.');

        $validator = new BackupValidator($nonExistentFile);
        $validator->validateFileExists();
    }

    public function testBackupValidationIsExecutableFile(): void
    {
        $executableFile = $this->tempDir . DIRECTORY_SEPARATOR . 'executable-file.exe';
        touch($executableFile);
        chmod($executableFile, 0755); // Set file permissions to make it executable

        $this->expectException(InvalidBackupFileException::class);
        $this->expectExceptionMessage('DANGER: The backup file is not safe to download. We advise you to take remediation steps immediately.');

        $validator = new BackupValidator($executableFile);
        $validator->isDangerousFile();
    }

    public function testBackupValidationNotAZipFile(): void
    {
        $notAZipFile = $this->tempDir . DIRECTORY_SEPARATOR . 'not-a-zip-file.txt';
        touch($notAZipFile);

        $this->expectException(InvalidBackupFileException::class);
        $this->expectExceptionMessage('WARNING: The backup file is not a ZIP file.');

        $validator = new BackupValidator($notAZipFile);
        $validator->validateFileIsZip();
    }

    public function testBackupValidationFileCannotBeUnzipped(): void
    {
        $corruptedZipFile = $this->tempDir . DIRECTORY_SEPARATOR . 'corrupted-file.zip';
        touch($corruptedZipFile);

        file_put_contents($corruptedZipFile, 'This is not a real zip file');

        $this->expectException(InvalidBackupFileException::class);
        $this->expectExceptionMessage('The backup file cannot be unzipped. It may be corrupted.');

        $validator = new BackupValidator($corruptedZipFile);
        $validator->validateFileIsUnzippable();
    }

    public function testBackupValidationZipDoesNotContainEssentialFiles(): void
    {
        $knownChecksums = [
            'index.php' => '926dd0f95df723f9ed934eb058882cc8',
            'wp-load.php' => '9141d894aa67a3a812b4d01cfa0070ac',
            'wp-cron.php' => '78ff257936e3e616eb1f8b3f0b37d7ff',
            'wp-login.php' => '55d19bcb77ba886a258a40895e62f677',
        ];

        $tempBackupZipFile = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();

        $this->tempZip->zipDir(ABSPATH, $tempBackupZipFile);

        $this->expectException(InvalidBackupFileException::class);
        $this->expectExceptionMessage('The backup ZIP file is missing the following essential WordPress files: index.php, wp-load.php, wp-cron.php, wp-login.php...');

        $validator = new BackupValidator($tempBackupZipFile);
        $validator->validateZipContainsEssentialFiles($knownChecksums);
    }
}
