<?php

namespace AMDarter\SimplyBackItUp\CloudStorage;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use AMDarter\SimplyBackItUp\CloudStorage\Interfaces\CloudStorageInterface;

class AwsS3Storage implements CloudStorageInterface
{
    /**
     * AWS S3 Client instance.
     *
     * @var S3Client
     */
    private S3Client $s3Client;

    /**
     * Initialize the AWS S3 client.
     *
     * @param array $credentials ['region' => '', 'access_key' => '', 'secret_key' => '']
     * @return void
     */
    public function init(array $credentials): void
    {
        $this->s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => $credentials['region'],
            'credentials' => [
                'key'    => $credentials['access_key'],
                'secret' => $credentials['secret_key'],
            ],
        ]);
    }

    /**
     * Upload a stream to AWS S3.
     *
     * @param resource $stream
     * @param string $destination
     * @return bool
     * @throws \Exception
     */
    public function uploadStream($stream, string $destination): bool
    {
        try {
            $this->s3Client->putObject([
                'Bucket' => $destination, // Assuming $destination includes bucket and key
                'Key'    => $destination,
                'Body'   => $stream,
                'ACL'    => 'private',
                'ContentType' => 'application/zip',
            ]);
            return true;
        } catch (S3Exception $e) {
            throw new \Exception('AWS S3 Upload Failed: ' . $e->getMessage());
        }
    }
}
