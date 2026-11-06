<?php

namespace Flang\Ast\Node;

final readonly class FunctionCall
{
    public function __construct(
        public mixed $name,
        public int $offset
    ) {}
}
