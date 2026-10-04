<?php
/**
 * Executa UM endpoint de api/ em um processo PHP separado (CLI), como se fosse uma
 * requisição POST, com $_POST e $_SESSION definidos pelo teste.
 *
 * Uso (feito por EndpointRunner): php endpoint_child.php
 * Configuração via variável de ambiente CF_HARNESS (JSON):
 *   { "endpoint": "membro_cadastrar.php", "post": {...}, "session": {...}, "session_id": "..." }
 */
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);

$cfg = json_decode((string) getenv('CF_HARNESS'), true) ?: [];
$raizApi = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'api';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $cfg['post'] ?? [];

// Grava a sessão antes; o session_start() do próprio endpoint vai ler este arquivo.
session_save_path(sys_get_temp_dir());
session_id($cfg['session_id']);
session_start();
$_SESSION = $cfg['session'] ?? [];
session_write_close();

// Remove o arquivo de sessão ao final (inclusive quando o endpoint chama exit()).
register_shutdown_function(function () {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
});

chdir($raizApi);
require $raizApi . DIRECTORY_SEPARATOR . $cfg['endpoint'];
