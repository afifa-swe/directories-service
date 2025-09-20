<?php
require __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

$bucket = getenv('AWS_BUCKET') ?: 'local';
$endpoint = getenv('AWS_ENDPOINT') ?: 'http://minio:9000';
$region = getenv('AWS_DEFAULT_REGION') ?: 'us-east-1';
$key = getenv('AWS_ACCESS_KEY_ID') ?: 'minio';
$secret = getenv('AWS_SECRET_ACCESS_KEY') ?: 'minio123';

$client = new S3Client([
    'version' => 'latest',
    'region' => $region,
    'endpoint' => $endpoint,
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key' => $key,
        'secret' => $secret,
    ],
]);

try {
    // Check if bucket exists
    $exists = false;
    try {
        $client->headBucket(['Bucket' => $bucket]);
        $exists = true;
    } catch (\Aws\S3\Exception\S3Exception $e) {
        $exists = false;
    }

    if (! $exists) {
        $client->createBucket(['Bucket' => $bucket]);
        // wait until exists
        $client->waitUntil('BucketExists', ['Bucket' => $bucket]);
        echo "bucket_created\n";
    } else {
        echo "bucket_already_exists\n";
    }
} catch (Exception $e) {
    echo "error: " . $e->getMessage() . "\n";
}
