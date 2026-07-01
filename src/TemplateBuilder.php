<?php

declare(strict_types=1);

namespace Spora\Maker;

/**
 * Builder for the PHP file templates every maker emits.
 *
 * Centralises the boilerplate SonarQube flagged as duplicated across
 * {@see MakeTool}, {@see MakeController}, and {@see MakeApp}: the
 * `<?php` / `declare(strict_types=1);` prefix, the namespace line, the
 * `use` statement block, and the `final class X [extends Y] { ... }`
 * wrapper every scaffolded file needs.
 *
 * Typical usage from a maker:
 *
 *   $contents = (new TemplateBuilder())
 *       ->namespace('App\\Tools')
 *       ->uses([
 *           'Spora\\Tools\\AbstractTool',
 *           'Spora\\Tools\\Attributes\\Tool',
 *       ])
 *       ->render(
 *           TemplateBuilder::classTemplate($className, 'AbstractTool', $body)
 *       );
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
     * Add a single `use ...;` line. FQCNs should be passed with their leading
     * backslash already doubled (`App\\Http\\Controllers\\Foo`).
     *
     * For 3+ imports prefer {@see self::uses()} which collapses the
     * chained calls SonarQube was flagging as duplication.
     */
    public function use(string $fqcn): self
    {
        $this->uses[] = $fqcn;
        return $this;
    }

    /**
     * Add many `use ...;` lines at once. Equivalent to calling
     * {@see self::use()} once per element, but expressed as a single
     * expression — kills the `->use('A')->use('B')->use('C')` chain that
     * was duplicated across every maker.
     *
     * @param list<string> $fqcnList
     */
    public function uses(array $fqcnList): self
    {
        foreach ($fqcnList as $fqcn) {
            $this->uses[] = $fqcn;
        }
        return $this;
    }

    /**
     * Wrap a class body in `final class $name [extends $parent] { ... }`.
     *
     * Centralises the class skeleton SonarQube flagged across every
     * maker: the `final class ... {` opener and the matching `}` closer.
     *
     * Pass `$parent` as null to omit the `extends` clause (for makers that
     * scaffold a plain class with no parent).
     *
     * `$classAttributes` lets a maker emit class-level PHP attributes
     * (e.g. `#[Tool(...)]`) above the `final class` declaration without
     * having to inline the class wrapper itself. Pass empty/null for
     * makers that don't decorate their class.
     */
    public static function classTemplate(
        string $name,
        ?string $parent,
        string $innerBody,
        string $classAttributes = '',
    ): string {
        $header = 'final class ' . $name . ($parent !== null ? ' extends ' . $parent : '');
        $trimmedAttrs = rtrim($classAttributes);
        // Strip trailing whitespace per line — heredoc bodies often leave "    " on
        // otherwise-blank separator lines, and we want the rendered file to be clean.
        $cleanedBody = $innerBody === ''
            ? ''
            : implode("\n", array_map(rtrim(...), explode("\n", rtrim($innerBody))));
        $hasBody = $cleanedBody !== '';
        $indentedBody = $hasBody
            ? preg_replace('/^/m', '    ', $cleanedBody)
            : '';

        $classBlock = $header . "\n{\n" . $indentedBody . ($hasBody ? "\n}" : "}");
        // Always end with a newline so concatenation with later blocks is clean.
        $classBlock .= "\n";

        if ($trimmedAttrs === '') {
            return $classBlock;
        }

        // Same trailing-whitespace cleanup for attribute lines.
        $cleanAttrs = implode("\n", array_map(rtrim(...), explode("\n", $trimmedAttrs)));

        return $cleanAttrs . "\n" . $classBlock;
    }

    /**
     * Assemble the file: `<?php` + declare + namespace + use statements + body.
     *
     * `$body` is expected to already contain the class declaration (built
     * with {@see self::classTemplate()} or supplied verbatim).
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
