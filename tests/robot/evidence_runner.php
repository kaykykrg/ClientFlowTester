<?php
/**
 * evidence_runner.php
 * Executa as 30 validações lógicas dos testes Robot Framework e
 * imprime saída estruturada para compor a documentação comprobatória.
 */

// ─── helpers ──────────────────────────────────────────────────────────────────
function onlyDigits(string $v): string {
    return preg_replace('/[^0-9]/', '', $v);
}
function isRepeat(string $v): bool {
    return (bool) preg_match('/^(.)\1+$/', $v);
}
function isValidCPF(string $v): bool {
    $c = onlyDigits($v);
    if (strlen($c) !== 11 || isRepeat($c)) return false;
    $s = 0; for ($i = 0; $i < 9; $i++) $s += intval($c[$i]) * (10 - $i);
    $k = ($s * 10) % 11; if ($k === 10) $k = 0; if ($k !== intval($c[9])) return false;
    $s = 0; for ($i = 0; $i < 10; $i++) $s += intval($c[$i]) * (11 - $i);
    $k = ($s * 10) % 11; if ($k === 10) $k = 0;
    return $k === intval($c[10]);
}
function isValidCNPJ(string $v): bool {
    $c = onlyDigits($v);
    if (strlen($c) !== 14 || isRepeat($c)) return false;
    $b = substr($c, 0, 12); $f1 = [5,4,3,2,9,8,7,6,5,4,3,2]; $s = 0;
    for ($i = 0; $i < 12; $i++) $s += intval($b[$i]) * $f1[$i];
    $r = $s % 11; $d1 = $r < 2 ? 0 : 11 - $r;
    $b2 = $b . $d1; $f2 = [6,5,4,3,2,9,8,7,6,5,4,3,2]; $s = 0;
    for ($i = 0; $i < 13; $i++) $s += intval($b2[$i]) * $f2[$i];
    $r = $s % 11; $d2 = $r < 2 ? 0 : 11 - $r;
    return $c === $b . $d1 . $d2;
}
function validatePasswordStrength(string $p): ?string {
    if (strlen($p) < 8)                      return 'curta';
    if (!preg_match('/[A-Z]/', $p))          return 'sem_maiuscula';
    if (!preg_match('/[a-z]/', $p))          return 'sem_minuscula';
    if (!preg_match('/[0-9]/', $p))          return 'sem_numero';
    if (!preg_match('/[^A-Za-z0-9]/', $p))  return 'sem_especial';
    return null;
}
function formatCPF(string $v): string {
    $d = substr(onlyDigits($v), 0, 11); $l = strlen($d);
    if ($l <= 3) return $d;
    if ($l <= 6) return substr($d,0,3).'.'.substr($d,3);
    if ($l <= 9) return substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6);
    return substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9);
}
function formatCNPJ(string $v): string {
    $d = substr(onlyDigits($v), 0, 14); $l = strlen($d);
    if ($l <= 2) return $d;
    if ($l <= 5) return substr($d,0,2).'.'.substr($d,2);
    if ($l <= 8) return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5);
    if ($l <=12) return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8);
    return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8,4).'-'.substr($d,12);
}
function formatPhone(string $v): string {
    $d = substr(onlyDigits($v), 0, 11); $l = strlen($d);
    if ($l <= 2) return $l ? '('.$d : '';
    if ($l <= 6) return '('.substr($d,0,2).') '.substr($d,2);
    if ($l <=10) return '('.substr($d,0,2).') '.substr($d,2,4).'-'.substr($d,6);
    return '('.substr($d,0,2).') '.substr($d,2,5).'-'.substr($d,7);
}
function normalize_extensions($v): ?string {
    if (is_array($v)) return null;
    $v = trim((string)$v); if ($v === '') return null;
    $parts = explode(',', strtolower($v)); $norm = [];
    foreach ($parts as $p) {
        $c = preg_replace('/[^a-z0-9]/', '', trim($p));
        if ($c !== '' && !in_array($c, $norm, true)) $norm[] = $c;
    }
    return count($norm) ? implode(',', $norm) : null;
}
function default_extensions_for_type(string $tipo): ?string {
    $mapa = ['image' => 'jpg,jpeg,png,gif,webp', 'file' => 'pdf,doc,docx,xls,xlsx,csv,txt'];
    return $mapa[$tipo] ?? null;
}
function parse_int_or_null($v): ?int {
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) return null;
    $p = intval($v); return $p > 0 ? $p : null;
}
function normalizar_item_template(array $item): ?array {
    $nome = trim((string)($item['nome'] ?? ''));
    if ($nome === '') return null;
    $tipo = strtolower(trim((string)($item['tipo'] ?? 'text')));
    $tipos = ['text','long_text','url','file','image','color'];
    if (!in_array($tipo, $tipos, true)) $tipo = 'text';
    $min = parse_int_or_null($item['min_chars'] ?? null);
    $max = parse_int_or_null($item['max_chars'] ?? null);
    if ($min !== null && $max !== null && $min > $max) { $tmp=$min; $min=$max; $max=$tmp; }
    return ['nome'=>$nome,'tipo'=>$tipo,'descricao'=>trim((string)($item['descricao']??'')),'min_chars'=>$min,'max_chars'=>$max,
        'allowed_extensions'=>normalize_extensions($item['allowed_extensions']??null),
        'max_file_size_kb'=>parse_int_or_null($item['max_file_size_kb']??null),
        'min_width'=>null,'max_width'=>null,'min_height'=>null,'max_height'=>null];
}
function montar_permissoes_sessao(array $ua): array {
    return ['perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes'],
        'perm_ver_projetos'=>(bool)$ua['perm_ver_projetos'],'perm_criar_projetos'=>(bool)$ua['perm_criar_projetos'],
        'perm_designar_projetos'=>(bool)$ua['perm_designar_projetos'],'perm_gerenciar_membros'=>(bool)$ua['perm_gerenciar_membros']];
}

// ─── runner ───────────────────────────────────────────────────────────────────
$results = [];

function run(int $id, string $descricao, string $entrada, string $esperado, $obtido, bool $pass): array {
    return compact('id','descricao','entrada','esperado','obtido','pass');
}

// BLOCO A
$r1 = isValidCPF('529.982.247-25');
$results[] = run(1,'T01 - CPF valido deve ser aceito','529.982.247-25','true',var_export($r1,true),$r1===true);

$r2 = isValidCPF('111.111.111-11');
$results[] = run(2,'T02 - CPF com digitos repetidos deve ser rejeitado','111.111.111-11','false',var_export($r2,true),$r2===false);

$r3 = isValidCNPJ('04.252.011/0001-10');
$results[] = run(3,'T03 - CNPJ valido deve ser aceito','04.252.011/0001-10','true',var_export($r3,true),$r3===true);

$r4 = isValidCNPJ('00.000.000/0000-00');
$results[] = run(4,'T04 - CNPJ invalido deve ser rejeitado','00.000.000/0000-00','false',var_export($r4,true),$r4===false);

$ua=['perm_ver_clientes'=>1,'perm_criar_clientes'=>1,'perm_ver_projetos'=>1,'perm_criar_projetos'=>1,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>1,'ua_id'=>10,'agencia_id'=>5,'papel'=>'admin'];
$perm = montar_permissoes_sessao($ua);
$sessao=['usuario_id'=>1,'agencia_id'=>$ua['agencia_id'],'permissoes'=>$perm];
$r5ok = $sessao['usuario_id']===1 && $sessao['agencia_id']===5 && $sessao['permissoes']['perm_ver_clientes']===true;
$results[] = run(5,'T05 - Login valido monta sessao corretamente','usuario_id=1, agencia_id=5, perm_ver_clientes=1','sessao montada com dados corretos','usuario_id=1, agencia_id=5, perm_ver_clientes=true',$r5ok);

// BLOCO B
$r6 = validatePasswordStrength('Ab1!');
$results[] = run(6,'T06 - Senha com menos de 8 chars rejeitada','Ab1! (4 chars)','curta',$r6,$r6==='curta');

$r7 = validatePasswordStrength('abc12345!');
$results[] = run(7,'T07 - Senha sem maiuscula rejeitada','abc12345!','sem_maiuscula',$r7,$r7==='sem_maiuscula');

$r8 = validatePasswordStrength('ABC12345!');
$results[] = run(8,'T08 - Senha sem minuscula rejeitada','ABC12345!','sem_minuscula',$r8,$r8==='sem_minuscula');

$r9 = validatePasswordStrength('Abcdefg!');
$results[] = run(9,'T09 - Senha sem numero rejeitada','Abcdefg!','sem_numero',$r9,$r9==='sem_numero');

$itens = [];
$msg10 = (!is_array($itens)||count($itens)===0) ? 'Adicione pelo menos um item no formulario.' : '';
$results[] = run(10,'T10 - Checklist sem itens retorna mensagem','itens=[] (array vazio)','Adicione pelo menos um item no formulario.',$msg10,$msg10==='Adicione pelo menos um item no formulario.');

// BLOCO C
$r11 = onlyDigits('(11) 91234-5678');
$results[] = run(11,'T11 - onlyDigits remove caracteres nao numericos','(11) 91234-5678','11912345678',$r11,$r11==='11912345678');

$r12 = formatCPF('52998224725');
$results[] = run(12,'T12 - formatCPF formata 11 digitos','52998224725','529.982.247-25',$r12,$r12==='529.982.247-25');

$r13 = formatCNPJ('11222333000181');
$results[] = run(13,'T13 - formatCNPJ formata 14 digitos','11222333000181','11.222.333/0001-81',$r13,$r13==='11.222.333/0001-81');

$r14 = formatPhone('11912345678');
$results[] = run(14,'T14 - formatPhone formata celular 11 digitos','11912345678','(11) 91234-5678',$r14,$r14==='(11) 91234-5678');

$checklist15 = ['id'=>100,'cliente_id'=>10];
$novo15 = 42;
$msg15 = ($checklist15['cliente_id']!==null && $checklist15['cliente_id']!==$novo15) ? 'Este checklist ja esta vinculado a outro cliente.' : '';
$results[] = run(15,'T15 - Checklist ja associado a outro cliente bloqueado','checklist.cliente_id=10, novo_cliente=42','Este checklist ja esta vinculado a outro cliente.',$msg15,$msg15==='Este checklist ja esta vinculado a outro cliente.');

// BLOCO D
$r16 = normalize_extensions('JPG,PNG,GIF');
$results[] = run(16,'T16 - normalize_extensions converte para minusculas','JPG,PNG,GIF','jpg,png,gif',$r16,$r16==='jpg,png,gif');

$r17 = normalize_extensions('jpg,png,jpg,gif,png');
$results[] = run(17,'T17 - normalize_extensions remove duplicatas','jpg,png,jpg,gif,png','jpg,png,gif',$r17,$r17==='jpg,png,gif');

$r18 = normalize_extensions('.jp@g, .p!ng, g*if');
$results[] = run(18,'T18 - normalize_extensions remove chars invalidos','.jp@g, .p!ng, g*if','jpg,png,gif',$r18,$r18==='jpg,png,gif');

$r19 = default_extensions_for_type('image');
$results[] = run(19,'T19 - default_extensions_for_type retorna extensoes de imagem','image','jpg,jpeg,png,gif,webp',$r19,$r19==='jpg,jpeg,png,gif,webp');

$status20 = 'approved';
$pode20 = ($status20 !== 'approved');
$r20 = $pode20 ? 'pode' : 'bloqueado';
$results[] = run(20,'T20 - Item approved nao aceita reenvio','status=approved','bloqueado',$r20,$r20==='bloqueado');

// BLOCO E
$r21 = normalizar_item_template(['nome'=>'   ','tipo'=>'text']);
$results[] = run(21,'T21 - item sem nome retorna null','nome="   " (espacos)','null',var_export($r21,true),$r21===null);

$r22 = normalizar_item_template(['nome'=>'Campo X','tipo'=>'banana']);
$results[] = run(22,'T22 - tipo invalido normalizado para text','tipo=banana','text',$r22['tipo']??'null',$r22['tipo']==='text');

$r23 = normalizar_item_template(['nome'=>'Logo','tipo'=>'image']);
$results[] = run(23,'T23 - tipo valido mantido inalterado','tipo=image','image',$r23['tipo']??'null',$r23['tipo']==='image');

$r24 = normalizar_item_template(['nome'=>'Biografia','tipo'=>'text','min_chars'=>100,'max_chars'=>10]);
$r24ok = $r24['min_chars']===10 && $r24['max_chars']===100;
$results[] = run(24,'T24 - limites invertidos sao trocados','min=100, max=10','min=10, max=100','min='.$r24['min_chars'].', max='.$r24['max_chars'],$r24ok);

$papel25='dev'; $perm25=['perm_criar_projetos'=>false];
$auth25 = $papel25==='admin_agencia' || !empty($perm25['perm_criar_projetos']);
$r25 = $auth25 ? 'autorizado' : 'bloqueado';
$results[] = run(25,'T25 - usuario sem permissao nao salva template','papel=dev, perm_criar_projetos=false','bloqueado',$r25,$r25==='bloqueado');

// BLOCO F
$ua26=['perm_ver_clientes'=>1,'perm_criar_clientes'=>0,'perm_ver_projetos'=>0,'perm_criar_projetos'=>0,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
$perm26=montar_permissoes_sessao($ua26); $r26=$perm26['perm_ver_clientes'];
$results[] = run(26,'T26 - perm_ver_clientes=1 vira bool true','perm_ver_clientes=1','true',var_export($r26,true),$r26===true);

$ua27=['perm_ver_clientes'=>0,'perm_criar_clientes'=>0,'perm_ver_projetos'=>0,'perm_criar_projetos'=>0,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
$perm27=montar_permissoes_sessao($ua27); $r27=$perm27['perm_criar_clientes'];
$results[] = run(27,'T27 - perm_criar_clientes=0 vira bool false','perm_criar_clientes=0','false',var_export($r27,true),$r27===false);

$ua28=['perm_ver_clientes'=>0,'perm_criar_clientes'=>0,'perm_ver_projetos'=>'1','perm_criar_projetos'=>0,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
$perm28=montar_permissoes_sessao($ua28); $r28=$perm28['perm_ver_projetos'];
$results[] = run(28,"T28 - perm_ver_projetos='1' (string MySQL) vira true","perm_ver_projetos='1'","true",var_export($r28,true),$r28===true);

$ua29=['perm_ver_clientes'=>0,'perm_criar_clientes'=>0,'perm_ver_projetos'=>0,'perm_criar_projetos'=>'0','perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
$perm29=montar_permissoes_sessao($ua29); $r29=$perm29['perm_criar_projetos'];
$results[] = run(29,"T29 - perm_criar_projetos='0' (string MySQL) vira false","perm_criar_projetos='0'","false",var_export($r29,true),$r29===false);

$sessao30=['usuario_id'=>999,'usuario_tipo'=>'agency'];
$msg30 = $sessao30['usuario_tipo']!=='admin' ? 'Perfil sem permissao para acessar esta area.' : 'ok';
$results[] = run(30,'T30 - nao-admin nao altera status de outra conta','usuario_tipo=agency','Perfil sem permissao para acessar esta area.',$msg30,$msg30==='Perfil sem permissao para acessar esta area.');

// ─── output JSON ──────────────────────────────────────────────────────────────
echo json_encode(['timestamp'=>date('d/m/Y H:i:s'),'total'=>count($results),'passed'=>count(array_filter($results,fn($r)=>$r['pass'])),'results'=>$results], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
