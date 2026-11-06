<?php

declare(strict_types=1);

namespace Flang\Ast\Transformer;

use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\FileSource;
use Flang\Ast\Node\IncludeNode;

/**
 * AST-level include linker (AST -> AST transformer)
 */
final class IncludeLinker
{
    private array $stack = [];

    public function __construct(
        private readonly ParserInterface $parser,
        private readonly string $rootDirectory,
        private readonly int $maxDepth = 64,
    ) {}

    public function link(ReadableInterface $source): array
    {
        $path = $source instanceof FileInterface ? $source->pathname : null;
        $real = $path !== null ? (realpath($path) ?: $path) : null;

        if ($real !== null) {
            if (isset($this->stack[$real])) {
                throw new \RuntimeException("Circular include detected: {$real}");
            }

            if (count($this->stack) >= $this->maxDepth) {
                throw new \RuntimeException(
                    "Maximum include depth of {$this->maxDepth} exceeded at {$real}"
                );
            }

            $this->stack[$real] = true;
        }

        try {
            $statements = $this->parser->parse($source);
        } finally {
            if ($real !== null) {
                unset($this->stack[$real]);
            }
        }

        if (!is_array($statements)) {
            throw new \UnexpectedValueException(sprintf(
                'Expected the parser to return a list of statements, got %s',
                get_debug_type($statements)
            ));
        }

        $baseDirectory = $real !== null ? dirname($real) : $this->rootDirectory;

        return $this->linkStatements($statements, $baseDirectory);
    }

    private function linkStatements(array $statements, string $baseDirectory): array
    {
        $result = [];

        foreach ($statements as $statement) {
            if (!$statement instanceof IncludeNode) {
                $result[] = $statement;
                continue;
            }

            $target = $this->resolvePath($statement->path, $baseDirectory);

            foreach ($this->link(FileSource::createFromPathname($target)) as $included) {
                $result[] = $included;
            }
        }

        return $result;
    }

    private function resolvePath(string $path, string $baseDirectory): string
    {
        if ($path === '') {
            throw new \InvalidArgumentException('Include path must not be empty');
        }

        $isAbsolute = $path[0] === '/'
            || $path[0] === '\\'
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1;

        if ($isAbsolute) {
            return $path;
        }

        return rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR . $path;
    }
}
