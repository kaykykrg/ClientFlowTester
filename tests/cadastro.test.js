/**
 * cadastro.test.js
 *
 * Testes unitários dos Blocos A, B e C (T01 a T20) – funções JavaScript
 * do arquivo cadastro.js (validação de CPF, CNPJ, senha, formatação, etc.)
 *
 * Framework: Jest
 */

const {
    onlyDigits,
    isValidCPF,
    isValidCNPJ,
    validatePasswordStrength,
    formatCPF,
    formatCNPJ,
    formatPhone,
} = require('./cadastro_exports');

// ============================================================
// BLOCO A – 8 testes (T01 a T08)
// ============================================================

describe('Bloco A – Validação e Formatação básica', () => {

    // T01 – CPF válido deve ser aceito
    test('T01 – CPF válido deve ser aceito', () => {
        expect(isValidCPF('529.982.247-25')).toBe(true);
    });

    // T02 – CPF com dígitos repetidos deve ser rejeitado
    test('T02 – CPF com dígitos repetidos deve ser rejeitado', () => {
        expect(isValidCPF('111.111.111-11')).toBe(false);
    });

    // T03 – CNPJ válido deve ser aceito
    test('T03 – CNPJ válido deve ser aceito', () => {
        expect(isValidCNPJ('04.252.011/0001-10')).toBe(true);
    });

    // T04 – CNPJ inválido deve ser rejeitado
    test('T04 – CNPJ inválido deve ser rejeitado', () => {
        expect(isValidCNPJ('00.000.000/0000-00')).toBe(false);
    });

    // T05 – Remover caracteres não numéricos de uma entrada
    test('T05 – onlyDigits remove caracteres não numéricos', () => {
        expect(onlyDigits('(11) 91234-5678')).toBe('11912345678');
    });

    // T06 – Formatar CPF corretamente
    test('T06 – formatCPF formata 11 dígitos em XXX.XXX.XXX-XX', () => {
        expect(formatCPF('52998224725')).toBe('529.982.247-25');
    });

    // T07 – Formatar CNPJ corretamente
    test('T07 – formatCNPJ formata 14 dígitos em XX.XXX.XXX/XXXX-XX', () => {
        expect(formatCNPJ('04252011000110')).toBe('04.252.011/0001-10');
    });

    // T08 – Formatar telefone corretamente
    test('T08 – formatPhone formata celular com 11 dígitos', () => {
        expect(formatPhone('11912345678')).toBe('(11) 91234-5678');
    });

});

// ============================================================
// BLOCO B – 4 testes unitários de JS (T09 a T12)
// ============================================================

describe('Bloco B – Validação de senha', () => {

    // T09 – Senha com menos de 8 caracteres deve ser rejeitada
    test('T09 – Senha curta é rejeitada', () => {
        const msg = validatePasswordStrength('Ab1!');
        expect(msg).toBe('A senha deve ter no mínimo 8 caracteres.');
    });

    // T10 – Senha sem letra maiúscula deve ser rejeitada
    test('T10 – Senha sem maiúscula é rejeitada', () => {
        const msg = validatePasswordStrength('ab1!defg');
        expect(msg).toBe('A senha deve conter ao menos 1 letra maiúscula.');
    });

    // T11 – Senha sem letra minúscula deve ser rejeitada
    test('T11 – Senha sem minúscula é rejeitada', () => {
        const msg = validatePasswordStrength('AB1!DEFG');
        expect(msg).toBe('A senha deve conter ao menos 1 letra minúscula.');
    });

    // T12 – Senha sem número deve ser rejeitada
    test('T12 – Senha sem número é rejeitada', () => {
        const msg = validatePasswordStrength('Abc!defg');
        expect(msg).toBe('A senha deve conter ao menos 1 número.');
    });

});

// ============================================================
// BLOCO C – 4 testes unitários de JS (T17 a T20)
// ============================================================

describe('Bloco C – Formatação (onlyDigits, CPF, CNPJ, Phone)', () => {

    // T17 – Remover caracteres não numéricos de uma entrada
    test('T17 – onlyDigits remove não numéricos (caso diferente)', () => {
        expect(onlyDigits('abc-123.456')).toBe('123456');
    });

    // T18 – Formatar CPF corretamente
    test('T18 – formatCPF com dígitos parciais', () => {
        expect(formatCPF('1234567890')).toBe('123.456.789-0');
    });

    // T19 – Formatar CNPJ corretamente
    test('T19 – formatCNPJ com 14 dígitos', () => {
        expect(formatCNPJ('11222333000181')).toBe('11.222.333/0001-81');
    });

    // T20 – Formatar telefone corretamente
    test('T20 – formatPhone com telefone fixo 10 dígitos', () => {
        expect(formatPhone('1134567890')).toBe('(11) 3456-7890');
    });

});
