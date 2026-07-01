<?php

declare(strict_types=1);

namespace Spora\Maker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Common base for every `make:*` command.
 *
 * Subclasses declare their command name, description, and argument help via
 * the `COMMAND_*` class constants. The base constructor wires those into a
 * {@see Command} and the `name` argument; subclasses don't repeat the
 * 5-line constructor pattern.
 *
 * Subclasses implement {@see MakerInterface::generate()} and
 * {@see MakerInterface::getSuccessMessage()} — typically each is 5-15 lines
 * of scaffold-specific code.
 */
abstract class AbstractMaker extends Command implements MakerInterface
{
    protected const COMMAND_NAME = '';
    protected const COMMAND_DESCRIPTION = '';
    protected const COMMAND_ARG_HELP = '';

    public function __construct()
    {
        parent::__construct(static::COMMAND_NAME);
        $this->setDescription(static::COMMAND_DESCRIPTION);
        $this->addArgument('name', InputArgument::REQUIRED, static::COMMAND_ARG_HELP);
    }

    protected function configure(): void
    {
        // name + argument set in the constructor; nothing to add here.
    }

    /**
     * Normalise a user-supplied class basename and append $suffix only if not already present.
     * Centralises the "WebSearchTool" → "WebSearchTool" and "WebSearch" → "WebSearchTool"
     * pattern every maker needs.
     */
    protected function normalisedClassName(InputInterface $input, string $suffix): string
    {
        $baseName = ucfirst((string) $input->getArgument('name'));
        return str_ends_with($baseName, $suffix) ? $baseName : $baseName . $suffix;
    }

    /**
     * "WebSearchTool" → "web_search_tool". Lifted out of MakeTool so MakeController
     * (or any future maker) can reuse the same derivation.
     */
    protected function toSnakeCase(string $className): string
    {
        $snake = strtolower((string) preg_replace('/(?<!^)([A-Z])/', '_$1', $className));
        return ltrim($snake, '_');
    }

    /**
     * "MyApiController" → "my-api". Lifted out of MakeController.
     */
    protected function toKebabCase(string $className): string
    {
        $kebab = strtolower((string) preg_replace('/(?<!^)([A-Z])/', '-$1', $className));
        return ltrim($kebab, '-');
    }

    /**
     * Render a scaffolded class file and queue it for writing.
     *
     * @param list<string> $uses
     */
    protected function renderClass(
        string $namespace,
        array $uses,
        string $className,
        ?string $parent,
        string $innerBody,
        string $targetPath,
        Generator $generator,
        string $classAttributes = '',
    ): void {
        $contents = (new TemplateBuilder())
            ->namespace($namespace)
            ->uses($uses)
            ->render(TemplateBuilder::classTemplate($className, $parent, $innerBody, $classAttributes));

        $generator->generateFile($targetPath, $contents);
    }
}
