/**
 * cadastro_exports.js
 *
 * Carrega o cadastro.js original (que usa funções globais, sem module.exports)
 * e exporta as funções para uso nos testes Jest.
 */
const fs = require('fs');
const vm = require('vm');

// Lê o arquivo original
const code = fs.readFileSync(
    require('path').join(__dirname, '../public/js/cadastro.js'),
    'utf-8'
);

// Cria um contexto isolado (sandbox) que simula o ambiente do browser
const sandbox = {
    document: {
        querySelectorAll: () => [],
        getElementById: () => null,
        activeElement: null,
    },
    console: console,
};

vm.createContext(sandbox);
vm.runInContext(code, sandbox);

// Exporta as funções que precisamos testar
module.exports = {
    onlyDigits: sandbox.onlyDigits,
    isRepeatedDigits: sandbox.isRepeatedDigits,
    hasSequentialPasswordPattern: sandbox.hasSequentialPasswordPattern,
    validatePasswordStrength: sandbox.validatePasswordStrength,
    getPasswordRules: sandbox.getPasswordRules,
    formatCPF: sandbox.formatCPF,
    formatCNPJ: sandbox.formatCNPJ,
    formatPhone: sandbox.formatPhone,
    formatDateBR: sandbox.formatDateBR,
    isValidCPF: sandbox.isValidCPF,
    isValidCNPJ: sandbox.isValidCNPJ,
    isValidPhone: sandbox.isValidPhone,
    isAdultBirthDate: sandbox.isAdultBirthDate,
    toISODateFromBR: sandbox.toISODateFromBR,
};
