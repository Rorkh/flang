<?php

namespace Flang\Ast\Node;

final readonly class VariableDeclaration
{
    public function __construct(
        public string $name,
        public Literal $value,
        public int $offset,
    ) {}
}
