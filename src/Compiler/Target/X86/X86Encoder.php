<?php

namespace Flang\Compiler\Target\X86;

use Flang\Compiler\Target\AbstractEncoder;

/**
 * X86_64 machine code generator
 */
class X86Encoder extends AbstractEncoder
{
    private const REGISTERS = [
        'rax', 'rcx', 'rdx', 'rbx',
        'rsp', 'rbp', 'rsi', 'rdi',
        'r8', 'r9', 'r10', 'r11',
        'r12', 'r13', 'r14', 'r15'
    ];

    private function rex($w, $r, $x, $b)
    {
        return $this->byte([0, 1, 0, 0, $w, $r, $x, $b]);
    }

    private function modrm(int $mod, int $reg, int $rm): string 
    {
        // Mask inputs to ensure they only occupy their allowed bit widths
        $mod = $mod & 0x03; // 2 bits (00000011)
        $reg = $reg & 0x07; // 3 bits (00000111)
        $rm  = $rm  & 0x07; // 3 bits (00000111)

        // Shift fields into their proper positions and combine them
        return chr(($mod << 6) | ($reg << 3) | $rm);
    }

    private function get_reg_index(string $reg)
    {
        static $map = [];

        if (isset($map[$reg])) {
            return $map[$reg];
        }

        $map[$reg] = array_search($reg, self::REGISTERS);
        return $map[$reg];
    }

    private function resolve_reg_rex_b(string $reg): int
    {
        $regIndex = $this->get_reg_index($reg);

        if ($regIndex >= 8) {
            return 1;
        }

        return 0;
    }

    public function mov($destination, $source): string
    {
        $r = '';

        $rex_b = $this->resolve_reg_rex_b($destination);
        $rm = null;

        $destRegIndex = $this->get_reg_index($destination);

        /** TODO: Different method */
        if ($rex_b == 1) {
            /** Rex prefix */
            $r = $this->rex(1, 0, 0, 1);
            $rm = $destRegIndex - 8;
        } else {
            $rm = $destRegIndex;
        }

        $r .= "\xC7";
        $r .= $this->modrm(3, 0, $rm);

        # imm (4 bytes)
        $r .= pack('V', $source);

        return $r;
    }

    public function jmp_rel32($displacement): string
    {
        $r = "\xE9";
        return $r . pack('V', $displacement);
    }

    public function syscall(): string
    {
        return "\x0F\x05";
    }
}
