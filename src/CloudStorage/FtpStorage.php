<?php

namespace AMDarter\SimplyBackItUp\CloudStorage;

use AMDarter\SimplyBackItUp\CloudStorage\Interfaces\CloudStorageInterface;

class FtpStorage implements CloudStorageInterface
{
    /**
     * FTP connection resource.
     *
     * @var resource|null
     */
    private $ftpConnection = null;

    /**
     * Initialize the FTP connection.
     *
     * @param array $credentials ['host' => '', 'username' => '', 'password' => '', 'port' => 21, 'ssl' => false]
     * @return void
     * @throws \Exception
     */
    public function init(array $credentials): void
    {
        $port = $credentials['port'] ?? 21;
        $host = $credentials['host'];
        $ssl = $credentials['ssl'] ?? false;

        if ($ssl) {
            $this->ftpConnection = ftp_ssl_connect($host, $port, 30);
        } else {
            $this->ftpConnection = ftp_connect($host, $port, 30);
        }

        if (!$this->ftpConnection) {
            throw new \Exception('FTP Connection Failed');
        }

        $login = ftp_login($this->ftpConnection, $credentials['username'], $credentials['password']);

        if (!$login) {
            ftp_close($this->ftpConnection);
            throw new \Exception('FTP Login Failed');
        }

        ftp_pasv($this->ftpConnection, true); // Enable passive mode
    }

    /**
     * Upload a stream to FTP.
     *
     * @param resource $stream
     * @param string $destination The path in FTP (e.g., '/backups/backup.zip').
     * @return bool
     * @throws \Exception
     */
    public function uploadStream($stream, string $destination): bool
    {
        if (!$this->ftpConnection) {
            throw new \Exception('FTP Connection Not Initialized');
        }

        // Read from stream and upload in chunks
        rewind($stream);
        while (!feof($stream)) {
            $data = fread($stream, 1024 * 1024); // 1MB chunks
            if ($data === false) {
                throw new \Exception('Failed to read from stream');
            }

            $tempHandle = tmpfile();
            fwrite($tempHandle, $data);
            fseek($tempHandle, 0);

            $upload = ftp_fput($this->ftpConnection, $destination, $tempHandle, FTP_BINARY, 0);
            fclose($tempHandle);

            if (!$upload) {
                throw new \Exception('FTP Upload Failed');
            }
        }

        return true;
    }

    /**
     * Close the FTP connection.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->ftpConnection) {
            ftp_close($this->ftpConnection);
        }
    }
}