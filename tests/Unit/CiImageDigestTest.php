<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every image line in .gitlab-ci.yml is pinned by a digest, except the PHP
 * matrix image, whose tag is built from the PHP_VERSION variable. Extends
 * PHPUnit's own TestCase, not the framework one, because the check is plain
 * PHP reading a file.
 */
class CiImageDigestTest extends TestCase
{
    public function test_every_image_line_is_pinned_by_a_digest(): void
    {
        $path = dirname(__DIR__, 2).'/.gitlab-ci.yml';
        $this->assertFileExists($path);

        foreach (explode("\n", (string) file_get_contents($path)) as $line) {
            $trimmed = trim($line);
            if (! str_starts_with($trimmed, 'image:')) {
                continue;
            }

            $value = trim(substr($trimmed, strlen('image:')));
            // The PHP matrix image is a variable, not a pin.
            if (str_starts_with($value, '"php:${')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/@sha256:[0-9a-f]{64}(?![0-9a-fA-F])/',
                $value,
                'image line is not pinned by a digest: '.$trimmed,
            );
        }
    }
}
