<?php

use App\Models\Registration;
use App\Services\CertificateService;
use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToWriteFile;

/**
 * Exercise the real S3 adapter and SDK against an ACL-disabled bucket boundary.
 */
function useCertificateS3Bucket(array &$objects, array &$operations, ?string $failOperation = null, string $root = ''): void
{
    $disk = Storage::build([
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-certificates',
        'root' => $root,
        'throw' => true,
        'handler' => function (CommandInterface $command) use (&$objects, &$operations, $failOperation) {
            $operation = $command->getName();
            $key = $command['Key'];
            $operations[] = $operation;
            if ($operation === 'GetObjectAcl' || $operation === $failOperation) {
                return Create::rejectionFor(new S3Exception('Access denied', $command, [
                    'code' => 'AccessDenied', 'response' => new Response(403),
                ]));
            }
            if (isset($command['ACL']) && ($operation === 'CopyObject' || $command['ACL'] !== 'bucket-owner-full-control')) {
                return Create::rejectionFor(new S3Exception('ACLs are disabled', $command, [
                    'code' => 'AccessControlListNotSupported', 'response' => new Response(400),
                ]));
            }
            if (in_array($operation, ['HeadObject', 'GetObject'], true)) {
                if (! array_key_exists($key, $objects)) {
                    return Create::rejectionFor(new S3Exception('Not found', $command, [
                        'code' => 'NoSuchKey', 'response' => new Response(404),
                    ]));
                }

                return Create::promiseFor(new Result([
                    'ContentLength' => strlen($objects[$key]),
                    'Body' => Utils::streamFor($objects[$key]),
                ]));
            }
            if ($operation === 'PutObject') {
                $objects[$key] = (string) $command['Body'];
            } elseif ($operation === 'CopyObject') {
                $source = rawurldecode($command['CopySource']);
                $source = substr($source, strlen('test-certificates/'));
                $objects[$key] = $objects[$source];
            } elseif ($operation === 'DeleteObject') {
                unset($objects[$key]);
            } else {
                throw new RuntimeException('Unexpected S3 operation: '.$operation);
            }

            return Create::promiseFor(new Result);
        },
    ]);
    Storage::set('s3', $disk);
}

it('uploads certificates to an ACL-disabled S3 bucket', function (string $format) {
    $objects = [];
    $operations = [];
    useCertificateS3Bucket($objects, $operations);
    $registration = Registration::factory()->create(['certificate_code' => 'acl-upload']);

    $path = app(CertificateService::class)->{'generate'.ucfirst($format)}($registration);

    expect($objects[$path])->not->toBeEmpty();
    expect($objects[$path])->toStartWith($format === 'pdf' ? '%PDF-' : "\xff\xd8");
    expect(Cache::get('certificate_exists_'.$format.':acl-upload'))->toBeTrue();
})->with(['jpg', 'pdf']);

it('migrates legacy certificates without ACL reads or unsupported ACL writes', function (string $format) {
    $registration = Registration::factory()->create(['certificate_code' => 'acl-migrate']);
    $source = 'certificates/'.$registration->seminar->scheduled_at->year.'/'.$registration->seminar->slug.'/acl-migrate.'.$format;
    $target = 'certificates/acl-migrate.'.$format;
    $objects = [$source => 'original certificate'];
    $operations = [];
    useCertificateS3Bucket($objects, $operations);

    expect(app(CertificateService::class)->{$format.'Exists'}($registration, fresh: true))->toBeTrue();

    expect($objects[$target])->toBe('original certificate');
    expect($objects[$source])->toBe('original certificate');
    expect($operations)->not->toContain('GetObjectAcl', 'DeleteObject');
})->with(['jpg', 'pdf']);

it('preserves the legacy certificate when the copy fails', function () {
    $registration = Registration::factory()->create(['certificate_code' => 'acl-failed-copy']);
    $source = 'certificates/'.$registration->seminar->scheduled_at->year.'/'.$registration->seminar->slug.'/acl-failed-copy.jpg';
    $objects = [$source => 'original certificate'];
    $operations = [];
    useCertificateS3Bucket($objects, $operations, 'CopyObject');

    expect(fn () => app(CertificateService::class)->jpgExists($registration, fresh: true))
        ->toThrow(UnableToCopyFile::class);

    expect($operations)->toContain('CopyObject')->not->toContain('DeleteObject');
    expect($objects)->toBe([$source => 'original certificate']);
    expect(Cache::get('certificate_exists_jpg:acl-failed-copy'))->not->toBeTrue();
});

it('honors the configured S3 root when migrating certificates', function () {
    $registration = Registration::factory()->create(['certificate_code' => 'prefixed-code']);
    $source = 'tenant/certificates/'.$registration->seminar->scheduled_at->year.'/'.$registration->seminar->slug.'/prefixed-code.jpg';
    $objects = [$source => 'original certificate'];
    $operations = [];
    useCertificateS3Bucket($objects, $operations, root: 'tenant');

    expect(app(CertificateService::class)->jpgExists($registration, fresh: true))->toBeTrue();
    expect($objects)->toBe([
        $source => 'original certificate',
        'tenant/certificates/prefixed-code.jpg' => 'original certificate',
    ]);
});

it('copies successfully when deleting objects is forbidden', function () {
    $registration = Registration::factory()->create(['certificate_code' => 'failed-delete']);
    $source = 'certificates/'.$registration->seminar->scheduled_at->year.'/'.$registration->seminar->slug.'/failed-delete.jpg';
    $objects = [$source => 'original certificate'];
    $operations = [];
    useCertificateS3Bucket($objects, $operations, 'DeleteObject');

    expect(app(CertificateService::class)->jpgExists($registration, fresh: true))->toBeTrue();
    expect($operations)->not->toContain('DeleteObject');

    expect($objects)->toBe([
        $source => 'original certificate',
        'certificates/failed-delete.jpg' => 'original certificate',
    ]);
    expect(Cache::get('certificate_exists_jpg:failed-delete'))->toBeTrue();
});

it('does not mark a certificate as uploaded when S3 denies the write', function () {
    $registration = Registration::factory()->create(['certificate_code' => 'denied-upload']);
    $objects = [];
    $operations = [];
    useCertificateS3Bucket($objects, $operations, 'PutObject');

    expect(fn () => app(CertificateService::class)->generateJpg($registration))
        ->toThrow(UnableToWriteFile::class);

    expect($objects)->toBeEmpty();
    expect(Cache::get('certificate_exists_jpg:denied-upload'))->not->toBeTrue();
});
