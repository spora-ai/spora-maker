<?php

declare(strict_types=1);

use Spora\Maker\Maker\MakeTool;

it('creates app/Tools/<Name>Tool.php with the Tool attribute', function (): void {
    generateInto(new MakeTool(), ['name' => 'WebSearch'], $this->generator);

    $written = $this->generator->writeChanges();
    $path = $this->tmpDir . '/app/Tools/WebSearchTool.php';

    expect($written)->toContain('app/Tools/WebSearchTool.php');
    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);
    expect($contents)->toContain('namespace App\\Tools');
    expect($contents)->toContain('extends AbstractTool');
    expect($contents)->toContain("#[Tool(");
    expect($contents)->toContain("name: 'web_search_tool'");
});

it('does not double-append the Tool suffix when already present', function (): void {
    generateInto(new MakeTool(), ['name' => 'EchoTool'], $this->generator);

    $this->generator->writeChanges();

    expect(file_exists($this->tmpDir . '/app/Tools/EchoTool.php'))->toBeTrue();
    expect(file_exists($this->tmpDir . '/app/Tools/EchoToolTool.php'))->toBeFalse();
});

it('refuses to overwrite an existing tool file', function (): void {
    mkdir($this->tmpDir . '/app/Tools', 0755, true);
    file_put_contents($this->tmpDir . '/app/Tools/WebSearchTool.php', '<?php // existing');

    generateInto(new MakeTool(), ['name' => 'WebSearch'], $this->generator);

    expect(fn () => $this->generator->writeChanges())->toThrow(RuntimeException::class);
});

it('registers the command as make:tool', function (): void {
    $cmd = new MakeTool();
    expect($cmd->getName())->toBe('make:tool');
});
