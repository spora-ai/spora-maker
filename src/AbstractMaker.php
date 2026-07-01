<?php

declare(strict_types=1);

namespace Spora\Maker;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Common base for every `make:*` command.
 *
 * Centralises the things every maker does the same way:
 *  - name + description + the "name" argument set in the constructor
 *    (so new MakeTool()->getName() works without Application::add())
 *  - the configure() no-op stub (kept so subclasses can still extend it)
 *  - the prefix-aware class-name normalisation (so a maker never has to
 *    re-implement str_ends_with + ucfirst by hand)
 *  - the class-skeleton rendering (so a maker never has to re-implement
 *    TemplateBuilder + generateFile — it just supplies the body)
 *
 * Subclasses implement {@see MakerInterface::generate()} and
 * {@see MakerInterface::getSuccessMessage()} only — typically each is 5-15
 * lines of scaffold-specific code.
 */
abstract class AbstractMaker extends Command implements MakerInterface
{
    /**
     * @param string $commandName  e.g. 'make:tool'
     * @param string $description  Short summary for `bin/spora list`
     * @param string $argNameHelp  Help text for the `name` argument
     */
    public function __construct(string $commandName, string $description, string $argNameHelp)
    {
        parent::__construct($commandName);
        $this->setDescription($description);
        $this->addArgument('name', InputArgument::REQUIRED, $argNameHelp);
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
     * Wraps the four lines SonarQube flagged as duplicated across every
     * maker: build a {@see TemplateBuilder}, set the namespace + use list,
     * hand it the inner body (already wrapped by
     * {@see TemplateBuilder::classTemplate()}), and queue the result with
     * the {@see Generator}.
     *
     * Usage from a maker:
     *
     *   $this->renderClass(
     *       namespace: 'App\\Tools',
     *       uses: ['Spora\\Tools\\AbstractTool', 'Spora\\Tools\\Attributes\\Tool'],
     *       className: $className,
     *       parent: 'AbstractTool',
     *       innerBody: $body,
     *       targetPath: 'app/Tools/' . $className . '.php',
     *       generator: $generator,
     *   );
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
