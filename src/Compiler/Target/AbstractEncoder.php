<?php

namespace Flang\Compiler\Target;

abstract class AbstractEncoder implements EncoderInterface
{
    abstract public function mov(mixed $destination, mixed $source): string;
    abstract public function syscall(): string;

    protected function byte(array $bits)
    {
        $byte = 0;
        foreach ($bits as $bit) {
            $byte = ($byte << 1) | (int) $bit;
        }

        return chr($byte);
    }
}
