<?php

declare(strict_types=1);

namespace Flang\Ast\Transformer;

use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\StringSource;
use Flang\Ast\Node\AsmNode;
use Flang\Ast\Node\FunctionDefinition;

final class FlasmTransformer
{

    public function __construct(
        private ParserInterface $flasmParser,
    ) {}

    public function transform(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            if ($node instanceof FunctionDefinition) {
                $node->children = $this->transform($node->children);
            }

            if (!$node instanceof AsmNode) {
                $result[] = $node;
                continue;
            }

            $result = array_merge($result, $this->flasmParser->parse(StringSource::createFromString($node->value)));
        }

        return $result;
    }
}
