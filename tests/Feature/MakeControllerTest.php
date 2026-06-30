<?php

declare(strict_types=1);

use Spora\Maker\FileManager;
use Spora\Maker\Generator;
use Spora\Maker\Maker\MakeController;

beforeEach(function (): void {
    $this->tmpDir = sys_get_temp_dir() . '/spora-maker-' . bin2hex(random_bytes(4));
    mkdir($this->tmpDir, 0755, true);
    $this->generator = new Generator(new FileManager($this->tmpDir));
});

afterEach(function (): void {
    if (is_dir($this->tmpDir)) {
        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($rii as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }
});

it('creates app/Http/Controllers/<Name>Controller.php', function (): void {
    generateInto(new MakeController(), ['name' => 'MyApi'], $this->generator);

    $written = $this->generator->writeChanges();
    $path = $this->tmpDir . '/app/Http/Controllers/MyApiController.php';

    expect($written)->toContain('app/Http/Controllers/MyApiController.php');
    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);
    expect($contents)->toContain('namespace App\\Http\\Controllers');
    expect($contents)->toContain('final class MyApiController');
    expect($contents)->toContain('public function index(');
});

it('does not double-append the Controller suffix when already present', function (): void {
    generateInto(new MakeController(), ['name' => 'WidgetController'], $this->generator);

    $this->generator->writeChanges();

    expect(file_exists($this->tmpDir . '/app/Http/Controllers/WidgetController.php'))->toBeTrue();
    expect(file_exists($this->tmpDir . '/app/Http/Controllers/WidgetControllerController.php'))->toBeFalse();
});

it('registers the command as make:controller', function (): void {
    $cmd = new MakeController();
    expect($cmd->getName())->toBe('make:controller');
});
