<?php

namespace Flang\Compiler;

use Flang\Compiler\Target\EncoderInterface;

class CodeGenerator
{
    private array $functions = [];

    private array $jmp_patches = [];

    public function __construct(private EncoderInterface $target)
    {}

    private function patch_32(&$buffer, int $offset, int $value): void
    {
        $buffer = substr_replace($buffer, pack('V', $value), $offset, 4);
    }

    public function compileNative(\Flang\Ast\Node\NativeInstruction $node): string
    {
        $r = '';

        if ($node->instruction == \Flang\Flasm\Opcode::MOV) {
            $r = $this->target->mov($node->firstop, $node->secondop);    
        }

        if ($node->instruction == \Flang\Flasm\Opcode::SYSCALL) {
            $r = $this->target->syscall();    
        }

        if ($r == '') {
            // TODO
            throw new \Exception("Unknown instruction???");
        }

        return $r;
    }

    public function compileNode($node, &$buffer)
    {
        if ($node instanceof \Flang\Ast\Node\NativeInstruction) {
            $buffer .= $this->compileNative($node);
        }

        if ($node instanceof \Flang\Ast\Node\FunctionDefinition) {
            $functionBuffer = '';

            foreach ($node->children as $children) {
                $this->compileNode($children, $functionBuffer);
            }

            $buffer .= $functionBuffer;
        }

        if ($node instanceof \Flang\Ast\Node\FunctionCall) {
            $buffer .= $this->target->jmp_rel32(0);
            $this->jmp_patches[$node->name] = strlen($buffer) - 4;
        }
    }

    public function emitEntrypoint(string &$buffer)
    {
        $buffer .= $this->target->mov("rax", 60);
        $buffer .= $this->target->mov("rdi", 0);
        $buffer .= $this->target->syscall();
    }

    public function compile(array $nodes): array
    {
        $buffer = '';
        $userBuffer = '';

        $epilogueBuffer = '';

        foreach ($nodes as $node) {
            if ($node instanceof \Flang\Ast\Node\FunctionDefinition) {
                $functionBuffer = '';

                $this->compileNode($node, $functionBuffer);
                $this->functions[$node->name] = ['content' => $functionBuffer, 'offset' => null];
            
                continue;
            }

            $this->compileNode($node, $userBuffer);
        }

        foreach ($this->functions as $name => $func) {
            $this->functions[$name]['offset'] = strlen($epilogueBuffer);
            $epilogueBuffer .= $func['content'];
        }

        foreach ($this->jmp_patches as $fn => $patch) {
            $rel32 = ($this->functions[$fn]['offset']) - (strlen($epilogueBuffer) + $patch+ 4);            
            $this->patch_32($userBuffer, $patch, $rel32);
        }

        $buffer .= $epilogueBuffer;
        $entOffset = strlen($buffer);

        $buffer .= $userBuffer;        
        $this->emitEntrypoint($buffer);
        
        return [$entOffset, $buffer];
    }
}
