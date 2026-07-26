<?php

declare(strict_types=1);

namespace Spora\Maker\Maker;

use Spora\Maker\AbstractMaker;
use Spora\Maker\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Scaffolds a Skill bundle under `skills/<name>/`.
 *
 *   php bin/spora make:skill git
 *
 * Creates skills/git/SKILL.md (entry point with frontmatter + body stub) and
 * skills/git/examples.md (optional sidecar). The slug must match the
 * agentskills.io name pattern enforced by {@see \Spora\Core\Skills\SkillValidator}:
 * lowercase alphanumeric + hyphens, no leading/trailing hyphen, no `--`,
 * 1-64 chars, and the directory name must equal the frontmatter `name`.
 */
final class MakeSkill extends AbstractMaker
{
    protected const COMMAND_NAME = 'make:skill';
    protected const COMMAND_DESCRIPTION = 'Create a new Skill under skills/<name>/';
    protected const COMMAND_ARG_HELP = 'The skill slug (lowercase, alphanumeric + hyphens, 1-64 chars).';

    /**
     * Mirrors {@see \Spora\Core\Skills\SkillValidator::NAME_PATTERN}. Duplicated
     * here so spora-maker stays self-contained and doesn't depend on
     * spora-core at runtime — the scaffolder runs against a project, not
     * inside one.
     */
    private const NAME_PATTERN = '/^(?![a-z0-9-]*--)[a-z0-9]([a-z0-9-]{0,62}[a-z0-9])?$/';

    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');

        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            throw new \RuntimeException(sprintf(
                'Skill name "%s" is not a valid agentskills.io slug. '
                . 'Use 1-64 lowercase alphanumeric chars and hyphens, no leading/trailing hyphen, no consecutive hyphens.',
                $name,
            ));
        }

        $generator->generateFile(
            'skills/' . $name . '/SKILL.md',
            $this->skillTemplate($name),
        );
        $generator->generateFile(
            'skills/' . $name . '/examples.md',
            $this->examplesTemplate($name),
        );

        $io->note(sprintf(
            "Don't forget to enable the skill tool on the agent:\n"
            . '  1. Settings -> Tools -> Skill -> allowed_skills: add %1$s\n'
            . '  2. Edit skills/%1$s/SKILL.md — replace the TODO frontmatter description with a real one.',
            $name,
        ));
    }

    public function getSuccessMessage(): string
    {
        return 'Skill scaffolded. Next: fill in the frontmatter description and write the SKILL.md body.';
    }

    private function skillTemplate(string $name): string
    {
        return <<<YAML
            ---
            name: {$name}
            description: "TODO: describe what this skill does and when to use it. 1-1024 chars."
            license: Apache-2.0
            ---

            # TODO: skill title

            TODO: write the body. The Agent reads this when it calls skill_read on SKILL.md.

            ## When to use this skill

            TODO: describe the trigger conditions.

            ## Steps

            TODO: document the procedure the Agent should follow.

            YAML;
    }

    private function examplesTemplate(string $name): string
    {
        return <<<MD
            # Examples

            TODO: add worked examples the Agent can read on demand via the
            Skill tool's `files` + `read` operations.

            MD;
    }
}
