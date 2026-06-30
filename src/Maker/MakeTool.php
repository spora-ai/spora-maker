<?php

declare(strict_types=1);

namespace Spora\Maker\Maker;

use Spora\Maker\Generator;
use Spora\Maker\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Scaffolds a Tool class under `app/Tools/<Name>.php`.
 *
 *   php bin/spora make:tool WebSearch
 *
 * Creates app/Tools/WebSearchTool.php (the "Tool" suffix is appended automatically
 * if not already present) using the AbstractTool + #[Tool] attribute pattern from
 * spora-core, ready for the developer to fill in execute() / parameters.
 */
final class MakeTool extends Command implements MakerInterface
{
    public function __construct()
    {
        parent::__construct('make:tool');
        $this->setDescription('Create a new Tool class under app/Tools/');
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'The tool class basename (without "Tool" suffix).',
        );
    }

    protected function configure(): void
    {
        // Argument added in constructor so new MakeTool()->getDefinition()
        // has 'name' available without requiring Application::add() to call
        // configure() first (matters for direct test invocation).
    }

    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void
    {
        $io = new SymfonyStyle($input, $output);
        $baseName = ucfirst((string) $input->getArgument('name'));
        $className = str_ends_with($baseName, 'Tool') ? $baseName : $baseName . 'Tool';
        $snakeName = $this->toSnakeCase($className);

        $contents = <<<PHP
            <?php

            declare(strict_types=1);

            namespace App\\Tools;

            use Spora\\Tools\\AbstractTool;
            use Spora\\Tools\\Attributes\\Tool;
            use Spora\\Tools\\Attributes\\ToolParameter;
            use Spora\\Tools\\ValueObjects\\ToolResult;

            #[Tool(
                name: '{$snakeName}',
                description: 'TODO: describe what this tool does.',
            )]
            final class {$className} extends AbstractTool
            {
                #[ToolParameter(
                    name: 'query',
                    type: 'string',
                    description: 'TODO: describe this parameter.',
                    required: true,
                )]
                private string \$query = '';

                public function execute(array \$arguments, int \$agentId, ?int \$userId = null, ?int \$taskId = null): ToolResult
                {
                    \$query = (string) (\$arguments['query'] ?? \$this->query);

                    // TODO: implement.

                    return ToolResult::ok('Not implemented yet.');
                }

                public function describeAction(array \$arguments): string
                {
                    \$query = (string) (\$arguments['query'] ?? '');
                    return sprintf('Running {$className} with query "%s".', \$query);
                }
            }

            PHP;

        $relativePath = 'app/Tools/' . $className . '.php';
        $generator->generateFile($relativePath, $contents);

        $io->note(sprintf(
            'Don\'t forget to register the tool in app/App.php:\n  public function tools(): array { return [Tools\\%s::class]; }',
            $className,
        ));
    }

    public function getSuccessMessage(): string
    {
        return 'Tool class created. Next: open the file and implement execute().';
    }

    private function toSnakeCase(string $className): string
    {
        $snake = strtolower((string) preg_replace('/(?<!^)([A-Z])/', '_$1', $className));
        // Strip a leading underscore (from "X" at index 0) if present.
        return ltrim($snake, '_');
    }
}
