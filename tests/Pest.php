<?php

declare(strict_types=1);

/*
 * Pest entry point.
 * Test cases live in tests/Unit/* and tests/Feature/* alongside this file.
 *
 * Global beforeEach/afterEach allocate a fresh tmp project dir, register
 * a FileManager + Generator on the current test instance, and recursively
 * delete the dir on tear-down. Each test file no longer needs to repeat
 * `beforeEach` / `afterEach` boilerplate — it just consumes
 * `$this->tmpDir`, `$this->fm`, `$this->generator`.
 *
 * `generateInto()` invokes a maker's `generate()` against a stub
 * input/output without flushing, so individual tests can assert on the
 * queued writes before ->writeChanges() runs.
 */

use Spora\Maker\FileManager;
use Spora\Maker\Generator;
use Spora\Maker\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

pest()->beforeEach(function (): void {
    $tmpDir = sys_get_temp_dir() . '/spora-maker-' . bin2hex(random_bytes(4));
    mkdir($tmpDir, 0755, true);
    /** @var object{tmpDir?: string, fm?: FileManager, generator?: Generator} $this */
    $this->tmpDir = $tmpDir;
    $this->fm = new FileManager($tmpDir);
    $this->generator = new Generator($this->fm);
});

pest()->afterEach(function (): void {
    /** @var object{tmpDir?: string} $this */
    if (!isset($this->tmpDir) || !is_dir($this->tmpDir)) {
        return;
    }
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($rii as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($this->tmpDir);
});

/**
 * Invoke a maker's generate() directly against the test Generator, WITHOUT
 * flushing. Tests then call $generator->writeChanges() explicitly to assert
 * what was written and when. This avoids the auto-flush that
 * MakerRunner::execute() does in production.
 *
 * Returns the maker's buffered output for further assertions.
 */
function generateInto(MakerInterface $maker, array $args, Generator $generator): string
{
    $output = new BufferedOutput();
    $input = new ArrayInput($args);
    if ($maker instanceof Command) {
        $input->bind($maker->getDefinition());
    }
    $maker->generate($input, $output, $generator);
    return $output->fetch();
}
