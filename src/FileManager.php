<?php

declare(strict_types=1);

namespace Spora\Maker;

use Spora\Maker\Exception\FileAlreadyExistsException;

/**
 * Filesystem safety net for the scaffolder.
 *
 * - Refuses to overwrite an existing file (throws FileAlreadyExistsException).
 * - Creates intermediate directories lazily.
 * - Centralises path resolution so templates stay path-agnostic.
 *
 * Borrowed shape from symfony/maker-bundle's FileManager, scoped down to
 * what Spora's app/ scaffolder actually needs.
 */
final class FileManager
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    /**
     * Absolute path for a target file under the project root.
     * Always returns the canonicalised project-relative path with leading slash stripped.
     */
    public function absolutePath(string $relativePath): string
    {
        $clean = ltrim($relativePath, '/');
        return $this->projectDir . '/' . $clean;
    }

    public function fileExists(string $relativePath): bool
    {
        return is_file($this->absolutePath($relativePath));
    }

    public function ensureDirectory(string $relativePath): void
    {
        $abs = $this->absolutePath($relativePath);
        if (!is_dir($abs)) {
            mkdir($abs, 0755, true);
        }
    }

    public function dumpFile(string $relativePath, string $contents): void
    {
        $abs = $this->absolutePath($relativePath);
        if (is_file($abs)) {
            throw new FileAlreadyExistsException(sprintf(
                'The file "%s" cannot be generated because it already exists. '
                . 'Delete it first or pick a different name.',
                $relativePath,
            ));
        }
        $this->ensureDirectory(dirname($relativePath));
        file_put_contents($abs, $contents);
    }
}
