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

$input       = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$notif_id    = isset($input['notificacao_id']) ? intval($input['notificacao_id']) : null;
$marcar_todas = !empty($input['todas']);

if ($marcar_todas) {
    $stmt = $conexao->prepare(
        "UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0"
    );
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    $stmt->close();

    $retorno["status"]  = "ok";
    $retorno["mensagem"] = "Todas as notificações marcadas como lidas.";
    $retorno["data"]    = ["atualizadas" => $afetadas];

} elseif ($notif_id > 0) {
    // Garante que a notificação pertence ao usuário
    $stmt = $conexao->prepare(
        "UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?"
    );
    $stmt->bind_param("ii", $notif_id, $usuario_id);
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    $stmt->close();

    if ($afetadas > 0) {
        $retorno["status"]  = "ok";
        $retorno["mensagem"] = "Notificação marcada como lida.";
        $retorno["data"]    = ["notificacao_id" => $notif_id];
    } else {
        $retorno["mensagem"] = "Notificação não encontrada.";
    }
} else {
    $retorno["mensagem"] = "Informe notificacao_id ou todas=1.";
}

$conexao->close();

header("Content-type: application/json;charset=utf-8");
echo json_encode($retorno);
?>
