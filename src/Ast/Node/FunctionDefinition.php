<?php

namespace Flang\Ast\Node;

final class FunctionDefinition
{
    public function __construct(
        public mixed $name,
        public int $offset,
        public array $children
    ) {}
}
