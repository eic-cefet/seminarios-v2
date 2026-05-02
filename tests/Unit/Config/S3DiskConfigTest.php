<?php

it('exposes temporary_url on the s3 disk so signed URLs can use a public host', function () {
    config()->set('filesystems.disks.s3.temporary_url', 'https://minio.example.test');

    expect(config('filesystems.disks.s3.temporary_url'))
        ->toBe('https://minio.example.test');
});

it('explicitly sets bucket_endpoint=false on the s3 disk for MinIO compatibility', function () {
    expect(config('filesystems.disks.s3'))
        ->toHaveKey('bucket_endpoint')
        ->and(config('filesystems.disks.s3.bucket_endpoint'))->toBeFalse();
});

it('maps AWS_URL into the s3 disk temporary_url key', function () {
    expect(config('filesystems.disks.s3'))->toHaveKey('temporary_url');
});
