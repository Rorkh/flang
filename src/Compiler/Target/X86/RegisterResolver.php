<?php

namespace Flang\Compiler\Target\X86;

class RegisterResolver
{
    const COMMON_16BIT = ['ax', 'bx', 'cx', 'dx', 'si', 'di', 'bp', 'sp'];

    public function getCommon()
    {
        $result = [];

        foreach (self::COMMON_16BIT as $reg) {
            $result[] = "r$reg";
            $result[] = "e$reg";
            $result[] = "$reg";
        }

        return $result;
    }
}
