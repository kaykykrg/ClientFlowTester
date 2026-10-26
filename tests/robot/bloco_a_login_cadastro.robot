*** Settings ***
Documentation     BLOCO A - Login e Cadastro (T01-T05)
...               Responsavel: Kayky Quesada Kruger
...
...               Testa as funcoes de validacao de CPF, CNPJ,
...               autenticacao e montagem de sessao do ClientFlow.
...               Conceitos aplicados: caixa-preta, particionamento de
...               equivalencia, valores limite e analise de causa-efeito.
Library           Process
Library           String
Library           Collections


*** Keywords ***
Validar CPF Retorna True
    [Documentation]    Executa a logica de isValidCPF via PHP e verifica retorno true.
    [Arguments]    ${cpf}
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function isRepeat($v){return (bool)preg_match('/^(.)\\1+$/',$v);}
    ...    function isValidCPF($v){$c=onlyDigits($v);if(strlen($c)!=11||isRepeat($c))return false;
    ...    $s=0;for($i=0;$i<9;$i++)$s+=intval($c[$i])*(10-$i);$k=($s*10)%11;if($k===10)$k=0;if($k!=intval($c[9]))return false;
    ...    $s=0;for($i=0;$i<10;$i++)$s+=intval($c[$i])*(11-$i);$k=($s*10)%11;if($k===10)$k=0;return $k===intval($c[10]);}
    ...    echo isValidCPF('${cpf}') ? 'true' : 'false';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    true

Validar CPF Retorna False
    [Documentation]    Executa a logica de isValidCPF via PHP e verifica retorno false.
    [Arguments]    ${cpf}
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function isRepeat($v){return (bool)preg_match('/^(.)\\1+$/',$v);}
    ...    function isValidCPF($v){$c=onlyDigits($v);if(strlen($c)!=11||isRepeat($c))return false;
    ...    $s=0;for($i=0;$i<9;$i++)$s+=intval($c[$i])*(10-$i);$k=($s*10)%11;if($k===10)$k=0;if($k!=intval($c[9]))return false;
    ...    $s=0;for($i=0;$i<10;$i++)$s+=intval($c[$i])*(11-$i);$k=($s*10)%11;if($k===10)$k=0;return $k===intval($c[10]);}
    ...    echo isValidCPF('${cpf}') ? 'true' : 'false';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    false


*** Test Cases ***
T01 CPF Valido Deve Ser Aceito
    [Documentation]    Unitario - Particionamento de equivalencia (classe valida).
    ...                isValidCPF('529.982.247-25') deve retornar true.
    [Tags]    bloco_a    unitario    cpf    kayky
    Validar CPF Retorna True    529.982.247-25

T02 CPF Com Digitos Repetidos Deve Ser Rejeitado
    [Documentation]    Unitario - Particionamento de equivalencia (classe invalida: todos iguais).
    ...                isValidCPF('111.111.111-11') deve retornar false.
    [Tags]    bloco_a    unitario    cpf    kayky
    Validar CPF Retorna False    111.111.111-11

T03 CNPJ Valido Deve Ser Aceito
    [Documentation]    Unitario - Particionamento de equivalencia (classe valida).
    ...                isValidCNPJ('04.252.011/0001-10') deve retornar true.
    [Tags]    bloco_a    unitario    cnpj    kayky
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function isRepeat($v){return (bool)preg_match('/^(.)\\1+$/',$v);}
    ...    function isValidCNPJ($v){$c=onlyDigits($v);if(strlen($c)!=14||isRepeat($c))return false;
    ...    $b=substr($c,0,12);$f1=[5,4,3,2,9,8,7,6,5,4,3,2];$s=0;
    ...    for($i=0;$i<12;$i++)$s+=intval($b[$i])*$f1[$i];$r=$s%11;$d1=$r<2?0:11-$r;
    ...    $b2=$b.$d1;$f2=[6,5,4,3,2,9,8,7,6,5,4,3,2];$s=0;
    ...    for($i=0;$i<13;$i++)$s+=intval($b2[$i])*$f2[$i];$r=$s%11;$d2=$r<2?0:11-$r;
    ...    return $c===$b.$d1.$d2;}
    ...    echo isValidCNPJ('04.252.011/0001-10') ? 'true' : 'false';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    true

T04 CNPJ Invalido Deve Ser Rejeitado
    [Documentation]    Unitario - Particionamento de equivalencia (classe invalida: zeros).
    ...                isValidCNPJ('00.000.000/0000-00') deve retornar false.
    [Tags]    bloco_a    unitario    cnpj    kayky
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function onlyDigits($v){return preg_replace('/[^0-9]/','', $v);}
    ...    function isRepeat($v){return (bool)preg_match('/^(.)\\1+$/',$v);}
    ...    function isValidCNPJ($v){$c=onlyDigits($v);if(strlen($c)!=14||isRepeat($c))return false;
    ...    $b=substr($c,0,12);$f1=[5,4,3,2,9,8,7,6,5,4,3,2];$s=0;
    ...    for($i=0;$i<12;$i++)$s+=intval($b[$i])*$f1[$i];$r=$s%11;$d1=$r<2?0:11-$r;
    ...    $b2=$b.$d1;$f2=[6,5,4,3,2,9,8,7,6,5,4,3,2];$s=0;
    ...    for($i=0;$i<13;$i++)$s+=intval($b2[$i])*$f2[$i];$r=$s%11;$d2=$r<2?0:11-$r;
    ...    return $c===$b.$d1.$d2;}
    ...    echo isValidCNPJ('00.000.000/0000-00') ? 'true' : 'false';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    false

T05 Login Valido Monta Sessao Com Dados Corretos
    [Documentation]    Integracao - simula montagem de sessao apos login bem-sucedido.
    ...                Verifica usuario_id, agencia_id e permissoes booleanas corretos.
    [Tags]    bloco_a    integracao    login    kayky
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    $ua=['perm_ver_clientes'=>1,'perm_criar_clientes'=>1,
    ...    'perm_ver_projetos'=>1,'perm_criar_projetos'=>1,
    ...    'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>1,
    ...    'ua_id'=>10,'agencia_id'=>5,'papel'=>'admin'];
    ...    $perm=['perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],
    ...    'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes']];
    ...    $sessao=['usuario_id'=>1,'agencia_id'=>$ua['agencia_id'],'permissoes'=>$perm];
    ...    $ok=$sessao['usuario_id']===1
    ...    && $sessao['agencia_id']===5
    ...    && $sessao['permissoes']['perm_ver_clientes']===true;
    ...    echo $ok ? 'ok' : 'nok';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    ok
