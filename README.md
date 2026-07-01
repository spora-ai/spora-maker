# spora-maker

Scaffolder for the Spora project App — generates Tools, Controllers, and the
`app/App.php` entry class. Inspired by Symfony's
[`maker-bundle`](https://github.com/symfony/maker-bundle), scoped to the
project-local extension model introduced in Spora v0.5.

## Install

```bash
composer require-dev spora-ai/spora-maker
```

The skeleton already wires this up — `composer.json` lists `spora-ai/spora-maker`
in `require-dev`. The path repository in the skeleton points at this repo
locally; in production it will resolve from Packagist once published.

## Commands

All commands assume the project root as the working directory (matching
`bin/spora`'s convention).

### `make:tool <Name>`

```
php bin/spora make:tool WebSearch
```

Creates `app/Tools/WebSearchTool.php` using the `AbstractTool` + `#[Tool]`
attribute pattern from `spora-core`. Refuses to overwrite an existing file.

### `make:controller <Name>`

```
php bin/spora make:controller MyApi
```

Creates `app/Http/Controllers/MyApiController.php` with a placeholder
`index()` method returning a JSON response, and prints the route-registration
snippet to paste into `app/App.php` inside `routes(MiddlewareRouteCollector $r)`.

### `make:app`

```
php bin/spora make:app
```

Recreates `app/App.php` from the latest scaffold template. Useful when the
file was deleted and the developer wants a fresh reference.

## Conventions

- Project-relative paths (`app/Tools/Foo.php`, never absolute).
- No overwrites — `FileManager::dumpFile()` raises `RuntimeException` if a
  target exists.
- Templates are inline strings, not external files. Keeps the scaffolder
  dependency-free (only Symfony Console).

## Adding a new `make:*`

1. Create `src/Maker/<Name>.php` extending `Symfony\Component\Console\Command\Command`
   and implementing `Spora\Maker\MakerInterface`.
2. Implement `generate(InputInterface, OutputInterface, Generator): void`.
   Use `$generator->generateFile('relative/path.php', $contents)` to queue.
3. Append the class FQCN to `MakeCommand::MAKERS`.

That's it — no other wiring required.

## License

MIT.