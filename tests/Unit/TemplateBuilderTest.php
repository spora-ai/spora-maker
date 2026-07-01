<?php

declare(strict_types=1);

use Spora\Maker\TemplateBuilder;

it('renders a file with namespace, uses, and body', function (): void {
    $out = (new TemplateBuilder())
        ->namespace('App\\Tools')
        ->use('Spora\\Tools\\AbstractTool')
        ->render('final class Foo extends AbstractTool {}');

    expect($out)->toContain('<?php');
    expect($out)->toContain('declare(strict_types=1);');
    expect($out)->toContain('namespace App\\Tools;');
    expect($out)->toContain('use Spora\\Tools\\AbstractTool;');
    expect($out)->toContain('final class Foo extends AbstractTool {}');
});

it('accepts many uses via uses() in one expression', function (): void {
    $out = (new TemplateBuilder())
        ->namespace('App\\Tools')
        ->uses([
            'Spora\\Tools\\AbstractTool',
            'Spora\\Tools\\Attributes\\Tool',
            'Spora\\Tools\\ValueObjects\\ToolResult',
        ])
        ->render('final class Foo {}');

    expect($out)->toContain('use Spora\\Tools\\AbstractTool;');
    expect($out)->toContain('use Spora\\Tools\\Attributes\\Tool;');
    expect($out)->toContain('use Spora\\Tools\\ValueObjects\\ToolResult;');
});

it('classTemplate wraps a body in final class with optional extends', function (): void {
    $out = TemplateBuilder::classTemplate(
        'WebSearchTool',
        'AbstractTool',
        "public function index(): void\n{\n}\n",
    );

    expect($out)->toContain('final class WebSearchTool extends AbstractTool');
    expect($out)->toContain('{');
    expect($out)->toContain('public function index(): void');
    expect($out)->toContain('}');
});

it('classTemplate omits extends when parent is null', function (): void {
    $out = TemplateBuilder::classTemplate('Plain', null, '');

    expect($out)->toContain('final class Plain');
    expect($out)->not->toContain('extends');
});

it('classTemplate prepends class-level attributes when supplied', function (): void {
    $out = TemplateBuilder::classTemplate(
        'WebSearchTool',
        'AbstractTool',
        "public function execute(): void\n{\n}\n",
        "#[Tool(\n    name: 'foo',\n)]",
    );

    expect($out)->toStartWith("#[Tool(\n    name: 'foo',\n)]");
    expect($out)->toContain('final class WebSearchTool extends AbstractTool');
});

it('classTemplate strips trailing whitespace from blank separator lines', function (): void {
    // Heredoc bodies often leave "    " on otherwise-blank lines.
    $body = "public function go(): void\n    \n{\n    return;\n}\n";
    $out = TemplateBuilder::classTemplate('Foo', null, $body);

    // The original "    " whitespace-only line is gone — it's a truly blank
    // separator (which becomes "    " after re-indenting, but no longer
    // carries 8 spaces of trailing indent on top of indentation).
    expect($out)->not->toContain("        \n");
    expect($out)->toContain("    }\n");
});
