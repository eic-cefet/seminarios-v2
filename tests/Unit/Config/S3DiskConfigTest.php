<?php

use Illuminate\Support\Env;

it('maps AWS_S3_TEMPORARY_URL into the s3 disk temporary_url key', function () {
    Env::getRepository()->set('AWS_S3_TEMPORARY_URL', 'https://public.minio.test');

    try {
        $fresh = require config_path('filesystems.php');

        expect($fresh['disks']['s3'])
            ->toHaveKey('temporary_url')
            ->and($fresh['disks']['s3']['temporary_url'])->toBe('https://public.minio.test');
    } finally {
        Env::getRepository()->clear('AWS_S3_TEMPORARY_URL');
    }
});

it('leaves temporary_url null when AWS_S3_TEMPORARY_URL is unset', function () {
    Env::getRepository()->clear('AWS_S3_TEMPORARY_URL');

    $fresh = require config_path('filesystems.php');

    expect($fresh['disks']['s3']['temporary_url'])->toBeNull();
});
