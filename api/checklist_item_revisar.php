<?php
include_once("db_conexao.php");
include_once("notificacao_criar_helper.php");
session_start();

$retorno = [
    "status" => "nok",
    "mensagem" => "Usuário não autenticado",
    "data" => null
];

$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_tipo = $_SESSION['usuario_tipo'] ?? null;
$agencia_id = $_SESSION['agencia_id'] ?? null;
$ua_id = $_SESSION['ua_id'] ?? null;
$papel_agencia = $_SESSION['papel_agencia'] ?? null;
$permissoes = $_SESSION['permissoes'] ?? [];

if (empty($usuario_id)) {
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

if ($usuario_tipo === "client") {
    $retorno["mensagem"] = "Perfil sem permissão para revisar itens.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

if (($usuario_tipo === 'agency' || $usuario_tipo === 'agency_member' || $usuario_tipo === 'freelancer') && empty($permissoes['perm_ver_projetos'])) {
    $retorno["mensagem"] = "Você não tem permissão para revisar itens.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

if (empty($agencia_id)) {
    $retorno["mensagem"] = "Agência não identificada na sessão.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$item_id = intval($input['item_id'] ?? 0);
$acao = trim($input['acao'] ?? '');
$motivo = trim($input['motivo'] ?? '');

if ($item_id <= 0 || !in_array($acao, ["aprovar", "reprovar"], true)) {
    $retorno["mensagem"] = "Parâmetros inválidos.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

if ($acao === "reprovar" && $motivo === "") {
    $retorno["mensagem"] = "Informe o motivo da reprovação.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

$sql_check = "SELECT i.id, i.checklist_id
     FROM itens_checklist i
     INNER JOIN checklists c ON c.id = i.checklist_id";

if ($usuario_tipo === 'agency_member' && $papel_agencia === 'dev') {
    $sql_check .= " INNER JOIN projetos_membros pm ON pm.checklist_id = c.id
    WHERE i.id = ? AND c.agencia_id = ? AND pm.usuario_agencia_id = ?";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bind_param("iii", $item_id, $agencia_id, $ua_id);
} else {
    $sql_check .= " WHERE i.id = ? AND c.agencia_id = ?";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bind_param("ii", $item_id, $agencia_id);
}
$stmt_check->execute();
$check_result = $stmt_check->get_result();

if ($check_result->num_rows !== 1) {
    $retorno["mensagem"] = "Item não encontrado para esta agência.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    $stmt_check->close();
    $conexao->close();
    exit();
}
$item_data = $check_result->fetch_assoc();
$checklist_id_revisar = intval($item_data['checklist_id']);
$stmt_check->close();

$novo_status = $acao === "aprovar" ? "approved" : "rejected";
$novo_motivo = $acao === "aprovar" ? null : $motivo;

$stmt_update = $conexao->prepare(
    "UPDATE itens_checklist
     SET status = ?, motivo_rejeicao = ?
     WHERE id = ?"
);
$stmt_update->bind_param("ssi", $novo_status, $novo_motivo, $item_id);

if ($stmt_update->execute()) {
    atualizar_status_checklist($conexao, $checklist_id_revisar);

    // --- Notificações: aviso ao cliente sobre o item revisado ---
    // Busca dados do checklist, cliente e agencia
    $stmt_info = $conexao->prepare(
        "SELECT ch.titulo, ch.agencia_id, ch.cliente_id,
                u.id AS usuario_cliente_id, u.nome AS cliente_nome
         FROM checklists ch
         LEFT JOIN clientes cl ON cl.id = ch.cliente_id
         LEFT JOIN usuarios u  ON u.id  = cl.usuario_id
         WHERE ch.id = ? LIMIT 1"
    );
    $stmt_info->bind_param("i", $checklist_id_revisar);
    $stmt_info->execute();
    $info_res = $stmt_info->get_result();

    if ($info_res->num_rows > 0) {
        $info = $info_res->fetch_assoc();
        $titulo_ch         = $info['titulo'];
        $agencia_id_notif  = intval($info['agencia_id']);
        $usuario_cliente   = $info['usuario_cliente_id'] ? intval($info['usuario_cliente_id']) : null;
        $link_ch           = "public/pages/dashboard_client.html";

        if ($usuario_cliente) {
            if ($acao === 'aprovar') {
                criar_notificacao(
                    $conexao, $usuario_cliente,
                    'item_aprovado',
                    "✅ Item aprovado!",
                    "Um item seu foi aprovado no projeto \"{$titulo_ch}\". Continue enviando os demais!",
                    $link_ch
                );
            } else {
                criar_notificacao(
                    $conexao, $usuario_cliente,
                    'item_reprovado',
                    "🔄 Item devolvido para correção",
                    "Um item do projeto \"{$titulo_ch}\" precisou ser corrigido. Motivo: {$motivo}",
                    $link_ch
                );
            }
        }

        // Verifica se todos os itens foram aprovados (checklist concluído)
        $stmt_concluido = $conexao->prepare(
            "SELECT COUNT(*) as total,
                    SUM(status = 'approved') as aprovados
             FROM itens_checklist WHERE checklist_id = ?"
        );
        $stmt_concluido->bind_param("i", $checklist_id_revisar);
        $stmt_concluido->execute();
        $row_c = $stmt_concluido->get_result()->fetch_assoc();
        $stmt_concluido->close();

        if (intval($row_c['total']) > 0 && intval($row_c['aprovados']) === intval($row_c['total'])) {
            // Notifica o cliente
            if ($usuario_cliente) {
                criar_notificacao(
                    $conexao, $usuario_cliente,
                    'checklist_concluido',
                    "🎉 Projeto concluído!",
                    "Todos os itens do projeto \"{$titulo_ch}\" foram aprovados. Parabéns!",
                    $link_ch
                );
            }
            // Notifica todos os membros da agência
            criar_notificacoes_agencia(
                $conexao,
                $agencia_id_notif,
                $checklist_id_revisar,
                'checklist_concluido',
                "🎉 Projeto concluído!",
                "Todos os itens do projeto \"{$titulo_ch}\" foram aprovados pelo cliente.",
                "public/pages/checklist_details.html?id={$checklist_id_revisar}"
            );
        }
    }
    $stmt_info->close();
    // --- Fim notificações ---

    $retorno["status"] = "ok";
    $retorno["mensagem"] = $acao === "aprovar" ? "Item aprovado." : "Item reprovado e devolvido ao cliente.";
    $retorno["data"] = [
        "item_id" => $item_id,
        "status"  => $novo_status
    ];
} else {
    $retorno["mensagem"] = "Erro ao revisar item.";
}

$stmt_update->close();
$conexao->close();

header("Content-type: application/json;charset:utf-8");
echo json_encode($retorno);
?>
