<?php

namespace Flang\Flasm;

enum Opcode: string
{
    case MOV = 'mov';
    case SYSCALL = 'syscall';
}
