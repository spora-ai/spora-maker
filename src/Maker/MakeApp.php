<?php

declare(strict_types=1);

namespace Spora\Maker\Maker;

use Spora\Maker\AbstractMaker;
use Spora\Maker\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Re-creates the project App entry class.
 *
 *   php bin/spora make:app
 *
 * Use this if you deleted app/App.php and want a fresh reference.
 */
final class MakeApp extends AbstractMaker
{
    public function __construct()
    {
        parent::__construct(
            'make:app',
            'Recreate the app/App.php entry-point class.',
            'Unused — make:app takes no arguments.',
        );
    }

    public function generate(InputInterface $input, OutputInterface $output, Generator $generator): void
    {
        $body = <<<'PHP'
            /**
             * Project-level App extension. Discovered by AppLoader via reflection;
             * one per installation, no manifest, no slug.
             *
             * Override hooks to wire project-local code into the framework:
             *   tools(), drivers(), recipePaths(), schemaVersion(), migrationsPath(),
             *   apps(), register(\DI\ContainerBuilder), routes(), boot().
             *
             * Promote to a plugin later: rename App → Plugin, add plugin.json, ship
             * as a Composer package.
             */
            public function getName(): string
            {
                return 'My Spora App';
            }

            PHP;

        $this->renderClass(
            namespace: 'App',
            uses: ['Spora\\Extensions\\AbstractExtension'],
            className: 'App',
            parent: 'AbstractExtension',
            innerBody: $body,
            targetPath: 'app/App.php',
            generator: $generator,
        );
    }

    public function getSuccessMessage(): string
    {
        return 'app/App.php recreated. Override the hooks you need.';
    }
}
