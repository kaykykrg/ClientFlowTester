<?php

/**
 * Converte as permissões do vínculo usuario_agencia (0/1 ou "0"/"1" vindos do MySQL)
 * em booleanos para guardar na sessão. Usada por usuario_login.php e pelos testes.
 */
function montar_permissoes_sessao($ua) {
    return [
        'perm_ver_clientes' => (bool)$ua['perm_ver_clientes'],
        'perm_criar_clientes' => (bool)$ua['perm_criar_clientes'],
        'perm_ver_projetos' => (bool)$ua['perm_ver_projetos'],
        'perm_criar_projetos' => (bool)$ua['perm_criar_projetos'],
        'perm_designar_projetos' => (bool)$ua['perm_designar_projetos'],
        'perm_gerenciar_membros' => (bool)$ua['perm_gerenciar_membros']
    ];
}
