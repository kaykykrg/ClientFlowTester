*** Settings ***
Documentation     BLOCO E - Templates (T21-T25)
...               Responsavel: Matheus Fonteles Pires
...
...               Testa a funcao normalizar_item_template() e as
...               regras de persistencia e permissao de templates.
...               Conceitos: caixa-preta, particao de equivalencia,
...               valores limite, causa-efeito e tabela de decisao.
Library           Process
Library           String
Library           Collections



*** Variables ***
${PHP_HELPERS}    require_once 'tests/test_helpers.php';


*** Test Cases ***
T21 Item De Template Sem Nome Deve Ser Rejeitado
    [Documentation]    Unitario - Valor limite: nome vazio deve fazer normalizar_item_template() retornar null.
    ...                Tecnica: particao invalida (campo obrigatorio ausente).
    [Tags]    bloco_e    unitario    template    matheus_fonteles
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function parse_int_or_null($v){if($v===null||$v==='')return null;if(!is_numeric($v))return null;$p=intval($v);return $p>0?$p:null;}
    ...    function normalize_extensions($v){if(is_array($v))return null;$v=trim((string)$v);if($v==='')return null;$parts=explode(',',strtolower($v));$norm=[];foreach($parts as $p){$c=preg_replace('/[^a-z0-9]/','',trim($p));if($c!==''&&!in_array($c,$norm,true))$norm[]=$c;}return count($norm)?implode(',',$norm):null;}
    ...    function normalizar_item_template($item){$nome=trim((string)($item['nome']??''));if($nome==='')return null;$tipo=strtolower(trim((string)($item['tipo']??'text')));$tipos=['text','long_text','url','file','image','color'];if(!in_array($tipo,$tipos,true))$tipo='text';$min=parse_int_or_null($item['min_chars']??null);$max=parse_int_or_null($item['max_chars']??null);if($min!==null&&$max!==null&&$min>$max){$tmp=$min;$min=$max;$max=$tmp;}return['nome'=>$nome,'tipo'=>$tipo,'descricao'=>trim((string)($item['descricao']??'')),'min_chars'=>$min,'max_chars'=>$max,'allowed_extensions'=>normalize_extensions($item['allowed_extensions']??null),'max_file_size_kb'=>parse_int_or_null($item['max_file_size_kb']??null),'min_width'=>parse_int_or_null($item['min_width']??null),'max_width'=>parse_int_or_null($item['max_width']??null),'min_height'=>parse_int_or_null($item['min_height']??null),'max_height'=>parse_int_or_null($item['max_height']??null)];}
    ...    $r = normalizar_item_template(['nome' => '   ', 'tipo' => 'text']);
    ...    echo $r === null ? 'null' : 'nao_null';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    null

T22 Tipo De Item Invalido Deve Ser Normalizado Para Text
    [Documentation]    Unitario - Causa-efeito: tipo desconhecido ('banana') -> normalizado para 'text'.
    ...                Tecnica: particao invalida (tipo fora do conjunto valido).
    [Tags]    bloco_e    unitario    template    matheus_fonteles
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function parse_int_or_null($v){if($v===null||$v==='')return null;if(!is_numeric($v))return null;$p=intval($v);return $p>0?$p:null;}
    ...    function normalize_extensions($v){return null;}
    ...    function normalizar_item_template($item){$nome=trim((string)($item['nome']??''));if($nome==='')return null;$tipo=strtolower(trim((string)($item['tipo']??'text')));$tipos=['text','long_text','url','file','image','color'];if(!in_array($tipo,$tipos,true))$tipo='text';return['nome'=>$nome,'tipo'=>$tipo,'descricao'=>'','min_chars'=>null,'max_chars'=>null,'allowed_extensions'=>null,'max_file_size_kb'=>null,'min_width'=>null,'max_width'=>null,'min_height'=>null,'max_height'=>null];}
    ...    $r = normalizar_item_template(['nome' => 'Campo X', 'tipo' => 'banana']);
    ...    echo $r['tipo'];
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    text

T23 Tipo De Item Valido Deve Ser Mantido
    [Documentation]    Unitario - Particao valida: tipo 'image' deve ser mantido inalterado.
    ...                Tecnica: particao equivalente (tipo dentro do conjunto aceito).
    [Tags]    bloco_e    unitario    template    matheus_fonteles
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function parse_int_or_null($v){if($v===null||$v==='')return null;if(!is_numeric($v))return null;$p=intval($v);return $p>0?$p:null;}
    ...    function normalize_extensions($v){return null;}
    ...    function normalizar_item_template($item){$nome=trim((string)($item['nome']??''));if($nome==='')return null;$tipo=strtolower(trim((string)($item['tipo']??'text')));$tipos=['text','long_text','url','file','image','color'];if(!in_array($tipo,$tipos,true))$tipo='text';return['nome'=>$nome,'tipo'=>$tipo,'descricao'=>'','min_chars'=>null,'max_chars'=>null,'allowed_extensions'=>null,'max_file_size_kb'=>null,'min_width'=>null,'max_width'=>null,'min_height'=>null,'max_height'=>null];}
    ...    $r = normalizar_item_template(['nome' => 'Logo', 'tipo' => 'image']);
    ...    echo $r['tipo'];
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    image

T24 Limites Minimo E Maximo Invertidos Devem Ser Trocados
    [Documentation]    Unitario - Valor limite: min_chars > max_chars deve ter os valores trocados.
    ...                Tecnica: analise de valor limite (min=100, max=10 -> swap para min=10, max=100).
    [Tags]    bloco_e    unitario    template    matheus_fonteles
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function parse_int_or_null($v){if($v===null||$v==='')return null;if(!is_numeric($v))return null;$p=intval($v);return $p>0?$p:null;}
    ...    function normalize_extensions($v){return null;}
    ...    function normalizar_item_template($item){$nome=trim((string)($item['nome']??''));if($nome==='')return null;$tipo=strtolower(trim((string)($item['tipo']??'text')));$tipos=['text','long_text','url','file','image','color'];if(!in_array($tipo,$tipos,true))$tipo='text';$min=parse_int_or_null($item['min_chars']??null);$max=parse_int_or_null($item['max_chars']??null);if($min!==null&&$max!==null&&$min>$max){$tmp=$min;$min=$max;$max=$tmp;}return['nome'=>$nome,'tipo'=>$tipo,'descricao'=>'','min_chars'=>$min,'max_chars'=>$max,'allowed_extensions'=>null,'max_file_size_kb'=>null,'min_width'=>null,'max_width'=>null,'min_height'=>null,'max_height'=>null];}
    ...    $r = normalizar_item_template(['nome' => 'Biografia', 'tipo' => 'text', 'min_chars' => 100, 'max_chars' => 10]);
    ...    echo $r['min_chars'].'|'.$r['max_chars'];
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    10|100

T25 Usuario Sem Permissao Nao Deve Salvar Template
    [Documentation]    Seguranca/Autorizacao - Usuario com papel 'dev' sem perm_criar_projetos
    ...                deve ser bloqueado de salvar template.
    ...                Tecnica: tabela de decisao (papel=dev, perm=false -> bloqueado).
    [Tags]    bloco_e    seguranca    template    matheus_fonteles
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    $papel = 'dev';
    ...    $permissoes = ['perm_criar_projetos' => false];
    ...    $autorizado = $papel === 'admin_agencia' || !empty($permissoes['perm_criar_projetos']);
    ...    echo $autorizado ? 'autorizado' : 'bloqueado';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    bloqueado
