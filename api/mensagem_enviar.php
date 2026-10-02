<?php
include_once("db_conexao.php");
include_once("notificacao_criar_helper.php");
session_start();

$retorno = [
    "status" => "nok",
    "mensagem" => "Não autorizado",
    "data" => null
];

$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_tipo = $_SESSION['usuario_tipo'] ?? null;
$usuario_email = $_SESSION['usuario_email'] ?? null;
$agencia_id = $_SESSION['agencia_id'] ?? null;
$ua_id = $_SESSION['ua_id'] ?? null;
$papel_agencia = $_SESSION['papel_agencia'] ?? null;
$permissoes = $_SESSION['permissoes'] ?? [];

if (empty($usuario_id)) {
    header("Content-type: application/json;charset=utf-8");
    echo json_encode($retorno);
    exit();
}

$checklist_id = $_POST['checklist_id'] ?? null;
$mensagem = trim($_POST['mensagem'] ?? '');

if (empty($checklist_id) || empty($mensagem)) {
    $retorno["mensagem"] = "checklist_id e mensagem são obrigatórios";
    header("Content-type: application/json;charset=utf-8");
    echo json_encode($retorno);
    exit();
}

$pode_acessar = false;

if ($usuario_tipo === 'client') {
    $stmt = $conexao->prepare("
        SELECT c.id FROM checklists ch
        JOIN clientes c ON ch.cliente_id = c.id
        WHERE ch.id = ? AND (c.usuario_id = ? OR c.email = ?)
    ");
    $stmt->bind_param("iis", $checklist_id, $usuario_id, $usuario_email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $pode_acessar = true;
    }
    $stmt->close();
} else if ($usuario_tipo === 'agency' || $usuario_tipo === 'agency_member' || $usuario_tipo === 'freelancer') {
    if (empty($permissoes['perm_ver_projetos'])) {
        $retorno["mensagem"] = "Você não tem permissão para enviar mensagens.";
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

    if ($usuario_tipo === 'agency_member' && $papel_agencia === 'dev') {
        $stmt = $conexao->prepare("
            SELECT ch.id
            FROM checklists ch
            INNER JOIN projetos_membros pm ON pm.checklist_id = ch.id
            WHERE ch.id = ? AND ch.agencia_id = ? AND pm.usuario_agencia_id = ?
        ");
        $stmt->bind_param("iii", $checklist_id, $agencia_id, $ua_id);
    } else {
        $stmt = $conexao->prepare("SELECT id FROM checklists WHERE id = ? AND agencia_id = ?");
        $stmt->bind_param("ii", $checklist_id, $agencia_id);
    }

    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $pode_acessar = true;
    }
    $stmt->close();
} else if ($usuario_tipo === 'admin') {
    $pode_acessar = true;
}

if (!$pode_acessar) {
    header("Content-type: application/json;charset=utf-8");
    echo json_encode($retorno);
    exit();
}

$stmt = $conexao->prepare("
    INSERT INTO mensagens_checklist (checklist_id, remetente_usuario_id, mensagem)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $checklist_id, $usuario_id, $mensagem);
if ($stmt->execute()) {
    $mensagem_id = $conexao->insert_id;

    // --- Notificação de chat ---
    // Busca dados do checklist para saber agencia_id e cliente
    $stmt_ch = $conexao->prepare(
        "SELECT ch.titulo, ch.agencia_id,
                u.id  AS usuario_cliente_id, u.nome AS cliente_nome
         FROM checklists ch
         LEFT JOIN clientes cl ON cl.id  = ch.cliente_id
         LEFT JOIN usuarios u  ON u.id   = cl.usuario_id
         WHERE ch.id = ? LIMIT 1"
    );
    $stmt_ch->bind_param("i", $checklist_id);
    $stmt_ch->execute();
    $ch_res = $stmt_ch->get_result();

    if ($ch_res->num_rows > 0) {
        $ch_info          = $ch_res->fetch_assoc();
        $titulo_ch        = $ch_info['titulo'];
        $agencia_id_chat  = intval($ch_info['agencia_id']);
        $uid_cliente      = $ch_info['usuario_cliente_id'] ? intval($ch_info['usuario_cliente_id']) : null;
        $link_chat        = "public/pages/checklist_details.html?id={$checklist_id}";

        if ($usuario_tipo === 'client') {
            // Cliente enviou mensagem → notifica agencia
            $stmt_remetente = $conexao->prepare("SELECT nome FROM usuarios WHERE id = ? LIMIT 1");
            $stmt_remetente->bind_param("i", $usuario_id);
            $stmt_remetente->execute();
            $rem = $stmt_remetente->get_result()->fetch_assoc();
            $stmt_remetente->close();
            $nome_rem = $rem ? $rem['nome'] : 'O cliente';

            criar_notificacoes_agencia(
                $conexao,
                $agencia_id_chat,
                $checklist_id,
                'nova_mensagem',
                "💬 Nova mensagem no chat",
                "{$nome_rem} enviou uma mensagem no projeto \"{$titulo_ch}\".",
                $link_chat
            );
        } else {
            // Agência enviou mensagem → notifica cliente
            if ($uid_cliente) {
                criar_notificacao(
                    $conexao,
                    $uid_cliente,
                    'nova_mensagem',
                    "💬 Nova mensagem no chat",
                    "Sua agência enviou uma mensagem no projeto \"{$titulo_ch}\".",
                    $link_chat
                );
            }
        }
    }
    $stmt_ch->close();
    // --- Fim notificação de chat ---

    $retorno["status"] = "ok";
    $retorno["mensagem"] = "Mensagem enviada com sucesso.";
    $retorno["data"] = [
        "id"                   => $mensagem_id,
        "checklist_id"         => $checklist_id,
        "remetente_usuario_id" => $usuario_id,
        "mensagem"             => $mensagem
    ];
} else {
    $retorno["mensagem"] = "Erro ao enviar mensagem.";
}
$stmt->close();

$conexao->close();
header("Content-type: application/json;charset=utf-8");
echo json_encode($retorno);
?>
