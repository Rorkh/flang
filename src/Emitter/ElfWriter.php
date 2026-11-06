<?php

namespace Flang\Emitter;

final class ElfWriter
{
    public const ET_EXEC = 2;
    public const EM_X86_64 = 62;
    public const PT_LOAD = 1;

    public const PF_X = 1;
    public const PF_W = 2;
    public const PF_R = 4;

    private const EHDR_SIZE = 64;
    private const PHDR_SIZE = 56;
    private const CODE_OFFSET = self::EHDR_SIZE + self::PHDR_SIZE;

    public function __construct(
        private readonly int $baseAddress = 0x400000,
        private readonly int $flags = self::PF_R | self::PF_W | self::PF_X,
        private readonly int $pageSize = 0x1000,
    ) {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException('ElfWriter requires a 64-bit PHP build.');
        }
        if ($baseAddress < 0) {
            throw new InvalidArgumentException('Base address must be non-negative.');
        }
        if ($pageSize <= 0 || ($pageSize & ($pageSize - 1)) !== 0) {
            throw new InvalidArgumentException('Page size must be a positive power of two.');
        }
        if (($baseAddress % $pageSize) !== 0) {
            throw new InvalidArgumentException(sprintf(
                'Base address 0x%X must be aligned to the page size 0x%X.',
                $baseAddress,
                $pageSize,
            ));
        }
    }

    public function pack(string $code, int $bssSize = 0, int $entOffset = 0): string
    {
        if ($bssSize < 0) {
            throw new InvalidArgumentException('BSS size must be non-negative.');
        }

        $entry = $this->baseAddress + self::CODE_OFFSET + $entOffset;
        $fileSize = self::CODE_OFFSET + strlen($code);
        $memSize = $fileSize + $bssSize;

        $elfHeader = $this->ident() . pack(
            'vvVPPPVvvvvvv',
            self::ET_EXEC,
            self::EM_X86_64,
            1,
            $entry,
            self::EHDR_SIZE,
            0,
            0,
            self::EHDR_SIZE,
            self::PHDR_SIZE,
            1,
            0,
            0,
            0,
        );

        $programHeader = pack(
            'VVPPPPPP',
            self::PT_LOAD,
            $this->flags,
            0,
            $this->baseAddress,
            $this->baseAddress,
            $fileSize,
            $memSize,
            $this->pageSize,
        );

        return $elfHeader . $programHeader . $code;
    }

    public function packHex(string $hex, int $bssSize = 0): string
    {
        return $this->pack($this->decodeHex($hex), $bssSize);
    }

    public function write(string $code, string $path, int $bssSize = 0, int $entOffset = 0): void
    {
        $this->store($path, $this->pack($code, $bssSize, $entOffset));
    }

    public function writeHex(string $hex, string $path, int $bssSize = 0): void
    {
        $this->store($path, $this->packHex($hex, $bssSize));
    }

    public function entryPoint(): int
    {
        return $this->baseAddress + self::CODE_OFFSET;
    }

    public function codeOffset(): int
    {
        return self::CODE_OFFSET;
    }

    public function baseAddress(): int
    {
        return $this->baseAddress;
    }

    public function flags(): int
    {
        return $this->flags;
    }

    private function decodeHex(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex);

        if ($hex === null || $hex === '' || (strlen($hex) % 2) !== 0 || !ctype_xdigit($hex)) {
            throw new InvalidArgumentException(
                'Hex payload must be a non-empty, even-length string of hex digits.',
            );
        }

        $decoded = hex2bin($hex);

        if ($decoded === false) {
            throw new InvalidArgumentException('Hex payload could not be decoded.');
        }

        return $decoded;
    }

    private function store(string $path, string $elf): void
    {
        if (file_put_contents($path, $elf) === false) {
            throw new RuntimeException(sprintf('Failed to write "%s".', $path));
        }

        if (!chmod($path, 0755)) {
            throw new RuntimeException(sprintf('Failed to make "%s" executable.', $path));
        }
    }

    private function ident(): string
    {
        return "\x7fELF"
            . "\x02"  // ELFCLASS64
            . "\x01"  // ELFDATA2LSB
            . "\x01"  // EV_CURRENT
            . "\x00"  // ELFOSABI_SYSV
            . "\x00"  // EI_ABIVERSION
            . str_repeat("\x00", 7);
    }
}
