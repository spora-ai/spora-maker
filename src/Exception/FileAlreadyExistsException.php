<?php

declare(strict_types=1);

namespace Spora\Maker\Exception;

use RuntimeException;

/**
 * Thrown when FileManager::dumpFile() refuses to overwrite a file that
 * already exists on disk. Surfaces as a RuntimeException to callers so the
 * MakerRunner can render it as a user-facing CLI error.
 */
final class FileAlreadyExistsException extends RuntimeException
{
}
