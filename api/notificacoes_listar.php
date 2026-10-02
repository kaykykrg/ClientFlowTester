<?php
include_once("db_conexao.php");
session_start();

$retorno = [
    "status"  => "nok",
    "mensagem" => "Não autorizado",
    "data"    => null
];

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (empty($usuario_id)) {
    header("Content-type: application/json;charset=utf-8");
    echo json_encode($retorno);
    exit();
}

$limite = 30;

// Busca as últimas N notificações do usuário
$stmt = $conexao->prepare(
    "SELECT id, tipo, titulo, mensagem, link, lida, criado_em
     FROM notificacoes
     WHERE usuario_id = ?
     ORDER BY criado_em DESC
     LIMIT ?"
);
$stmt->bind_param("ii", $usuario_id, $limite);
$stmt->execute();
$res = $stmt->get_result();

$notificacoes = [];
while ($row = $res->fetch_assoc()) {
    $notificacoes[] = [
        "id"        => intval($row['id']),
        "tipo"      => $row['tipo'],
        "titulo"    => $row['titulo'],
        "mensagem"  => $row['mensagem'],
        "link"      => $row['link'],
        "lida"      => intval($row['lida']),
        "criado_em" => $row['criado_em']
    ];
}
$stmt->close();

// Contagem de não lidas
$stmt_count = $conexao->prepare(
    "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0"
);
$stmt_count->bind_param("i", $usuario_id);
$stmt_count->execute();
$res_count = $stmt_count->get_result()->fetch_assoc();
$nao_lidas = intval($res_count['total']);
$stmt_count->close();

$conexao->close();

$retorno["status"]  = "ok";
$retorno["mensagem"] = "Notificações listadas.";
$retorno["data"] = [
    "notificacoes" => $notificacoes,
    "nao_lidas"    => $nao_lidas
];

header("Content-type: application/json;charset=utf-8");
echo json_encode($retorno);
?>
