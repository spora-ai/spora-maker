<?php

declare(strict_types=1);

namespace Spora\Maker\Maker;

use Spora\Maker\AbstractMaker;
use Spora\Maker\Generator;
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
final class MakeTool extends AbstractMaker
{
    public function __construct()
    {
        parent::__construct(
            'make:tool',
            'Create a new Tool class under app/Tools/',
            'The tool class basename (without "Tool" suffix).',
        );
    }

    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void
    {
        $io = new SymfonyStyle($input, $output);
        $className = $this->normalisedClassName($input, 'Tool');
        $snakeName = $this->toSnakeCase($className);

        $classAttributes = <<<PHP
            #[Tool(
                name: '{$snakeName}',
                description: 'TODO: describe what this tool does.',
            )]

            PHP;

        $body = <<<PHP
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

            PHP;

        $this->renderClass(
            namespace: 'App\\Tools',
            uses: [
                'Spora\\Tools\\AbstractTool',
                'Spora\\Tools\\Attributes\\Tool',
                'Spora\\Tools\\Attributes\\ToolParameter',
                'Spora\\Tools\\ValueObjects\\ToolResult',
            ],
            className: $className,
            parent: 'AbstractTool',
            innerBody: $body,
            targetPath: 'app/Tools/' . $className . '.php',
            generator: $generator,
            classAttributes: $classAttributes,
        );

        $io->note(sprintf(
            'Don\'t forget to register the tool in app/App.php:\n  public function tools(): array { return [Tools\\%s::class]; }',
            $className,
        ));
    }

    public function getSuccessMessage(): string
    {
        return 'Tool class created. Next: open the file and implement execute().';
    }
}
