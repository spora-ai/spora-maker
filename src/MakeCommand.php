<?php

declare(strict_types=1);

namespace Spora\Maker;

use Spora\Maker\Maker\MakeApp;
use Spora\Maker\Maker\MakeController;
use Spora\Maker\Maker\MakeTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Aggregator command that hosts every `make:*` subcommand under a single
 * `php bin/spora make` umbrella. This is the only class spora-core's
 * console kernel needs to know about — MakerInterface implementations
 * self-register via AsCommand attributes.
 *
 * The list below is intentionally hardcoded (not discovered) so the entry
 * points are grep-able and the scaffolder stays trivial. New makers get
 * added by appending to the array.
 */
#[AsCommand(
    name: 'make',
    description: 'Scaffold project-local code (Tools, Controllers, App entry-point).',
)]
final class MakeCommand extends Command
{
    /** @var list<class-string<AbstractMaker>> */
    private const MAKERS = [
        MakeTool::class,
        MakeController::class,
        MakeApp::class,
    ];

    protected function configure(): void
    {
        $this
            ->setName('make')
            ->setDescription('Scaffold project-local code.')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command hosts every scaffolder.

                Usage:
                  <info>%command.name% <name></info>

                Available subcommands:
                  make:tool <Name>        Create app/Tools/<Name>Tool.php
                  make:controller <Name>  Create app/Http/Controllers/<Name>Controller.php
                  make:app                Recreate app/App.php

                Run any subcommand directly with `php bin/spora make:tool Foo`.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Available makers');

        $rows = [];
        foreach (self::MAKERS as $makerClass) {
            $cmd = new $makerClass();
            $rows[] = [$cmd->getName(), $cmd->getDescription()];
        }
        $io->table(['Command', 'Description'], $rows);
        $io->writeln('');
        $io->writeln('Run a specific maker with `php bin/spora <command> <name>`.');
        return Command::SUCCESS;
    }

    /**
     * Helper used by the spora-core console kernel to instantiate every maker
     * with a shared Generator/FileManager pair.
     *
     * @return list<Command>
     */
    public static function buildMakers(string $projectDir): array
    {
        $fileManager = new FileManager($projectDir);
        $generator   = new Generator($fileManager);

        $makers = [];
        foreach (self::MAKERS as $makerClass) {
            $maker = new $makerClass();
            // Wire the shared Generator into the command by re-creating it
            // with a closure that injects on each `execute()`. We achieve this
            // by wrapping the original command via a small decorator below.
            $makers[] = new MakerRunner($maker, $generator);
        }
        return $makers;
    }
}
