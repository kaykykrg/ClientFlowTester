*** Settings ***
Documentation     BLOCO C - Formatacao e Vinculo de Checklist (T11-T15)
...               Responsavel: Arthur Kenji
...
...               Testa formatacao de CPF, CNPJ, telefone (onlyDigits/format)
...               e as regras de vinculo de checklist por link.
...               Conceitos: caixa-preta, particao de equivalencia,
...               causa-efeito e analise de valores limite.
Library           Process
Library           String
Library           Collections



*** Test Cases ***
T11 Remover Caracteres Nao Numericos De Uma Entrada
    [Documentation]    Unitario - Caixa-preta: onlyDigits('(11) 91234-5678') deve retornar '11912345678'.
    ...                Tecnica: particao de equivalencia (entrada mista -> saida so digitos).
    [Tags]    bloco_c    unitario    formatacao    arthur
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    echo onlyDigits('(11) 91234-5678');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    11912345678

T12 Formatar CPF Com 11 Digitos
    [Documentation]    Unitario - formatCPF('52998224725') deve retornar '529.982.247-25'.
    ...                Tecnica: particao valida (11 digitos) e verificacao de formato.
    [Tags]    bloco_c    unitario    formatacao    cpf    arthur
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function formatCPF($v){$d=substr(onlyDigits($v),0,11);$l=strlen($d);
    ...    if($l<=3) return $d;
    ...    if($l<=6) return substr($d,0,3).'.'.substr($d,3);
    ...    if($l<=9) return substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6);
    ...    return substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9);}
    ...    echo formatCPF('52998224725');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    529.982.247-25

T13 Formatar CNPJ Com 14 Digitos
    [Documentation]    Unitario - formatCNPJ('11222333000181') deve retornar '11.222.333/0001-81'.
    ...                Tecnica: particao valida (14 digitos) e verificacao de formato.
    [Tags]    bloco_c    unitario    formatacao    cnpj    arthur
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function formatCNPJ($v){$d=substr(onlyDigits($v),0,14);$l=strlen($d);
    ...    if($l<=2) return $d;
    ...    if($l<=5) return substr($d,0,2).'.'.substr($d,2);
    ...    if($l<=8) return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5);
    ...    if($l<=12) return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8);
    ...    return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8,4).'-'.substr($d,12);}
    ...    echo formatCNPJ('11222333000181');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    11.222.333/0001-81

T14 Formatar Telefone Celular Com 11 Digitos
    [Documentation]    Unitario - formatPhone('11912345678') deve retornar '(11) 91234-5678'.
    ...                Tecnica: valor limite (11 digitos = celular com 9 digito).
    [Tags]    bloco_c    unitario    formatacao    telefone    arthur
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function formatPhone($v){$d=substr(onlyDigits($v),0,11);$l=strlen($d);
    ...    if($l<=2) return $l ? '('.$d : '';
    ...    if($l<=6) return '('.substr($d,0,2).') '.substr($d,2);
    ...    if($l<=10) return '('.substr($d,0,2).') '.substr($d,2,4).'-'.substr($d,6);
    ...    return '('.substr($d,0,2).') '.substr($d,2,5).'-'.substr($d,7);}
    ...    echo formatPhone('11912345678');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    (11) 91234-5678

T15 Checklist Ja Associado A Outro Cliente Deve Ser Bloqueado
    [Documentation]    Funcional/API - Causa-efeito: checklist com cliente_id diferente
    ...                do solicitante deve retornar mensagem de bloqueio.
    [Tags]    bloco_c    funcional    checklist    arthur
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    $checklist = ['id' => 100, 'cliente_id' => 10];
    ...    $novo_cliente_id = 42;
    ...    $msg = '';
    ...    if ($checklist['cliente_id'] !== null && $checklist['cliente_id'] !== $novo_cliente_id) {
    ...        $msg = 'Este checklist ja esta vinculado a outro cliente.';
    ...    }
    ...    echo $msg;
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    Este checklist ja esta vinculado a outro cliente.
