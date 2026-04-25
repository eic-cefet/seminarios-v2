<?php

use Symfony\Component\Finder\Finder;

it('forbids raw Cache::lock calls outside the Locking layer', function () {
    $finder = (new Finder)
        ->files()
        ->in(base_path('app'))
        ->name('*.php')
        ->notPath('Support/Locking');

    $offenders = [];
    foreach ($finder as $file) {
        if (preg_match('/Cache::\s*lock\s*\(/', $file->getContents())) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBeEmpty(
        'Use App\\Support\\Locking\\Mutex instead of Cache::lock(). Offenders: '
        .implode(', ', $offenders)
    );
});
