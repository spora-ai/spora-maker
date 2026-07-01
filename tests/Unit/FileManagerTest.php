<?php

declare(strict_types=1);

it('returns absolute paths with leading slash stripped', function (): void {
    expect($this->fm->absolutePath('app/Tools/Foo.php'))->toBe($this->tmpDir . '/app/Tools/Foo.php');
    expect($this->fm->absolutePath('/app/Tools/Foo.php'))->toBe($this->tmpDir . '/app/Tools/Foo.php');
});

it('reports file existence via fileExists', function (): void {
    expect($this->fm->fileExists('app/Tools/Foo.php'))->toBeFalse();

    $this->fm->ensureDirectory('app/Tools');
    file_put_contents($this->tmpDir . '/app/Tools/Foo.php', '<?php // existing');

    expect($this->fm->fileExists('app/Tools/Foo.php'))->toBeTrue();
});

it('creates intermediate directories on demand', function (): void {
    $this->fm->ensureDirectory('app/Http/Controllers');

    expect(is_dir($this->tmpDir . '/app/Http/Controllers'))->toBeTrue();
});

it('writes new files when target does not exist', function (): void {
    $this->fm->dumpFile('app/Foo.php', '<?php // hello');

    expect($this->fm->fileExists('app/Foo.php'))->toBeTrue();
    expect(file_get_contents($this->tmpDir . '/app/Foo.php'))->toBe('<?php // hello');
});

it('refuses to overwrite an existing file', function (): void {
    $this->fm->dumpFile('app/Foo.php', '<?php // first');

    expect(fn () => $this->fm->dumpFile('app/Foo.php', '<?php // second'))
        ->toThrow(RuntimeException::class);
});

it('creates parent directories automatically when dumping', function (): void {
    $this->fm->dumpFile('app/Http/Controllers/Nested/Foo.php', '<?php // x');

    expect(is_dir($this->tmpDir . '/app/Http/Controllers/Nested'))->toBeTrue();
    expect($this->fm->fileExists('app/Http/Controllers/Nested/Foo.php'))->toBeTrue();
});
