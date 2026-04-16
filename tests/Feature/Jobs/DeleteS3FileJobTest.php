<?php

use App\Jobs\DeleteS3FileJob;
use Illuminate\Support\Facades\Storage;

it('deletes the file from s3 and records an audit log', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('reports/test-report.xlsx', 'content');

    expect(Storage::disk('s3')->exists('reports/test-report.xlsx'))->toBeTrue();

    $job = new DeleteS3FileJob('reports/test-report.xlsx');
    $job->handle();

    expect(Storage::disk('s3')->exists('reports/test-report.xlsx'))->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event_name' => 's3.file_deleted',
        'event_type' => 'system',
    ]);
});

it('can delete from a custom disk', function () {
    Storage::fake('local');
    Storage::disk('local')->put('tmp/file.txt', 'data');

    $job = new DeleteS3FileJob('tmp/file.txt', 'local');
    $job->handle();

    expect(Storage::disk('local')->exists('tmp/file.txt'))->toBeFalse();
});
