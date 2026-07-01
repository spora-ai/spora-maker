<?php

declare(strict_types=1);

namespace Spora\Maker;

/**
 * Builder for the PHP file templates every maker emits.
 *
 * Centralises the boilerplate that SonarQube flagged as duplicated across
 * {@see MakeTool}, {@see MakeController}, and {@see MakeApp}: the
 * `<?php` / `declare(strict_types=1);` prefix, the namespace line, and the
 * optional use-statement block.
 *
 * Usage from a maker:
 *
 *   $contents = (new TemplateBuilder())
 *       ->namespace('App\\Tools')
 *       ->use('Spora\\Tools\\AbstractTool')
 *       ->use('Spora\\Tools\\Attributes\\Tool')
 *       ->render(<<<BODY
 *           final class {$className} extends AbstractTool
 *           {
 *               // ...
 *           }
 *           BODY);
 *
 * `render()` returns the assembled file as a string ready for
 * {@see Generator::generateFile()}.
 */
final class TemplateBuilder
{
    private string $namespace = '';
    /** @var list<string> */
    private array $uses = [];

    /**
     * Set the namespace the rendered file lives in (e.g. `App\\Tools`).
     */
    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Add a `use ...;` line. FQCNs should be passed with their leading
     * backslash already doubled (`App\\Http\\Controllers\\Foo`).
     */
    public function use(string $fqcn): self
    {
        $this->uses[] = $fqcn;
        return $this;
    }

    /**
     * Assemble the file: `<?php` + declare + namespace + use statements + body.
     *
     * $body is expected to already contain the class declaration (with its
     * own leading indentation matching the heredoc it was written in).
     */
    public function render(string $body): string
    {
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
        ];

        if ($this->namespace !== '') {
            $lines[] = 'namespace ' . $this->namespace . ';';
            $lines[] = '';
        }

        foreach ($this->uses as $use) {
            $lines[] = 'use ' . $use . ';';
        }

        if ($this->uses !== []) {
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines), "\n") . "\n\n" . $body;
    }
}
