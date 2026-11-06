<?php

namespace Flang\Compiler\Target\A64;

use Flang\Compiler\Target\AbstractEncoder;

/**
 * ARM64 machine code generator
 */
class A64Encoder extends AbstractEncoder
{
    public function mov($destination, $source): string
    {
        $r = "\x2B";

        $r .= "\x0";
        $r .= "\x80";

        $r .= "\xD2";

        return $r;
    }

    public function syscall(): string
    {
        return '';
    }
}
