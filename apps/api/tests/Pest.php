<?php

use Tests\TestCase;

// Bind the Laravel TestCase so config()/response() helpers work in both suites.
uses(TestCase::class)->in('Feature', 'Unit');
