<?php

declare(strict_types=1);

use Spora\Maker\Exception\FileAlreadyExistsException;
use Spora\Maker\Maker\MakeSkill;

it('creates skills/<name>/SKILL.md and examples.md', function (): void {
    generateInto(new MakeSkill(), ['name' => 'git'], $this->generator);

    $written = $this->generator->writeChanges();
    $skillPath = $this->tmpDir . '/skills/git/SKILL.md';
    $examplesPath = $this->tmpDir . '/skills/git/examples.md';

    expect($written)->toContain('skills/git/SKILL.md')
        ->and($written)->toContain('skills/git/examples.md')
        ->and(file_exists($skillPath))->toBeTrue()
        ->and(file_exists($examplesPath))->toBeTrue();
});

it('writes a SKILL.md with valid frontmatter matching the slug', function (): void {
    generateInto(new MakeSkill(), ['name' => 'pdf-processing'], $this->generator);
    $this->generator->writeChanges();

    $contents = file_get_contents($this->tmpDir . '/skills/pdf-processing/SKILL.md');
    expect($contents)->toContain('name: pdf-processing')
        ->and($contents)->toContain('description:')
        ->and($contents)->toContain('---');
});

it('rejects slugs with consecutive hyphens', function (): void {
    expect(fn () => generateInto(new MakeSkill(), ['name' => 'BAD--NAME'], $this->generator))
        ->toThrow(RuntimeException::class, 'not a valid agentskills.io slug');
});

it('rejects slugs with uppercase letters', function (): void {
    expect(fn () => generateInto(new MakeSkill(), ['name' => 'GitHelper'], $this->generator))
        ->toThrow(RuntimeException::class, 'not a valid agentskills.io slug');
});

it('rejects slugs that are too long', function (): void {
    $tooLong = str_repeat('a', 65);
    expect(fn () => generateInto(new MakeSkill(), ['name' => $tooLong], $this->generator))
        ->toThrow(RuntimeException::class, 'not a valid agentskills.io slug');
});

it('refuses to overwrite an existing skill directory', function (): void {
    mkdir($this->tmpDir . '/skills/git', 0755, true);
    file_put_contents($this->tmpDir . '/skills/git/SKILL.md', '-- existing --');

    generateInto(new MakeSkill(), ['name' => 'git'], $this->generator);

    expect(fn () => $this->generator->writeChanges())->toThrow(FileAlreadyExistsException::class);
});

it('registers the command as make:skill', function (): void {
    $cmd = new MakeSkill();
    expect($cmd->getName())->toBe('make:skill');
});
