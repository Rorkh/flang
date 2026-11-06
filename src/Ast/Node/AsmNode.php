<?php

namespace Flang\Ast\Node;

final readonly class AsmNode
{
    public function __construct(
        public mixed $value,
        public int $offset,
    ) {}
}
