<?php

declare(strict_types=1);

namespace Spora\Maker\Exception;

use RuntimeException;

/**
 * Thrown when Generator::generateFile() is called twice for the same
 * relative path within a single make invocation. Indicates a bug in the
 * maker — a single run should never queue the same path twice.
 */
final class DuplicateQueuedFileException extends RuntimeException
{
}
