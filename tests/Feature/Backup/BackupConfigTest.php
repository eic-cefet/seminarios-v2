<?php

use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\DbDumper\Compressors\GzipCompressor;

describe('backup configuration', function () {
    it('backs up only the mysql database', function () {
        expect(config('backup.backup.source.databases'))->toBe(['mysql']);
    });

    it('includes no files, only the database dump', function () {
        expect(config('backup.backup.source.files.include'))->toBe([]);
    });

    it('gzip-compresses the database dump', function () {
        expect(config('backup.backup.database_dump_compressor'))->toBe(GzipCompressor::class);
    });

    it('stores backups on the s3 disk', function () {
        expect(config('backup.backup.destination.disks'))->toBe(['s3']);
    });

    it('stores backups under the backups/database path', function () {
        expect(config('backup.backup.name'))->toBe('backups/database');
        expect(config('backup.monitor_backups.0.name'))->toBe('backups/database');
    });

    it('retains backups for 90 days', function () {
        expect(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(90);
    });

    it('emails on failures but not on successful runs', function () {
        $channels = config('backup.notifications.notifications');
        expect($channels[BackupHasFailedNotification::class])->toBe(['mail']);
        expect($channels[BackupWasSuccessfulNotification::class])->toBe([]);
    });
});
