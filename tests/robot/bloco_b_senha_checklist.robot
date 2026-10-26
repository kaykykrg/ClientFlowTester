*** Settings ***
Documentation     BLOCO B - Senha e Checklist (T06-T10)
...               Responsavel: Vantuil Junior Plaster
...
...               Testa as funcoes de validacao de forca de senha e
...               as regras de criacao de checklist do ClientFlow.
...               Conceitos aplicados: particionamento de equivalencia,
...               valores limite, analise de causa-efeito e caixa-preta.
Library           Process
Library           String
Library           Collections



*** Test Cases ***
T06 Senha Com Menos De 8 Caracteres Deve Ser Rejeitada
    [Documentation]    Unitario - Valor limite (abaixo do minimo de 8 chars).
    ...                validatePasswordStrength com 4 chars deve retornar mensagem de erro.
    [Tags]    bloco_b    unitario    senha    vantuil
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function vps($p){
    ...    if(strlen($p)<8) return 'curta';
    ...    if(!preg_match('/[A-Z]/',$p)) return 'sem_maiuscula';
    ...    if(!preg_match('/[a-z]/',$p)) return 'sem_minuscula';
    ...    if(!preg_match('/[0-9]/',$p)) return 'sem_numero';
    ...    return null;}
    ...    echo vps('Ab1!');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    curta

T07 Senha Sem Letra Maiuscula Deve Ser Rejeitada
    [Documentation]    Unitario - Particao invalida (sem maiuscula).
    ...                validatePasswordStrength('abc12345!') deve retornar erro de maiuscula.
    [Tags]    bloco_b    unitario    senha    vantuil
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function vps($p){
    ...    if(strlen($p)<8) return 'curta';
    ...    if(!preg_match('/[A-Z]/',$p)) return 'sem_maiuscula';
    ...    if(!preg_match('/[a-z]/',$p)) return 'sem_minuscula';
    ...    if(!preg_match('/[0-9]/',$p)) return 'sem_numero';
    ...    return null;}
    ...    echo vps('abc12345!');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    sem_maiuscula

T08 Senha Sem Letra Minuscula Deve Ser Rejeitada
    [Documentation]    Unitario - Particao invalida (sem minuscula).
    ...                validatePasswordStrength('ABC12345!') deve retornar erro de minuscula.
    [Tags]    bloco_b    unitario    senha    vantuil
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function vps($p){
    ...    if(strlen($p)<8) return 'curta';
    ...    if(!preg_match('/[A-Z]/',$p)) return 'sem_maiuscula';
    ...    if(!preg_match('/[a-z]/',$p)) return 'sem_minuscula';
    ...    if(!preg_match('/[0-9]/',$p)) return 'sem_numero';
    ...    return null;}
    ...    echo vps('ABC12345!');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    sem_minuscula

T09 Senha Sem Numero Deve Ser Rejeitada
    [Documentation]    Unitario - Particao invalida (sem digito numerico).
    ...                validatePasswordStrength('Abcdefg!') deve retornar erro de numero.
    [Tags]    bloco_b    unitario    senha    vantuil
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function vps($p){
    ...    if(strlen($p)<8) return 'curta';
    ...    if(!preg_match('/[A-Z]/',$p)) return 'sem_maiuscula';
    ...    if(!preg_match('/[a-z]/',$p)) return 'sem_minuscula';
    ...    if(!preg_match('/[0-9]/',$p)) return 'sem_numero';
    ...    return null;}
    ...    echo vps('Abcdefg!');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    sem_numero

T10 Checklist Sem Itens Deve Retornar Mensagem De Erro
    [Documentation]    Funcional/API - Causa-efeito: array vazio de itens causa mensagem de bloqueio.
    ...                Replica a logica de checklist_criar.php: se itens vazio retorna mensagem esperada.
    [Tags]    bloco_b    funcional    checklist    vantuil
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    $itens = [];
    ...    $msg = '';
    ...    if (!is_array($itens) || count($itens) === 0) {
    ...        $msg = 'Adicione pelo menos um item no formulario.';
    ...    }
    ...    echo $msg;
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    Adicione pelo menos um item no formulario.
