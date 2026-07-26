<?php

declare(strict_types=1);

namespace Spora\Maker\Exception;

use InvalidArgumentException;

/**
 * Thrown by {@see \Spora\Maker\Maker\MakeSkill::generate()} when the
 * supplied skill slug does not match the agentskills.io name pattern.
 * Surfaces as an InvalidArgumentException to callers so the
 * MakerRunner can render it as a user-facing CLI error.
 */
final class InvalidSkillNameException extends InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self(sprintf(
            'Skill name "%s" is not a valid agentskills.io slug. '
            . 'Use 1-64 lowercase alphanumeric chars and hyphens, no leading/trailing hyphen, no consecutive hyphens.',
            $name,
        ));
    }
}
