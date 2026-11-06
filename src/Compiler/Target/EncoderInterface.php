<?php

namespace Flang\Compiler\Target;

interface EncoderInterface
{
    public function mov(mixed $destination, mixed $source): string;
    public function syscall(): string;
}
