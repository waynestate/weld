<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PracticesSync;
use PHPUnit\Framework\TestCase;

/**
 * PracticesSync against inline fixtures: a slice of BEST_PRACTICES.md and a
 * guide that repeats its practices. Extends PHPUnit's own TestCase, not the
 * framework one, because the class under test is plain PHP.
 */
class PracticesSyncTest extends TestCase
{
    public function test_it_parses_the_practice_sections(): void
    {
        $bestPractices = <<<'MD'
        # WELD best practices

        ## What this covers

        Some intro text.

        ### W18. Database queue by default
        - **Practice:** Use the database connection and run the worker as a process type.
        - **Why:** It needs no extra service.
        - **Stack:** `Documented`. `.env.example` sets `QUEUE_CONNECTION=database`.

        ### W19. Run the queue from the scheduler
        - **Practice:** Schedule `queue:work` every minute.
        - **Why:** The scheduler is the one cron entry the platform can run.
        - **Stack:** `Checked`. A test shows the entry under the citadel preset.

        ### W20. Timeout below retry_after
        - **Practice:** Set each job timeout lower than `retry_after`.
        - **Why:** Otherwise the job is attempted again before it has finished.

        ### Not a practice
        - **Practice:** Ignored, this heading has no W id.

        ## What it does not cover

        More intro text.
        MD;

        $this->assertSame([
            'W18' => [
                'Practice' => 'Use the database connection and run the worker as a process type.',
                'Why' => 'It needs no extra service.',
                'Stack' => '`Documented`. `.env.example` sets `QUEUE_CONNECTION=database`.',
            ],
            'W19' => [
                'Practice' => 'Schedule `queue:work` every minute.',
                'Why' => 'The scheduler is the one cron entry the platform can run.',
                'Stack' => '`Checked`. A test shows the entry under the citadel preset.',
            ],
            'W20' => [
                'Practice' => 'Set each job timeout lower than `retry_after`.',
                'Why' => 'Otherwise the job is attempted again before it has finished.',
            ],
        ], PracticesSync::practices($bestPractices));
    }

    public function test_a_stale_stack_line_is_replaced(): void
    {
        $practices = [
            'W19' => [
                'Practice' => 'Schedule `queue:work` every minute.',
                'Why' => 'The scheduler is the one cron entry the platform can run.',
                'Stack' => '`Checked`. A test shows the entry under the citadel preset.',
            ],
        ];
        $guide = <<<'MD'
        # Queues and scheduling

        ## W19. Run the queue from the scheduler

        **Practice:** Schedule `queue:work` every minute.

        **Why:** The scheduler is the one cron entry the platform can run.

        **Stack:** `Not yet`. `routes/console.php` has no scheduler entries yet.

        ## Related

        - [App structure](./app-structure.md)
        MD;
        $expected = <<<'MD'
        # Queues and scheduling

        ## W19. Run the queue from the scheduler

        **Practice:** Schedule `queue:work` every minute.

        **Why:** The scheduler is the one cron entry the platform can run.

        **Stack:** `Checked`. A test shows the entry under the citadel preset.

        ## Related

        - [App structure](./app-structure.md)
        MD;

        $this->assertSame($expected, PracticesSync::sync($guide, $practices));
    }

    public function test_a_label_the_guide_lacks_is_not_added(): void
    {
        $practices = [
            'W30' => [
                'Practice' => 'Support only PHP versions that are not end of life.',
                'Why' => 'End of life means no security fixes.',
                'Stack' => '`Enforced` by composer.json.',
            ],
        ];
        $guide = "## W30. Support only PHP versions that are not end of life\n\n**Practice:** stale\n\n**Stack:** stale\n\n## Related\n";

        $this->assertSame(
            "## W30. Support only PHP versions that are not end of life\n\n**Practice:** Support only PHP versions that are not end of life.\n\n**Stack:** `Enforced` by composer.json.\n\n## Related\n",
            PracticesSync::sync($guide, $practices),
        );
    }

    public function test_an_unknown_section_and_the_text_around_are_unchanged(): void
    {
        $practices = [
            'W1' => [
                'Practice' => 'new practice text',
                'Why' => 'new why text',
                'Stack' => 'new stack text',
            ],
        ];
        $guide = <<<'MD'
        ---
        title: App structure
        ---

        # App structure

        Intro paragraph.

        ## W1. The app is a Laravel app

        **Practice:** stale practice

        **Why:** stale why

        **Stack:** stale stack

        ## W99. A practice BEST_PRACTICES does not know

        **Practice:** left alone

        **Why:** left alone

        **Stack:** left alone

        ## How to read the tags

        Closing paragraph.
        MD;
        $expected = <<<'MD'
        ---
        title: App structure
        ---

        # App structure

        Intro paragraph.

        ## W1. The app is a Laravel app

        **Practice:** new practice text

        **Why:** new why text

        **Stack:** new stack text

        ## W99. A practice BEST_PRACTICES does not know

        **Practice:** left alone

        **Why:** left alone

        **Stack:** left alone

        ## How to read the tags

        Closing paragraph.
        MD;

        $this->assertSame($expected, PracticesSync::sync($guide, $practices));
    }

    public function test_crlf_input_keeps_crlf(): void
    {
        $practices = [
            'W19' => [
                'Stack' => '`Checked`. The release script lists the commands.',
            ],
        ];
        $guide = "# Queues\r\n\r\n## W19. Run the queue from the scheduler\r\n\r\n**Practice:** schedule it\r\n\r\n**Stack:** `Not yet`. Nothing yet.\r\n\r\n## Related\r\n";

        $this->assertSame(
            "# Queues\r\n\r\n## W19. Run the queue from the scheduler\r\n\r\n**Practice:** schedule it\r\n\r\n**Stack:** `Checked`. The release script lists the commands.\r\n\r\n## Related\r\n",
            PracticesSync::sync($guide, $practices),
        );
    }
}
