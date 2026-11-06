<?php

namespace Flang\Ast\Node;

final readonly class IncludeNode
{
    public function __construct(
        public mixed $path,
        public int $offset,
    ) {}
}
