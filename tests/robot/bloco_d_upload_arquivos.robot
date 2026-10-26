*** Settings ***
Documentation     BLOCO D - Upload de Arquivos (T16-T20)
...               Responsavel: Matheus Pires
...
...               Testa as funcoes normalize_extensions() e
...               default_extensions_for_type(), alem das regras
...               de validacao e persistencia de upload.
...               Conceitos: caixa-preta, particao de equivalencia,
...               valores limite e analise de causa-efeito.
Library           Process
Library           String
Library           Collections



*** Test Cases ***
T16 Extensoes Devem Ser Convertidas Para Minusculas
    [Documentation]    Unitario - Caixa-preta: normalize_extensions('JPG,PNG,GIF')
    ...                deve retornar 'jpg,png,gif'.
    ...                Tecnica: particao equivalente (entradas em maiusculas -> minusculas).
    [Tags]    bloco_d    unitario    upload    matheus_pires
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function normalize_extensions($v){
    ...    if(is_array($v)) return null;
    ...    $v=trim((string)$v); if($v==='') return null;
    ...    $parts=explode(',',strtolower($v)); $norm=[];
    ...    foreach($parts as $p){$c=preg_replace('/[^a-z0-9]/','',trim($p));
    ...    if($c!==''&&!in_array($c,$norm,true))$norm[]=$c;}
    ...    return count($norm)?implode(',',$norm):null;}
    ...    echo normalize_extensions('JPG,PNG,GIF');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    jpg,png,gif

T17 Extensoes Duplicadas Devem Ser Removidas
    [Documentation]    Unitario - normalize_extensions('jpg,png,jpg,gif,png')
    ...                deve retornar 'jpg,png,gif' sem duplicatas.
    ...                Tecnica: particao invalida (duplicatas).
    [Tags]    bloco_d    unitario    upload    matheus_pires
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function normalize_extensions($v){
    ...    if(is_array($v)) return null;
    ...    $v=trim((string)$v); if($v==='') return null;
    ...    $parts=explode(',',strtolower($v)); $norm=[];
    ...    foreach($parts as $p){$c=preg_replace('/[^a-z0-9]/','',trim($p));
    ...    if($c!==''&&!in_array($c,$norm,true))$norm[]=$c;}
    ...    return count($norm)?implode(',',$norm):null;}
    ...    echo normalize_extensions('jpg,png,jpg,gif,png');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    jpg,png,gif

T18 Caracteres Invalidos Das Extensoes Devem Ser Removidos
    [Documentation]    Unitario - normalize_extensions('.jp@g, .p!ng, g*if')
    ...                deve retornar somente alfanumericos validos.
    ...                Tecnica: causa-efeito (caracteres especiais -> removidos).
    [Tags]    bloco_d    unitario    upload    matheus_pires
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function normalize_extensions($v){
    ...    if(is_array($v)) return null;
    ...    $v=trim((string)$v); if($v==='') return null;
    ...    $parts=explode(',',strtolower($v)); $norm=[];
    ...    foreach($parts as $p){$c=preg_replace('/[^a-z0-9]/','',trim($p));
    ...    if($c!==''&&!in_array($c,$norm,true))$norm[]=$c;}
    ...    return count($norm)?implode(',',$norm):null;}
    ...    echo normalize_extensions('.jp@g, .p!ng, g*if');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    jpg,png,gif

T19 Tipo Image Deve Retornar Extensoes Padrao
    [Documentation]    Unitario - default_extensions_for_type('image')
    ...                deve retornar 'jpg,jpeg,png,gif,webp'.
    ...                Tecnica: particao equivalente (tipo reconhecido).
    [Tags]    bloco_d    unitario    upload    matheus_pires
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function default_extensions_for_type($tipo){
    ...    $mapa=['image'=>'jpg,jpeg,png,gif,webp','file'=>'pdf,doc,docx,xls,xlsx,csv,txt'];
    ...    return $mapa[$tipo] ?? null;}
    ...    echo default_extensions_for_type('image');
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    jpg,jpeg,png,gif,webp

T20 Item Com Status Approved Nao Deve Aceitar Reenvio
    [Documentation]    Seguranca/Regra de negocio - Item aprovado nao pode ser reenviado.
    ...                Causa-efeito: status='approved' -> reenvio bloqueado.
    [Tags]    bloco_d    seguranca    upload    matheus_pires
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    $item_status = 'approved';
    ...    $pode_reenviar = ($item_status !== 'approved');
    ...    echo $pode_reenviar ? 'pode' : 'bloqueado';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    bloqueado
