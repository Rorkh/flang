<?php

namespace Flang;

use Flang\Ast\Transformer\IncludeLinker;
use Phplrt\Compiler\Compiler;
use Phplrt\Source\FileSource;

final class Flang
{
    private function compileParser(string $grammarPath): mixed
    {
        $compiler = (new Compiler())->load(FileSource::createFromPathname($grammarPath));
        
        $resolver = new \Flang\Compiler\Target\X86\RegisterResolver;
        $commonRegs = $resolver->getCommon();

        $tokenRefs = [];

        foreach ($commonRegs as $commonReg) {
            $compiler->lexer->addPattern($commonReg, "T_KW_" . strtoupper($commonReg));
            $tokenRefs[] = new \Phplrt\Parser\Builder\Definition\TokenNameRuleDefinition("T_KW_" . strtoupper($commonReg));
        }

        $compiler->parser->addAlternation($tokenRefs, 'Register');

        $result = $compiler->build();

        return $result->parser->toParser($result->lexer->toLexer());
    }

    public function compile(string $filepath)
    {
        $flangGrammarPath = dirname(__DIR__) . '/grammar.pp3';
        $flasmGrammarPath = dirname(__DIR__) . '/grammar/flasm.pp3';

        $flangParser = $this->compileParser($flangGrammarPath);

        $ast = (new IncludeLinker($flangParser, __DIR__))->link(
            FileSource::createFromPathname($filepath)
        );

        $flasmParser = $this->compileParser($flasmGrammarPath);
        $flasmTransformer = new \Flang\Ast\Transformer\FlasmTransformer($flasmParser);

        $ast = $flasmTransformer->transform($ast);


        $compiler = new \Flang\Compiler\CodeGenerator(
            new \Flang\Compiler\Target\X86\X86Encoder
        );

        [$entOffset, $machine] = $compiler->compile($ast);

        $packager = new \Flang\Emitter\ElfWriter;
        $packager->write($machine, getcwd() . '/program', 0, $entOffset);
    }
}
