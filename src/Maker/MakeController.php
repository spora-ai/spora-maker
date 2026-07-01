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
 * and prints the route-registration snippet the developer pastes into App::routes().
 *
 *   php bin/spora make:controller MyApi
 *
 * The generated controller has a single index() method returning a basic JSON
 * response, ready to be filled in. Routes are registered imperatively
 * (RouteDefinitions-style) rather than via attributes, so the route snippet
 * is printed for the developer to paste.
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
        $routePath = '/api/v1/' . $this->toKebabCase($className);

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
        $io->writeln('Paste this into <info>app/App.php</info> inside <comment>routes(MiddlewareRouteCollector $r)</comment>:');
        $io->writeln('');
        $io->writeln(<<<SNIPPET
            \$r->addRoute(
                'GET',
                '{$routePath}',
                [\\App\\Http\\Controllers\\{$className}::class, 'index'],
                [\\Spora\\Http\\Middleware\\AuthMiddleware::class, \\Spora\\Http\\Middleware\\CsrfMiddleware::class],
            );
            SNIPPET);
    }

    public function getSuccessMessage(): string
    {
        return 'Controller class created. Next: add route wiring to app/App.php.';
    }
}
