<?php

it('exposes temporary_url on the s3 disk so signed URLs can use a public host', function () {
    config()->set('filesystems.disks.s3.temporary_url', 'https://minio.example.test');

    expect(config('filesystems.disks.s3.temporary_url'))
        ->toBe('https://minio.example.test');
});

it('maps AWS_S3_TEMPORARY_URL into the s3 disk temporary_url key', function () {
    config()->set('filesystems.disks.s3', config('filesystems.disks.s3'));
    putenv('AWS_S3_TEMPORARY_URL=https://public.minio.test');

    $fresh = require config_path('filesystems.php');

    expect($fresh['disks']['s3'])
        ->toHaveKey('temporary_url')
        ->and($fresh['disks']['s3']['temporary_url'])->toBe('https://public.minio.test');

    putenv('AWS_S3_TEMPORARY_URL');
});
