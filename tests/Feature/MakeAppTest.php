<?php

declare(strict_types=1);

use Spora\Maker\Maker\MakeApp;

it('creates app/App.php with the AbstractExtension scaffold', function (): void {
    generateInto(new MakeApp(), [], $this->generator);

    $written = $this->generator->writeChanges();
    $path = $this->tmpDir . '/app/App.php';

    expect($written)->toContain('app/App.php');
    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);
    expect($contents)->toContain('namespace App');
    expect($contents)->toContain('extends AbstractExtension');
    expect($contents)->toContain('public function getName(): string');
    // Scaffold is intentionally minimal — only getName() is implemented;
    // every other hook inherits its no-op default from AbstractExtension.
    expect($contents)->not->toContain('// public function tools(): array');
    expect($contents)->not->toContain('// public function routes(');
    expect($contents)->not->toContain('// public function boot(): void');
});

it('refuses to overwrite an existing app/App.php', function (): void {
    mkdir($this->tmpDir . '/app', 0755, true);
    file_put_contents($this->tmpDir . '/app/App.php', '<?php // existing');

    generateInto(new MakeApp(), [], $this->generator);

    expect(fn () => $this->generator->writeChanges())->toThrow(RuntimeException::class);
});

it('registers the command as make:app', function (): void {
    $cmd = new MakeApp();
    expect($cmd->getName())->toBe('make:app');
});
