<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pest is the default for new tests (see README, Testing).
 * Existing PHPUnit class tests keep running unchanged.
 */
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
