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
 *
 * A heading may separate the id with a period or a colon (`## W26: <title>`
 * reads the same as `## W26. <title>`). Before both were read, the five
 * security and four supply chain practices sat under colon headings, the
 * sync skipped them without a word, and their guides went stale. coverage()
 * is the guard against the next quiet skip: every practice must have exactly
 * one section, with a Stack line, somewhere in the guides.
 */
final class PracticesSync
{
    /** A practice heading in BEST_PRACTICES.md: `### W<n>.` or `### W<n>:`. */
    private const SOURCE_HEADING = '/^### W(\d+)[.:] /';

    /** A practice section heading in a guide: `## W<n>.` or `## W<n>:`. */
    private const GUIDE_HEADING = '/^## W(\d+)[.:] /';

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
            if (preg_match(self::SOURCE_HEADING, $line, $m) === 1) {
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
            if (preg_match(self::GUIDE_HEADING, $body, $m) === 1) {
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
     * The practice sections a guide carries, in file order: each id with
     * whether its section has a `**Stack:**` line. An id that appears twice
     * in one guide is listed twice.
     *
     * @return list<array{0: string, 1: bool}> e.g. [['W25', true], ['W26', false]]
     */
    public static function sections(string $guide): array
    {
        /** @var list<string> $ids */
        $ids = [];
        /** @var list<bool> $stacks */
        $stacks = [];
        $inSection = false;

        foreach (self::lines($guide) as [$body]) {
            if (preg_match(self::GUIDE_HEADING, $body, $m) === 1) {
                $ids[] = 'W'.$m[1];
                $stacks[] = false;
                $inSection = true;

                continue;
            }
            if (str_starts_with($body, '## ')) {
                $inSection = false;

                continue;
            }
            if ($inSection && str_starts_with($body, '**Stack:** ')) {
                array_pop($stacks);
                $stacks[] = true;
            }
        }

        $out = [];
        foreach ($ids as $i => $id) {
            $out[] = [$id, $stacks[$i]];
        }

        return $out;
    }

    /**
     * What keeps the guides from covering BEST_PRACTICES.md one for one, as
     * plain sentences (empty when they do): a practice with no section in
     * any guide, a practice with a section in more than one place, a section
     * whose id BEST_PRACTICES.md does not know, and a section without a
     * Stack line (the tag is the point of the section).
     *
     * @param  array<string, array<string, string>>  $practices  from practices()
     * @param  array<string, string>  $guides  guide path (as it should be printed) => guide text
     * @return list<string>
     */
    public static function coverage(array $practices, array $guides): array
    {
        $problems = [];
        /** @var array<string, list<string>> $seenIn */
        $seenIn = [];

        foreach ($guides as $path => $text) {
            foreach (self::sections($text) as [$id, $hasStack]) {
                $seenIn[$id][] = $path;
                if (! isset($practices[$id])) {
                    $problems[] = "{$path}: {$id} is not a practice in BEST_PRACTICES.md.";

                    continue;
                }
                if (! $hasStack) {
                    $problems[] = "{$path}: the {$id} section has no **Stack:** line.";
                }
            }
        }

        foreach (array_keys($practices) as $id) {
            $places = $seenIn[$id] ?? [];
            if ($places === []) {
                $problems[] = "{$id} is in BEST_PRACTICES.md, but no guide in docs/guides has a \"## {$id}.\" section.";
            } elseif (count($places) > 1) {
                $problems[] = "{$id} has more than one section: ".implode(', ', $places).'.';
            }
        }

        return $problems;
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
