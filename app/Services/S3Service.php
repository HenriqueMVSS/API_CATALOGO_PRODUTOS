<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    private ?S3Client $s3Client = null;
    private bool $useS3;

    public function __construct()
    {
        $this->useS3 = !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'));
        
        if ($this->useS3) {
            try {
                $this->s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => config('filesystems.disks.s3.region', 'us-east-1'),
                    'credentials' => [
                        'key' => config('filesystems.disks.s3.key'),
                        'secret' => config('filesystems.disks.s3.secret'),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to initialize S3 client', ['error' => $e->getMessage()]);
                $this->useS3 = false;
            }
        }
    }

    public function uploadFile(UploadedFile $file, string $path): string
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fullPath = $path . '/' . $fileName;

        if ($this->useS3 && config('filesystems.disks.s3.bucket')) {
            try {
                $result = $this->s3Client->putObject([
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $fullPath,
                    'Body' => fopen($file->getRealPath(), 'r'),
                    'ACL' => 'public-read',
                    'ContentType' => $file->getMimeType(),
                ]);

                $url = $result->get('ObjectURL');
                Log::info('File uploaded to S3', ['path' => $fullPath, 'url' => $url]);
                return $url;
            } catch (\Exception $e) {
                Log::error('Failed to upload to S3, falling back to local storage', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback para storage local
        $storedPath = Storage::disk('public')->putFile($path, $file);
        $url = Storage::disk('public')->url($storedPath);
        
        Log::info('File uploaded to local storage', ['path' => $storedPath, 'url' => $url]);
        return $url;
    }
}
