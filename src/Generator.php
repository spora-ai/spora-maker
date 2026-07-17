<?php

declare(strict_types=1);

namespace Spora\Maker;

use Spora\Maker\Exception\DuplicateQueuedFileException;

/**
 * Queues file-emission operations and flushes them in one pass.
 *
 * Pattern lifted from symfony/maker-bundle/src/Generator.php:
 *   1. Maker calls generateClass() / generateFile() to queue operations.
 *   2. writeChanges() flushes the queue — FileManager refuses to overwrite.
 *
 * Templating is intentionally trivial: a maker supplies the rendered
 * contents directly. No Twig, no Smarty — keeps the scaffolder dependency-free.
 */
final class Generator
{
    /** @var array<string, array{relativePath: string, contents: string}> */
    private array $pending = [];

    public function __construct(
        private readonly FileManager $fileManager,
    ) {
    }

    /**
     * Queue a file write. $relativePath is project-relative (e.g. "app/Tools/Foo.php").
     * Throws if a file with the same relative path is already pending (duplicate queue).
     */
    public function generateFile(string $relativePath, string $contents): string
    {
        if (isset($this->pending[$relativePath])) {
            throw new DuplicateQueuedFileException(sprintf(
                'File "%s" was queued twice by the same maker.',
                $relativePath,
            ));
        }
        $this->pending[$relativePath] = [
            'relativePath' => $relativePath,
            'contents'     => $contents,
        ];
        return $relativePath;
    }

    /**
     * Flush queued operations to disk. Returns the list of relative paths written.
     *
     * @return list<string>
     */
    public function writeChanges(): array
    {
        $written = [];
        foreach ($this->pending as $entry) {
            $this->fileManager->dumpFile($entry['relativePath'], $entry['contents']);
            $written[] = $entry['relativePath'];
        }
        $this->pending = [];
        return $written;
    }

    /**
     * @return list<string>
     */
    public function getPendingFiles(): array
    {
        return array_keys($this->pending);
    }
}
