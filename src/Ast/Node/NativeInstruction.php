<?php

namespace Flang\Ast\Node;

final readonly class NativeInstruction
{
    public function __construct(
        public \Flang\Flasm\Opcode $instruction,
        public mixed $firstop,
        public mixed $secondop
    ) {}
}
