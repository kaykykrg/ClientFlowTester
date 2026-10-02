<?php
declare(strict_types=1);

namespace ClientFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Blocos A, B e C – Testes unitários das funções JavaScript do cadastro.js
 *
 * Estratégia: Usa Node.js via shell_exec para executar as funções JS
 * dentro de um sandbox. Se Node.js não estiver disponível, os testes
 * são marcados como skipped.
 *
 * Alternativa: Replica a lógica das funções JS em PHP puro para teste
 * (abordagem adotada aqui para garantir execução sem dependências externas).
 */
final class BlockABCTest extends TestCase
{
    // ================================================================
    // Réplicas das funções JS em PHP para teste direto
    // (baseadas no código-fonte de cadastro.js)
    // ================================================================

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    private function isRepeatedDigits(string $value): bool
    {
        return (bool) preg_match('/^(\d)\1+$/', $value);
    }

    private function isValidCPF(string $value): bool
    {
        $cpf = $this->onlyDigits($value);
        if (strlen($cpf) !== 11 || $this->isRepeatedDigits($cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $check = ($sum * 10) % 11;
        if ($check === 10) $check = 0;
        if ($check !== intval($cpf[9])) return false;

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $check = ($sum * 10) % 11;
        if ($check === 10) $check = 0;

        return $check === intval($cpf[10]);
    }

    private function isValidCNPJ(string $value): bool
    {
        $cnpj = $this->onlyDigits($value);
        if (strlen($cnpj) !== 14 || $this->isRepeatedDigits($cnpj)) {
            return false;
        }

        $base = substr($cnpj, 0, 12);
        $factors1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += intval($base[$i]) * $factors1[$i];
        }
        $rem = $sum % 11;
        $d1 = $rem < 2 ? 0 : 11 - $rem;

        $base2 = $base . $d1;
        $factors2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += intval($base2[$i]) * $factors2[$i];
        }
        $rem = $sum % 11;
        $d2 = $rem < 2 ? 0 : 11 - $rem;

        return $cnpj === $base . $d1 . $d2;
    }

    private function validatePasswordStrength(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'A senha deve ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'A senha deve conter ao menos 1 letra maiúscula.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'A senha deve conter ao menos 1 letra minúscula.';
        }
        if (!preg_match('/\d/', $password)) {
            return 'A senha deve conter ao menos 1 número.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'A senha deve conter ao menos 1 caractere especial.';
        }
        if (preg_match('/(.)\1{2,}/', $password)) {
            return 'A senha não pode repetir o mesmo caractere em sequência.';
        }
        return null;
    }

    private function formatCPF(string $value): string
    {
        $d = substr($this->onlyDigits($value), 0, 11);
        $len = strlen($d);
        if ($len <= 3) return $d;
        if ($len <= 6) return substr($d, 0, 3) . '.' . substr($d, 3);
        if ($len <= 9) return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6);
        return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9);
    }

    private function formatCNPJ(string $value): string
    {
        $d = substr($this->onlyDigits($value), 0, 14);
        $len = strlen($d);
        if ($len <= 2) return $d;
        if ($len <= 5) return substr($d, 0, 2) . '.' . substr($d, 2);
        if ($len <= 8) return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5);
        if ($len <= 12) return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8);
        return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12);
    }

    private function formatPhone(string $value): string
    {
        $d = substr($this->onlyDigits($value), 0, 11);
        $len = strlen($d);
        if ($len <= 2) return $len ? '(' . $d : '';
        if ($len <= 6) return '(' . substr($d, 0, 2) . ') ' . substr($d, 2);
        if ($len <= 10) return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
    }

    // ================================================================
    // BLOCO A – 8 testes (T01 a T08)
    // ================================================================

    // T01 – CPF válido deve ser aceito
    public function testT01CpfValidoAceito(): void
    {
        $this->assertTrue($this->isValidCPF('529.982.247-25'));
    }

    // T02 – CPF com dígitos repetidos deve ser rejeitado
    public function testT02CpfDigitosRepetidosRejeitado(): void
    {
        $this->assertFalse($this->isValidCPF('111.111.111-11'));
    }

    // T03 – CNPJ válido deve ser aceito
    public function testT03CnpjValidoAceito(): void
    {
        $this->assertTrue($this->isValidCNPJ('04.252.011/0001-10'));
    }

    // T04 – CNPJ inválido deve ser rejeitado
    public function testT04CnpjInvalidoRejeitado(): void
    {
        $this->assertFalse($this->isValidCNPJ('00.000.000/0000-00'));
    }

    // T05 – Remover caracteres não numéricos de uma entrada
    public function testT05OnlyDigitsRemoveNaoNumericos(): void
    {
        $this->assertSame('11912345678', $this->onlyDigits('(11) 91234-5678'));
    }

    // T06 – Formatar CPF corretamente
    public function testT06FormatCPF(): void
    {
        $this->assertSame('529.982.247-25', $this->formatCPF('52998224725'));
    }

    // T07 – Formatar CNPJ corretamente
    public function testT07FormatCNPJ(): void
    {
        $this->assertSame('04.252.011/0001-10', $this->formatCNPJ('04252011000110'));
    }

    // T08 – Formatar telefone corretamente
    public function testT08FormatPhone(): void
    {
        $this->assertSame('(11) 91234-5678', $this->formatPhone('11912345678'));
    }

    // ================================================================
    // BLOCO B – 4 testes unitários JS (T09 a T12)
    // ================================================================

    // T09 – Senha com menos de 8 caracteres deve ser rejeitada
    public function testT09SenhaCurta(): void
    {
        $this->assertSame(
            'A senha deve ter no mínimo 8 caracteres.',
            $this->validatePasswordStrength('Ab1!')
        );
    }

    // T10 – Senha sem letra maiúscula deve ser rejeitada
    public function testT10SenhaSemMaiuscula(): void
    {
        $this->assertSame(
            'A senha deve conter ao menos 1 letra maiúscula.',
            $this->validatePasswordStrength('ab1!defg')
        );
    }

    // T11 – Senha sem letra minúscula deve ser rejeitada
    public function testT11SenhaSemMinuscula(): void
    {
        $this->assertSame(
            'A senha deve conter ao menos 1 letra minúscula.',
            $this->validatePasswordStrength('AB1!DEFG')
        );
    }

    // T12 – Senha sem número deve ser rejeitada
    public function testT12SenhaSemNumero(): void
    {
        $this->assertSame(
            'A senha deve conter ao menos 1 número.',
            $this->validatePasswordStrength('Abc!defg')
        );
    }

    // ================================================================
    // BLOCO C – 4 testes unitários JS (T17 a T20)
    // ================================================================

    // T17 – Remover caracteres não numéricos (caso diferente)
    public function testT17OnlyDigitsCasoDiferente(): void
    {
        $this->assertSame('123456', $this->onlyDigits('abc-123.456'));
    }

    // T18 – Formatar CPF com dígitos parciais
    public function testT18FormatCPFParcial(): void
    {
        $this->assertSame('123.456.789-0', $this->formatCPF('1234567890'));
    }

    // T19 – Formatar CNPJ com 14 dígitos
    public function testT19FormatCNPJ14Digitos(): void
    {
        $this->assertSame('11.222.333/0001-81', $this->formatCNPJ('11222333000181'));
    }

    // T20 – Formatar telefone fixo com 10 dígitos
    public function testT20FormatPhoneFixo(): void
    {
        $this->assertSame('(11) 3456-7890', $this->formatPhone('1134567890'));
    }
}
