<?php

use AMDarter\SimplyBackItUp\Service\TempZip;
use PHPUnit\Framework\TestCase;

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
        
        // Create necessary subdirectories
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
        touch($twentytwentyDir . DIRECTORY_SEPARATOR . 'style.css');
    }

    protected function tearDown(): void
    {
        // Clean up temporary files created during tests
        $backupFiles = glob($this->tempDir . DIRECTORY_SEPARATOR . '*.zip');
        foreach ($backupFiles as $backupFile) {
            unlink($backupFile);
        }

        // Clean up the simulated WordPress directory
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

    public function testGetFileNames()
    {
        $filename = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();
        touch($filename);

        $fileNames = $this->tempZip->getFileNames();
        $this->assertContains(basename($filename), $fileNames);
    }

    public function testZipDir()
    {
        // Define a constant ABSPATH for the purpose of this test
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->wordpressDir);
        }

        $tempBackupZipFile = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();

        $this->tempZip->zipDir(ABSPATH, $tempBackupZipFile);
        $this->assertFileExists($tempBackupZipFile);
    }

    public function testGetMostRecent()
    {
        $filename1 = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();
        sleep(1);
        $filename2 = $this->tempDir . DIRECTORY_SEPARATOR . $this->tempZip->generateFilename();
        touch($filename1);
        touch($filename2);

        $mostRecent = $this->tempZip->getMostRecent();
        $this->assertEquals($filename2, $mostRecent);
    }
}
