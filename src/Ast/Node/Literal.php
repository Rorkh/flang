<?php

namespace Flang\Ast\Node;

final readonly class Literal
{
    public function __construct(
        public mixed $value,
        public int $offset,
    ) {}
}
