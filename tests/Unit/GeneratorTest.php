<?php

declare(strict_types=1);

it('starts with no pending files', function (): void {
    expect($this->generator->getPendingFiles())->toBe([]);
});

it('queues files via generateFile and lists them as pending', function (): void {
    $path = $this->generator->generateFile('app/Foo.php', '<?php // hello');

    expect($path)->toBe('app/Foo.php');
    expect($this->generator->getPendingFiles())->toBe(['app/Foo.php']);
});

it('flushes all queued files on writeChanges', function (): void {
    $this->generator->generateFile('app/A.php', '<?php // a');
    $this->generator->generateFile('app/B.php', '<?php // b');

    $written = $this->generator->writeChanges();

    expect($written)->toBe(['app/A.php', 'app/B.php']);
    expect(file_get_contents($this->tmpDir . '/app/A.php'))->toBe('<?php // a');
    expect(file_get_contents($this->tmpDir . '/app/B.php'))->toBe('<?php // b');
});

it('clears pending files after writeChanges', function (): void {
    $this->generator->generateFile('app/A.php', '<?php // a');
    $this->generator->writeChanges();

    expect($this->generator->getPendingFiles())->toBe([]);
});

it('refuses to queue the same relative path twice', function (): void {
    $this->generator->generateFile('app/A.php', '<?php // a');

    expect(fn () => $this->generator->generateFile('app/A.php', '<?php // b'))
        ->toThrow(RuntimeException::class);
});

it('refuses to write to an existing file (delegated to FileManager)', function (): void {
    mkdir($this->tmpDir . '/app', 0755, true);
    file_put_contents($this->tmpDir . '/app/A.php', '<?php // existing');

    $this->generator->generateFile('app/A.php', '<?php // new');

    expect(fn () => $this->generator->writeChanges())
        ->toThrow(RuntimeException::class);
});
