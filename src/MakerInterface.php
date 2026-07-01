<?php

declare(strict_types=1);

namespace Spora\Maker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contract every `spora make:*` command implements.
 *
 * Mirrors symfony/maker-bundle's AbstractMaker pattern: the command
 * is responsible for prompting the user and gathering parameters;
 * the shared {@see Generator} is responsible for writing files safely.
 */
interface MakerInterface
{
    /**
     * Generate files for this maker.
     *
     * Use $generator->generateClass(), ->generateFile() etc. to queue
     * writes — actual disk I/O happens once ->writeChanges() is called.
     * FileManager will throw RuntimeCommandException if a target exists.
     */
    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void;

    /**
     * Return the human-readable success message printed after the files
     * are written (e.g. "Next: open the new Tool class and add parameters").
     */
    public function getSuccessMessage(): string;
}
