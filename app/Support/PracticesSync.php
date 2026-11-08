<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Keeps the practice lines in the docs guides in step with BEST_PRACTICES.md.
 *
 * BEST_PRACTICES.md is the truth for each practice: a `### W<n>. <title>`
 * heading followed by one-line bullets `- **Practice:** ...`, `- **Why:** ...`
 * and `- **Stack:** ...`, ending at the next line starting with `### ` or
 * `## ` or the end of the file. Each guide in docs/guides repeats the same
 * practices as `## W<n>. <title>` sections with paragraph lines
 * `**Practice:** ...`, `**Why:** ...` and `**Stack:** ...`. sync() rewrites
 * those paragraph lines from the parsed practices and keeps every other byte,
 * including line endings.
 */
final class PracticesSync
{
    /**
     * The BEST_PRACTICES text as a map of practice id to the label lines it
     * carries, in file order and only for the labels present.
     *
     * @return array<string, array<string, string>> e.g. ['W19' => ['Practice' => '...', 'Why' => '...', 'Stack' => '...']]
     */
    public static function practices(string $bestPractices): array
    {
        $out = [];
        $current = null;

        foreach (self::lines($bestPractices) as [$line]) {
            if (preg_match('/^### W(\d+)\. /', $line, $m) === 1) {
                $current = 'W'.$m[1];
                $out[$current] ??= [];

                continue;
            }
            if (str_starts_with($line, '### ') || str_starts_with($line, '## ')) {
                $current = null;

                continue;
            }
            if ($current !== null && preg_match('/^- \*\*(Practice|Why|Stack):\*\* (.*)$/', $line, $m) === 1) {
                $out[$current][$m[1]] = $m[2];
            }
        }

        return $out;
    }

    /**
     * The guide text with, inside every `## W<n>.` section whose id is in
     * $practices, each `**Practice:**`, `**Why:**` and `**Stack:**` line
     * replaced by the same label and the BEST_PRACTICES text. A label the
     * section does not have is not added, a section whose id is not in
     * $practices is left alone, and every other line is kept byte for byte,
     * including line endings and the heading titles.
     *
     * @param  array<string, array<string, string>>  $practices
     */
    public static function sync(string $guide, array $practices): string
    {
        $out = '';
        $current = null;

        foreach (self::lines($guide) as [$body, $ending]) {
            if (preg_match('/^## W(\d+)\. /', $body, $m) === 1) {
                $current = 'W'.$m[1];

                $out .= $body.$ending;

                continue;
            }
            if (str_starts_with($body, '## ')) {
                $current = null;

                $out .= $body.$ending;

                continue;
            }
            if ($current !== null
                && preg_match('/^\*\*(Practice|Why|Stack):\*\* (.*)$/', $body, $m) === 1
                && isset($practices[$current][$m[1]])) {
                $body = '**'.$m[1].':** '.$practices[$current][$m[1]];
            }

            $out .= $body.$ending;
        }

        return $out;
    }

    /**
     * The text as [line body, line ending] pairs, keeping every byte: the
     * ending is "\n", "\r\n" or '' (a final line without one).
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function lines(string $text): array
    {
        $out = [];
        foreach (preg_split('/(?<=\n)/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $piece) {
            $ending = '';
            if (str_ends_with($piece, "\n")) {
                $ending = "\n";
                $piece = substr($piece, 0, -1);
                if (str_ends_with($piece, "\r")) {
                    $ending = "\r\n";
                    $piece = substr($piece, 0, -1);
                }
            }
            $out[] = [$piece, $ending];
        }

        return $out;
    }
}
