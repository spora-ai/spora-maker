<?php

declare(strict_types=1);

namespace Spora\Maker\Maker;

use Spora\Maker\AbstractMaker;
use Spora\Maker\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Scaffolds an HTTP controller class under `app/Http/Controllers/<Name>Controller.php`
 * and prints the RoutesRegisteringEvent subscriber the developer pastes into App.
 *
 *   php bin/spora make:controller MyApi
 *
 * The generated controller has a single index() method returning a basic JSON
 * response, ready to be filled in. Routes are registered imperatively
 * (RouteDefinitions-style) rather than via attributes, so the route snippet
 * is printed for the developer to paste.
 *
 * The printed snippet is the whole App class — use statements, the
 * `EventSubscriberInterface` implementation, `getSubscribedEvents()` and the
 * handler — because the App is only wired to the event dispatcher when it
 * implements the subscriber interface. A bare handler body would leave the
 * operator assembling the class that makes the route live.
 *
 * `getName()` is part of the printed class because `AbstractExtension` leaves
 * it abstract: the snippet has to stay a class PHP can load, not a fragment
 * that only works when merged into an existing app/App.php.
 */
final class MakeController extends AbstractMaker
{
    protected const COMMAND_NAME = 'make:controller';
    protected const COMMAND_DESCRIPTION = 'Create a new HTTP controller under app/Http/Controllers/';
    protected const COMMAND_ARG_HELP = 'The controller basename (e.g. "MyApi" → MyApiController).';

    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void
    {
        $io = new SymfonyStyle($input, $output);
        $className = $this->normalisedClassName($input, 'Controller');
        // The route names the resource, not the PHP class: `MyApiController`
        // is reachable at `/api/v1/my-api`. The suffix belongs to the file
        // name, not to the URL.
        $routeSlug = str_ends_with($className, 'Controller')
            ? substr($className, 0, -strlen('Controller'))
            : $className;
        $routePath = '/api/v1/' . $this->toKebabCase($routeSlug);

        $body = <<<PHP
            public function index(Request \$request): Response
            {
                // TODO: implement.

                return new JsonResponse([
                    'message' => 'Hello from {$className}!',
                ]);
            }

            PHP;

        $this->renderClass(
            namespace: 'App\\Http\\Controllers',
            uses: [
                'Symfony\\Component\\HttpFoundation\\JsonResponse',
                'Symfony\\Component\\HttpFoundation\\Request',
                'Symfony\\Component\\HttpFoundation\\Response',
            ],
            className: $className,
            parent: null,
            innerBody: $body,
            targetPath: 'app/Http/Controllers/' . $className . '.php',
            generator: $generator,
        );

        $io->writeln('');
        $io->writeln('Add a <comment>RoutesRegisteringEvent</comment> subscriber to <info>app/App.php</info>. The App receives the event only if it implements <comment>EventSubscriberInterface</comment> and lists the method in <comment>getSubscribedEvents()</comment>. The class below is complete and compiles as written — merge it into <info>app/App.php</info>: copy the <info>use</info> statements, add <comment>implements EventSubscriberInterface</comment> to the class declaration, and add the members, keeping your own <info>getName()</info> value and the hooks your project already overrides.');
        $io->writeln('');
        $io->writeln(<<<SNIPPET
            <?php

            declare(strict_types=1);

            namespace App;

            use Spora\\Events\\RoutesRegisteringEvent;
            use Spora\\Extensions\\AbstractExtension;
            use Spora\\Http\\Middleware\\AuthMiddleware;
            use Spora\\Http\\Middleware\\CsrfMiddleware;
            use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;

            final class App extends AbstractExtension implements EventSubscriberInterface
            {
                public function getName(): string
                {
                    return 'My Spora App';
                }

                /** @return array<class-string, string> */
                public static function getSubscribedEvents(): array
                {
                    return [RoutesRegisteringEvent::class => 'onRoutesRegistering'];
                }

                public function onRoutesRegistering(RoutesRegisteringEvent \$event): void
                {
                    \$event->routes()->addRoute(
                        'GET',
                        '{$routePath}',
                        [\\App\\Http\\Controllers\\{$className}::class, 'index'],
                        [AuthMiddleware::class, CsrfMiddleware::class],
                    );
                }
            }
            SNIPPET);
    }

    public function getSuccessMessage(): string
    {
        return 'Controller class created. Next: merge the printed subscriber into app/App.php.';
    }
}
