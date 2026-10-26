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
        // The exact path, not a substring: '/api/v1/my-api' is also a prefix
        // of '/api/v1/my-api-controller', so the old assertion passed for a
        // route that named the PHP class instead of the resource.
        ->toContain("'/api/v1/my-api',")
        // routes() was replaced by the event; printing it sends developers
        // to a method that no longer exists on the extension contract.
        ->not->toContain('routes(MiddlewareRouteCollector');
});

/*
 * The App is only attached to the event dispatcher when it implements
 * EventSubscriberInterface (AppLoader::wireEventSubscribers), so the
 * printed class has to carry the interface, the subscriber map, the
 * imports and `getName()` — a handler body alone does not compile in the
 * file it is pasted into.
 */
it('prints the complete App class the subscriber needs', function (): void {
    $output = generateInto(new MakeController(), ['name' => 'MyApi'], $this->generator);

    expect($output)
        ->toContain('use Spora\Events\RoutesRegisteringEvent;')
        ->toContain('use Spora\Extensions\AbstractExtension;')
        ->toContain('use Spora\Http\Middleware\AuthMiddleware;')
        ->toContain('use Spora\Http\Middleware\CsrfMiddleware;')
        ->toContain('use Symfony\Component\EventDispatcher\EventSubscriberInterface;')
        ->toContain('final class App extends AbstractExtension implements EventSubscriberInterface')
        // AbstractExtension leaves getName() abstract — without it the
        // printed class fatals on load and is not pasteable.
        ->toContain('public function getName(): string')
        ->toContain('public static function getSubscribedEvents(): array')
        ->toContain('return [RoutesRegisteringEvent::class => \'onRoutesRegistering\'];')
        ->toContain('public function onRoutesRegistering(RoutesRegisteringEvent $event): void')
        ->toContain('[\App\Http\Controllers\MyApiController::class, \'index\']')
        ->toContain('[AuthMiddleware::class, CsrfMiddleware::class]');
});

it('points the success message at the printed subscriber', function (): void {
    expect((new MakeController())->getSuccessMessage())
        ->toContain('app/App.php');
});

it('registers the command as make:controller', function (): void {
    $cmd = new MakeController();
    expect($cmd->getName())->toBe('make:controller');
});
