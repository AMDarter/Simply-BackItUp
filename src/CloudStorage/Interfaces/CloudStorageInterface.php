<?php

namespace AMDarter\SimplyBackItUp\CloudStorage\Interfaces;

interface CloudStorageInterface
{
    /**
     * Initialize the cloud storage service.
     *
     * @param array $credentials The credentials required to authenticate with the cloud provider.
     * @return void
     */
    public function init(array $credentials): void;

    /**
     * Upload a stream to the cloud storage.
     *
     * @param resource $stream The stream resource to upload.
     * @param string $destination The destination path/key in the cloud storage.
     * @return bool Returns true on success, throws exception on failure.
     */
    public function uploadStream($stream, string $destination): bool;
}
