<?php

declare(strict_types=1);

namespace Spora\Maker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Decorator that injects the shared Generator into a maker command and
 * flushes pending writes after the maker's generate() returns.
 *
 * Also handles the success message and error reporting uniformly so each
 * maker implementation only has to focus on prompting + queueing.
 */
final class MakerRunner extends Command
{
    public function __construct(
        private readonly Command $inner,
        private readonly Generator $generator,
    ) {
        parent::__construct($inner->getName() ?? 'maker');
        $this->setDescription($inner->getDescription());

        // Mirror the inner command's argument definitions so `php bin/spora
        // make:tool Foo` still prompts for "name".
        $this->setDefinition($inner->getDefinition());
        $this->setHelp((string) $inner->getHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->inner instanceof MakerInterface) {
            $output->writeln('<error>Inner command is not a MakerInterface.</error>');
            return Command::FAILURE;
        }

        try {
            $this->inner->generate($input, $output, $this->generator);
            $written = $this->generator->writeChanges();
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        if ($written !== []) {
            $io->success(sprintf('Created %d file(s):', count($written)));
            foreach ($written as $path) {
                $io->writeln('  • ' . $path);
            }
        }
        $io->writeln($this->inner->getSuccessMessage());

        return Command::SUCCESS;
    }
}
