<?php

use App\Services\EnvFileWriter;

beforeEach(function () {
    $this->envDir = sys_get_temp_dir().'/env-writer-test-'.uniqid();
    mkdir($this->envDir, 0755, true);
    $this->envPath = $this->envDir.'/.env';
});

afterEach(function () {
    @chmod($this->envDir, 0755);
    @chmod($this->envPath, 0644);
    @unlink($this->envPath);
    @unlink($this->envPath.'.tmp');
    @rmdir($this->envDir);
});

it('replaces an existing key in place, preserving surrounding lines', function () {
    file_put_contents($this->envPath, "APP_NAME=Seminarios\nAWS_ENV_SECRET_ID=old\n# comment\n");

    (new EnvFileWriter($this->envPath))->setValues(['AWS_ENV_SECRET_ID' => 'new-secret']);

    expect(file_get_contents($this->envPath))
        ->toBe("APP_NAME=Seminarios\nAWS_ENV_SECRET_ID=new-secret\n# comment\n");
});

it('appends a missing key at the end', function () {
    file_put_contents($this->envPath, "APP_NAME=Seminarios\n");

    (new EnvFileWriter($this->envPath))->setValues(['AWS_ENV_SECRET_ID' => 'my-secret']);

    expect(file_get_contents($this->envPath))
        ->toBe("APP_NAME=Seminarios\nAWS_ENV_SECRET_ID=my-secret\n");
});

it('adds a newline separator when the file lacks a trailing newline', function () {
    file_put_contents($this->envPath, 'APP_NAME=Seminarios');

    (new EnvFileWriter($this->envPath))->setValues(['NEW_KEY' => 'value']);

    expect(file_get_contents($this->envPath))
        ->toBe("APP_NAME=Seminarios\nNEW_KEY=value\n");
});

it('removes all lines for a key when the value is null', function () {
    file_put_contents($this->envPath, "A=1\nDUP=x\nB=2\nDUP=y\n");

    (new EnvFileWriter($this->envPath))->setValues(['DUP' => null]);

    expect(file_get_contents($this->envPath))->toBe("A=1\nB=2\n");
});

it('removing a missing key is a no-op', function () {
    file_put_contents($this->envPath, "A=1\n");

    (new EnvFileWriter($this->envPath))->setValues(['MISSING' => null]);

    expect(file_get_contents($this->envPath))->toBe("A=1\n");
});

it('replaces only the first occurrence when duplicates exist', function () {
    file_put_contents($this->envPath, "DUP=x\nDUP=y\n");

    (new EnvFileWriter($this->envPath))->setValues(['DUP' => 'z']);

    expect(file_get_contents($this->envPath))->toBe("DUP=z\nDUP=y\n");
});

it('creates the file when missing', function () {
    (new EnvFileWriter($this->envPath))->setValues(['AWS_ENV_SECRET_ID' => 'my-secret']);

    expect(file_get_contents($this->envPath))->toBe("AWS_ENV_SECRET_ID=my-secret\n");
});

it('applies multiple keys in one call, mixing set and remove', function () {
    file_put_contents($this->envPath, "A=1\nB=2\n");

    (new EnvFileWriter($this->envPath))->setValues(['A' => 'updated', 'B' => null, 'C' => '3']);

    expect(file_get_contents($this->envPath))->toBe("A=updated\nC=3\n");
});

it('writes bare values for the safe character set', function (string $value) {
    (new EnvFileWriter($this->envPath))->setValues(['KEY' => $value]);

    expect(file_get_contents($this->envPath))->toBe("KEY={$value}\n");
})->with([
    'arn' => 'arn:aws:secretsmanager:us-east-1:123456789012:secret:app/env-abc123',
    'base64ish' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY=',
]);

it('single-quotes values containing characters outside the safe set', function () {
    (new EnvFileWriter($this->envPath))->setValues(['KEY' => 'has space and $dollar\\slash']);

    expect(file_get_contents($this->envPath))->toBe("KEY='has space and \$dollar\\slash'\n");
});

it('rejects values containing single quotes', function () {
    (new EnvFileWriter($this->envPath))->setValues(['KEY' => "it's bad"]);
})->throws(RuntimeException::class, 'single quote');

it('rejects values containing newlines', function (string $value) {
    (new EnvFileWriter($this->envPath))->setValues(['KEY' => $value]);
})->with([
    'trailing line feed' => "value\n",
    'embedded line feed' => "line1\nline2",
    'carriage return' => "value\rmore",
])->throws(RuntimeException::class, 'newline');

it('escapes replacement metacharacters when replacing an existing key', function () {
    file_put_contents($this->envPath, "BEFORE=1\nKEY=old\nAFTER=2\n");

    (new EnvFileWriter($this->envPath))->setValues(['KEY' => 'has space and $dollar\\slash']);

    expect(file_get_contents($this->envPath))
        ->toBe("BEFORE=1\nKEY='has space and \$dollar\\slash'\nAFTER=2\n");
});

it('throws when the env file exists but is unreadable', function () {
    file_put_contents($this->envPath, "A=1\n");
    chmod($this->envPath, 0000);

    expect(fn () => (new EnvFileWriter($this->envPath))->setValues(['KEY' => 'value']))
        ->toThrow(RuntimeException::class, 'Unable to read');
});

it('throws and leaves no temp file when the target directory is unwritable', function () {
    file_put_contents($this->envPath, "A=1\n");
    chmod($this->envDir, 0555);

    expect(fn () => (new EnvFileWriter($this->envPath))->setValues(['KEY' => 'value']))
        ->toThrow(RuntimeException::class, 'Unable to write');

    expect(is_file($this->envPath.'.tmp'))->toBeFalse();
});

it('leaves no temp file behind', function () {
    (new EnvFileWriter($this->envPath))->setValues(['KEY' => 'value']);

    expect(is_file($this->envPath.'.tmp'))->toBeFalse();
});

it('resolves from the container pointed at base_path(.env)', function () {
    expect(app(EnvFileWriter::class))->toBeInstanceOf(EnvFileWriter::class);
});
