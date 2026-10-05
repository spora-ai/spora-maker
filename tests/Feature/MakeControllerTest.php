<?php

declare(strict_types=1);

use Spora\Maker\Maker\MakeController;

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

it('prints a RoutesRegisteringEvent subscriber, not the hook removed in 1.0', function (): void {
    $output = generateInto(new MakeController(), ['name' => 'MyApi'], $this->generator);

    expect($output)
        ->toContain('onRoutesRegistering')
        ->toContain('RoutesRegisteringEvent')
        ->toContain('EventSubscriberInterface')
        ->toContain('/api/v1/my-api')
        // routes() was replaced by the event; printing it sends developers
        // to a method that no longer exists on the extension contract.
        ->not->toContain('routes(MiddlewareRouteCollector');
});

it('registers the command as make:controller', function (): void {
    $cmd = new MakeController();
    expect($cmd->getName())->toBe('make:controller');
});
