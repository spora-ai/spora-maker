<?php

declare(strict_types=1);

namespace Spora\Maker\Maker;

use Spora\Maker\Generator;
use Spora\Maker\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
final class MakeController extends Command implements MakerInterface
{
    public function __construct()
    {
        parent::__construct('make:controller');
        $this->setDescription('Create a new HTTP controller under app/Http/Controllers/');
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'The controller basename (e.g. "MyApi" → MyApiController).',
        );
    }

    protected function configure(): void
    {
        // Argument added in constructor so new MakeController()->getDefinition()
        // has 'name' available without requiring Application::add() to call
        // configure() first (matters for direct test invocation).
    }

    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void
    {
        $io = new SymfonyStyle($input, $output);
        $baseName = ucfirst((string) $input->getArgument('name'));
        $className = str_ends_with($baseName, 'Controller') ? $baseName : $baseName . 'Controller';
        $routePath = '/api/v1/' . $this->toKebabCase($baseName);

        $contents = <<<PHP
            <?php

            declare(strict_types=1);

            namespace App\\Http\\Controllers;

            use Symfony\\Component\\HttpFoundation\\JsonResponse;
            use Symfony\\Component\\HttpFoundation\\Request;
            use Symfony\\Component\\HttpFoundation\\Response;

            final class {$className}
            {
                public function index(Request \$request): Response
                {
                    // TODO: implement.

                    return new JsonResponse([
                        'message' => 'Hello from {$className}!',
                    ]);
                }
            }

            PHP;

        $relativePath = 'app/Http/Controllers/' . $className . '.php';
        $generator->generateFile($relativePath, $contents);

        $io->writeln('');
        $io->writeln('Paste this into <info>app/App.php</info> inside <comment>routes(MiddlewareRouteCollector \$r)</comment>:');
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

    private function toKebabCase(string $className): string
    {
        $kebab = strtolower((string) preg_replace('/(?<!^)([A-Z])/', '-$1', $className));
        return ltrim($kebab, '-');
    }
}
