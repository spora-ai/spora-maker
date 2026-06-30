<?php

declare(strict_types=1);

/*
 * Pest entry point.
 * Test cases live in tests/Unit/* and tests/Feature/* alongside this file.
 */

use Spora\Maker\MakerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Invoke a maker's generate() directly against the test Generator, WITHOUT
 * flushing. Tests then call $generator->writeChanges() explicitly to assert
 * what was written and when. This avoids the auto-flush that
 * MakerRunner::execute() does in production.
 *
 * Returns the maker's buffered output for further assertions.
 */
function generateInto(MakerInterface $maker, array $args, \Spora\Maker\Generator $generator): string
{
    $output = new BufferedOutput();
    $input = new ArrayInput($args);
    if ($maker instanceof \Symfony\Component\Console\Command\Command) {
        $input->bind($maker->getDefinition());
    }
    $maker->generate($input, $output, $generator);
    return $output->fetch();
}
