*** Settings ***
Documentation     Keywords reutilizáveis para os testes do ClientFlow.
...               Contém helpers de validação de CPF, CNPJ, senha, formatação e
...               regras de negócio usadas em todos os blocos de teste.
Library           String
Library           Collections
Library           OperatingSystem
Library           Process


*** Variables ***
# CPFs para teste
${CPF_VALIDO}             529.982.247-25
${CPF_DIGITOS_REP}        111.111.111-11
${CPF_INVALIDO}           123.456.789-00
${CPF_CURTO}              123.456.78

# CNPJs para teste
${CNPJ_VALIDO}            04.252.011/0001-10
${CNPJ_INVALIDO}          00.000.000/0000-00
${CNPJ_DIGITOS_REP}       11.111.111/1111-11

# Senhas para teste
${SENHA_VALIDA}           Senha@123
${SENHA_CURTA}            Ab1!
${SENHA_SEM_MAIUSCULA}    abc12345!
${SENHA_SEM_MINUSCULA}    ABC12345!
${SENHA_SEM_NUMERO}       Abcdefg!
${SENHA_SEM_ESPECIAL}     Abcdefg1

# PHP binary (ajustado para o ambiente)
${PHP_BIN}                php


*** Keywords ***
# ─────────────────────────────────────────────────────────────────────────────
# Helpers de execução PHP inline
# ─────────────────────────────────────────────────────────────────────────────
Executar PHP Inline
    [Documentation]    Executa um trecho de código PHP via linha de comando e
    ...                retorna a saída padrão (stdout).
    [Arguments]    ${codigo_php}
    ${result}=    Run Process    ${PHP_BIN}    -r    ${codigo_php}
    ...           stdout=PIPE    stderr=PIPE
    RETURN    ${result.stdout.strip()}

Executar PHP Com Helpers
    [Documentation]    Executa código PHP carregando test_helpers.php primeiro.
    [Arguments]    ${codigo_php}
    ${helpers}=    Set Variable    require __DIR__ . '/tests/test_helpers.php';
    ${result}=    Run Process    ${PHP_BIN}    -r    ${helpers} ${codigo_php}
    ...           cwd=${CURDIR}/../../..    stdout=PIPE    stderr=PIPE
    RETURN    ${result.stdout.strip()}

# ─────────────────────────────────────────────────────────────────────────────
# Validações de CPF
# ─────────────────────────────────────────────────────────────────────────────
Validar CPF Retorna True
    [Documentation]    Chama a lógica de isValidCPF e verifica retorno true.
    [Arguments]    ${cpf}
    ${saida}=    Executar PHP Inline
    ...    function onlyDigits($v){return preg_replace('/\\D/','',$v);}
    ...    function isRepeat($v){return (bool)preg_match('/^(\\d)\\1+$/',$v);}
    ...    function isValidCPF($v){$c=onlyDigits($v);if(strlen($c)!==11||isRepeat($c))return false;
    ...    $s=0;for($i=0;$i<9;$i++)$s+=intval($c[$i])*(10-$i);$k=($s*10)%11;if($k===10)$k=0;if($k!==intval($c[9]))return false;
    ...    $s=0;for($i=0;$i<10;$i++)$s+=intval($c[$i])*(11-$i);$k=($s*10)%11;if($k===10)$k=0;return $k===intval($c[10]);}
    ...    echo isValidCPF('${cpf}') ? 'true' : 'false';
    Should Be Equal    ${saida}    true

Validar CPF Retorna False
    [Documentation]    Chama a lógica de isValidCPF e verifica retorno false.
    [Arguments]    ${cpf}
    ${saida}=    Executar PHP Inline
    ...    function onlyDigits($v){return preg_replace('/\\D/','',$v);}
    ...    function isRepeat($v){return (bool)preg_match('/^(\\d)\\1+$/',$v);}
    ...    function isValidCPF($v){$c=onlyDigits($v);if(strlen($c)!==11||isRepeat($c))return false;
    ...    $s=0;for($i=0;$i<9;$i++)$s+=intval($c[$i])*(10-$i);$k=($s*10)%11;if($k===10)$k=0;if($k!==intval($c[9]))return false;
    ...    $s=0;for($i=0;$i<10;$i++)$s+=intval($c[$i])*(11-$i);$k=($s*10)%11;if($k===10)$k=0;return $k===intval($c[10]);}
    ...    echo isValidCPF('${cpf}') ? 'true' : 'false';
    Should Be Equal    ${saida}    false

# ─────────────────────────────────────────────────────────────────────────────
# Validações de senha (lógica pura)
# ─────────────────────────────────────────────────────────────────────────────
Senha Deve Ser Rejeitada Com Mensagem
    [Documentation]    Replica validatePasswordStrength e verifica a mensagem retornada.
    [Arguments]    ${senha}    ${mensagem_esperada}
    ${saida}=    Executar PHP Inline
    ...    function validatePasswordStrength($p){ if(strlen($p)<8) return 'A senha deve ter no minimo 8 caracteres.';
    ...    if(!preg_match('/[A-Z]/',$p)) return 'A senha deve conter ao menos 1 letra maiuscula.';
    ...    if(!preg_match('/[a-z]/',$p)) return 'A senha deve conter ao menos 1 letra minuscula.';
    ...    if(!preg_match('/\\d/',$p)) return 'A senha deve conter ao menos 1 numero.';
    ...    if(!preg_match('/[^A-Za-z0-9]/',$p)) return 'A senha deve conter ao menos 1 caractere especial.';
    ...    return null;} echo validatePasswordStrength('${senha}');
    Should Be Equal    ${saida}    ${mensagem_esperada}

Senha Valida Deve Retornar Null
    [Documentation]    Uma senha que atende todos os critérios retorna null/string vazia.
    [Arguments]    ${senha}
    ${saida}=    Executar PHP Inline
    ...    function validatePasswordStrength($p){ if(strlen($p)<8) return 'curta';
    ...    if(!preg_match('/[A-Z]/',$p)) return 'sem_maiuscula';
    ...    if(!preg_match('/[a-z]/',$p)) return 'sem_minuscula';
    ...    if(!preg_match('/\\d/',$p)) return 'sem_numero';
    ...    if(!preg_match('/[^A-Za-z0-9]/',$p)) return 'sem_especial';
    ...    return null;} $r=validatePasswordStrength('${senha}'); echo $r===null ? 'ok' : $r;
    Should Be Equal    ${saida}    ok
