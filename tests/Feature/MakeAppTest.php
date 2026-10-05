<?php

declare(strict_types=1);

use Spora\Maker\Exception\FileAlreadyExistsException;
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

it('names no hook that was removed in 1.0', function (): void {
    generateInto(new MakeApp(), [], $this->generator);
    $this->generator->writeChanges();

    $docblock = file_get_contents($this->tmpDir . '/app/App.php');

    expect($docblock)
        ->toContain('EventSubscriberInterface')
        ->toContain('RoutesRegisteringEvent')
        // Five of these were removed in 1.0. A scaffold that names them
        // sends every new install looking for methods that do not exist.
        ->not->toContain('drivers()')
        ->not->toContain('recipePaths()')
        ->not->toContain('register(\\DI\\ContainerBuilder)')
        ->not->toContain('routes()')
        ->not->toContain('boot()');
});

it('refuses to overwrite an existing app/App.php', function (): void {
    mkdir($this->tmpDir . '/app', 0755, true);
    file_put_contents($this->tmpDir . '/app/App.php', '<?php // existing');

    generateInto(new MakeApp(), [], $this->generator);

    expect(fn () => $this->generator->writeChanges())->toThrow(FileAlreadyExistsException::class);
});

it('registers the command as make:app', function (): void {
    $cmd = new MakeApp();
    expect($cmd->getName())->toBe('make:app');
});
