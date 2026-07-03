<?php

namespace App\Services;

use RuntimeException;

/**
 * Minimal .env line editor used by the env-secrets admin setup screen.
 * Replaces or appends `KEY=value` lines in place, removes keys on null, and
 * writes atomically (temp file + rename) so a concurrently booting process
 * never reads a half-written file.
 */
class EnvFileWriter
{
    private const BARE_VALUE_PATTERN = '/^[A-Za-z0-9_@:+=,.\/-]*$/D';

    public function __construct(private string $path) {}

    /**
     * @param  array<string, string|null>  $values  null removes the key
     */
    public function setValues(array $values): void
    {
        $contents = is_file($this->path) ? @file_get_contents($this->path) : '';

        if ($contents === false) {
            throw new RuntimeException("Unable to read {$this->path}.");
        }

        foreach ($values as $key => $value) {
            $contents = $this->setValue($contents, (string) $key, $value);
        }

        $temp = $this->path.'.tmp';

        if (@file_put_contents($temp, $contents) !== strlen($contents) || ! @rename($temp, $this->path)) {
            @unlink($temp);

            throw new RuntimeException("Unable to write {$this->path}.");
        }
    }

    private function setValue(string $contents, string $key, ?string $value): string
    {
        $pattern = '/^'.preg_quote($key, '/').'=.*(?:\R|$)/m';

        if ($value === null) {
            return preg_replace($pattern, '', $contents);
        }

        $line = $key.'='.$this->quote($value)."\n";

        if (preg_match($pattern, $contents) === 1) {
            return preg_replace($pattern, addcslashes($line, '$\\'), $contents, 1);
        }

        if ($contents !== '' && ! str_ends_with($contents, "\n")) {
            $contents .= "\n";
        }

        return $contents.$line;
    }

    private function quote(string $value): string
    {
        if (str_contains($value, "'")) {
            throw new RuntimeException('Env values containing a single quote are not supported.');
        }

        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw new RuntimeException('Env values containing a newline are not supported.');
        }

        if (preg_match(self::BARE_VALUE_PATTERN, $value) === 1) {
            return $value;
        }

        return "'{$value}'";
    }
}
