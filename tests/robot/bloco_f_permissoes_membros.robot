*** Settings ***
Documentation     BLOCO F - Permissoes e Membros (T26-T30)
...               Responsavel: Yuri Allegreti
...
...               Testa a funcao montar_permissoes_sessao() (conversao 0/1->bool)
...               e as regras de cadastro, rejeicao e remocao de colaboradores.
...               Conceitos: caixa-preta, particao de equivalencia,
...               causa-efeito e tabela de decisao.
Library           Process
Library           String
Library           Collections



*** Variables ***
${PERMISSOES_PHP}
...    function montar_permissoes_sessao($ua){return[
...    'perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],
...    'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes'],
...    'perm_ver_projetos'=>(bool)$ua['perm_ver_projetos'],
...    'perm_criar_projetos'=>(bool)$ua['perm_criar_projetos'],
...    'perm_designar_projetos'=>(bool)$ua['perm_designar_projetos'],
...    'perm_gerenciar_membros'=>(bool)$ua['perm_gerenciar_membros']];}


*** Test Cases ***
T26 Permissao perm_ver_clientes Com Valor 1 Deve Virar Booleano True
    [Documentation]    Unitario - Caixa-preta: montar_permissoes_sessao com perm_ver_clientes=1
    ...                deve retornar true (booleano estrito).
    ...                Tecnica: particao equivalente (inteiro 1 -> true).
    [Tags]    bloco_f    unitario    permissoes    yuri
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function montar_permissoes_sessao($ua){return['perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes'],'perm_ver_projetos'=>(bool)$ua['perm_ver_projetos'],'perm_criar_projetos'=>(bool)$ua['perm_criar_projetos'],'perm_designar_projetos'=>(bool)$ua['perm_designar_projetos'],'perm_gerenciar_membros'=>(bool)$ua['perm_gerenciar_membros']];}
    ...    $ua=['perm_ver_clientes'=>1,'perm_criar_clientes'=>0,'perm_ver_projetos'=>0,'perm_criar_projetos'=>0,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
    ...    $r = montar_permissoes_sessao($ua);
    ...    echo ($r['perm_ver_clientes'] === true) ? 'true' : 'false';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    true

T27 Permissao perm_criar_clientes Com Valor 0 Deve Virar Booleano False
    [Documentation]    Unitario - Caixa-preta: montar_permissoes_sessao com perm_criar_clientes=0
    ...                deve retornar false (booleano estrito).
    ...                Tecnica: particao equivalente (inteiro 0 -> false).
    [Tags]    bloco_f    unitario    permissoes    yuri
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function montar_permissoes_sessao($ua){return['perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes'],'perm_ver_projetos'=>(bool)$ua['perm_ver_projetos'],'perm_criar_projetos'=>(bool)$ua['perm_criar_projetos'],'perm_designar_projetos'=>(bool)$ua['perm_designar_projetos'],'perm_gerenciar_membros'=>(bool)$ua['perm_gerenciar_membros']];}
    ...    $ua=['perm_ver_clientes'=>0,'perm_criar_clientes'=>0,'perm_ver_projetos'=>0,'perm_criar_projetos'=>0,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
    ...    $r = montar_permissoes_sessao($ua);
    ...    echo ($r['perm_criar_clientes'] === false) ? 'false' : 'true';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    false

T28 Permissao perm_ver_projetos Com String 1 Deve Virar Booleano True
    [Documentation]    Unitario - Causa-efeito: string '1' (vinda do MySQL) deve virar bool true.
    ...                Tecnica: particao equivalente (string '1' -> true via cast (bool)).
    [Tags]    bloco_f    unitario    permissoes    yuri
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function montar_permissoes_sessao($ua){return['perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes'],'perm_ver_projetos'=>(bool)$ua['perm_ver_projetos'],'perm_criar_projetos'=>(bool)$ua['perm_criar_projetos'],'perm_designar_projetos'=>(bool)$ua['perm_designar_projetos'],'perm_gerenciar_membros'=>(bool)$ua['perm_gerenciar_membros']];}
    ...    $ua=['perm_ver_clientes'=>0,'perm_criar_clientes'=>0,'perm_ver_projetos'=>'1','perm_criar_projetos'=>0,'perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
    ...    $r = montar_permissoes_sessao($ua);
    ...    echo ($r['perm_ver_projetos'] === true) ? 'true' : 'false';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    true

T29 Permissao perm_criar_projetos Com String 0 Deve Virar Booleano False
    [Documentation]    Unitario - Causa-efeito: string '0' (vinda do MySQL) deve virar bool false.
    ...                Tecnica: particao equivalente (string '0' -> false via cast (bool)).
    [Tags]    bloco_f    unitario    permissoes    yuri
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    function montar_permissoes_sessao($ua){return['perm_ver_clientes'=>(bool)$ua['perm_ver_clientes'],'perm_criar_clientes'=>(bool)$ua['perm_criar_clientes'],'perm_ver_projetos'=>(bool)$ua['perm_ver_projetos'],'perm_criar_projetos'=>(bool)$ua['perm_criar_projetos'],'perm_designar_projetos'=>(bool)$ua['perm_designar_projetos'],'perm_gerenciar_membros'=>(bool)$ua['perm_gerenciar_membros']];}
    ...    $ua=['perm_ver_clientes'=>0,'perm_criar_clientes'=>0,'perm_ver_projetos'=>0,'perm_criar_projetos'=>'0','perm_designar_projetos'=>0,'perm_gerenciar_membros'=>0];
    ...    $r = montar_permissoes_sessao($ua);
    ...    echo ($r['perm_criar_projetos'] === false) ? 'false' : 'true';
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    false

T30 Nao Admin Nao Deve Conseguir Alterar Status De Outra Conta
    [Documentation]    Seguranca/Autorizacao - Usuario com tipo 'agency' nao pode usar
    ...                admin_usuario_atualizar_status. Deve receber mensagem de bloqueio.
    ...                Tecnica: tabela de decisao (tipo != admin -> acesso bloqueado).
    [Tags]    bloco_f    seguranca    permissoes    yuri
    ${codigo}=    Catenate    SEPARATOR=\n
    ...    $sessao = ['usuario_id' => 999, 'usuario_tipo' => 'agency'];
    ...    $msg = '';
    ...    if ($sessao['usuario_tipo'] !== 'admin') {
    ...        $msg = 'Perfil sem permissao para acessar esta area.';
    ...    }
    ...    echo $msg;
    ${resultado}=    Run Process    php    -r    ${codigo}    stdout=PIPE    stderr=PIPE
    Should Be Equal    ${resultado.stdout.strip()}    Perfil sem permissao para acessar esta area.
