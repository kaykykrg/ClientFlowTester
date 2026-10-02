<?php
/**
 * Helper central de notificações do ClientFlow.
 *
 * Funções exportadas:
 *   criar_notificacao($conexao, $usuario_id, $tipo, $titulo, $mensagem, $link = null)
 *   criar_notificacoes_agencia($conexao, $agencia_id, $checklist_id, $tipo, $titulo, $mensagem, $link = null)
 *
 * Cada chamada:
 *   1. Insere uma linha na tabela `notificacoes`
 *   2. Envia e-mail ao destinatário — com anti-spam de 5 min por (usuario, tipo)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

// ---------------------------------------------------------------------------
// Configurações de e-mail (mesmo padrão dos crons existentes)
// ---------------------------------------------------------------------------
define('CF_MAIL_HOST',     'smtp.gmail.com');
define('CF_MAIL_USER',     'clientflow.aviso@gmail.com');
define('CF_MAIL_PASS',     'nrpv mvby ebuq ifvh');
define('CF_MAIL_PORT',     587);
define('CF_MAIL_FROM',     'clientflow.aviso@gmail.com');
define('CF_MAIL_FROM_NAME','ClientFlow');

// Anti-spam: não envia e-mail se já foi enviado um do mesmo (usuario_id + tipo)
// nos últimos N minutos.
define('CF_MAIL_COOLDOWN_MIN', 5);

// ---------------------------------------------------------------------------
// Criar uma única notificação para um usuário
// ---------------------------------------------------------------------------
function criar_notificacao($conexao, $usuario_id, $tipo, $titulo, $mensagem, $link = null) {
    if (empty($usuario_id)) return false;

    // 1. Busca dados do destinatário (nome + e-mail)
    $stmt_u = $conexao->prepare(
        "SELECT nome, email FROM usuarios WHERE id = ? LIMIT 1"
    );
    $stmt_u->bind_param("i", $usuario_id);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    if ($res_u->num_rows === 0) {
        $stmt_u->close();
        return false;
    }
    $dest = $res_u->fetch_assoc();
    $stmt_u->close();

    // 2. Insere na tabela de notificações
    $stmt_n = $conexao->prepare(
        "INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt_n->bind_param("issss", $usuario_id, $tipo, $titulo, $mensagem, $link);
    $stmt_n->execute();
    $notif_id = $conexao->insert_id;
    $stmt_n->close();

    // 3. Verifica anti-spam: enviou e-mail para este usuário + tipo nos últimos X min?
    $cooldown_min = CF_MAIL_COOLDOWN_MIN;
    $stmt_spam = $conexao->prepare(
        "SELECT id FROM notificacoes
         WHERE usuario_id = ? AND tipo = ? AND email_enviado_em IS NOT NULL
           AND email_enviado_em >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
           AND id != ?
         LIMIT 1"
    );
    $stmt_spam->bind_param("isii", $usuario_id, $tipo, $cooldown_min, $notif_id);
    $stmt_spam->execute();
    $spam_res = $stmt_spam->get_result();
    $pode_enviar_email = ($spam_res->num_rows === 0);
    $stmt_spam->close();

    // 4. Envia e-mail se não estiver em cooldown
    if ($pode_enviar_email) {
        $enviado = _enviar_email_notificacao($dest['email'], $dest['nome'], $titulo, $mensagem, $link);
        if ($enviado) {
            $stmt_upd = $conexao->prepare(
                "UPDATE notificacoes SET email_enviado_em = NOW() WHERE id = ?"
            );
            $stmt_upd->bind_param("i", $notif_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        }
    }

    return $notif_id;
}

// ---------------------------------------------------------------------------
// Criar notificações para TODOS os membros da agência com acesso ao projeto
// ---------------------------------------------------------------------------
function criar_notificacoes_agencia($conexao, $agencia_id, $checklist_id, $tipo, $titulo, $mensagem, $link = null) {
    if (empty($agencia_id)) return;

    // Busca usuários da agência que têm perm_ver_projetos = 1
    // Inclui o dono da agência (tipo = 'agency') e membros com permissão
    if ($checklist_id) {
        // Apenas membros designados ao projeto OU que têm acesso geral (não é dev)
        $stmt = $conexao->prepare(
            "SELECT DISTINCT ua.usuario_id
             FROM usuarios_agencia ua
             WHERE ua.agencia_id = ?
               AND ua.ativo = 1
               AND ua.perm_ver_projetos = 1
               AND (
                   ua.papel != 'dev'
                   OR EXISTS (
                       SELECT 1 FROM projetos_membros pm
                       WHERE pm.usuario_agencia_id = ua.id
                         AND pm.checklist_id = ?
                   )
               )"
        );
        $stmt->bind_param("ii", $agencia_id, $checklist_id);
    } else {
        $stmt = $conexao->prepare(
            "SELECT usuario_id FROM usuarios_agencia
             WHERE agencia_id = ? AND ativo = 1 AND perm_ver_projetos = 1"
        );
        $stmt->bind_param("i", $agencia_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $membros = [];
    while ($row = $res->fetch_assoc()) {
        $membros[] = intval($row['usuario_id']);
    }
    $stmt->close();

    // Também inclui o dono da agência (tipo = 'agency') — ele pode não estar em usuarios_agencia
    $stmt_dono = $conexao->prepare(
        "SELECT u.id FROM usuarios u
         WHERE u.tipo = 'agency'
           AND EXISTS (
               SELECT 1 FROM usuarios_agencia ua
               WHERE ua.usuario_id = u.id AND ua.agencia_id = ?
               LIMIT 1
           )
         LIMIT 1"
    );
    $stmt_dono->bind_param("i", $agencia_id);
    $stmt_dono->execute();
    $res_dono = $stmt_dono->get_result();
    if ($res_dono->num_rows > 0) {
        $dono_id = intval($res_dono->fetch_assoc()['id']);
        if (!in_array($dono_id, $membros)) {
            $membros[] = $dono_id;
        }
    }
    $stmt_dono->close();

    foreach ($membros as $uid) {
        criar_notificacao($conexao, $uid, $tipo, $titulo, $mensagem, $link);
    }
}

// ---------------------------------------------------------------------------
// Envio de e-mail interno
// ---------------------------------------------------------------------------
function _enviar_email_notificacao($email, $nome, $titulo, $mensagem, $link = null) {
    $link_btn = $link ? "http://localhost/ClientFlow/" . ltrim($link, '/') : null;

    $btn_html = $link_btn ? "
        <div style='text-align:center; margin: 28px 0;'>
            <a href='{$link_btn}'
               style='display:inline-block; background: linear-gradient(135deg, #4f46e5, #7c3aed);
                      color:#fff; padding:13px 30px; border-radius:8px; text-decoration:none;
                      font-weight:700; font-size:15px; letter-spacing:0.3px;'>
                Ver detalhes →
            </a>
        </div>" : '';

    $nome_safe    = htmlspecialchars($nome, ENT_QUOTES);
    $titulo_safe  = htmlspecialchars($titulo, ENT_QUOTES);
    $msg_safe     = nl2br(htmlspecialchars($mensagem, ENT_QUOTES));

    $html = "
        <div style='font-family: Inter, Arial, sans-serif; color: #1f2937; max-width:620px;
                    margin:0 auto; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                        padding: 32px; text-align:center;'>
                <h1 style='margin:0; color:#fff; font-size:22px; font-weight:700;'>ClientFlow</h1>
                <p style='margin:6px 0 0; color:rgba(255,255,255,0.8); font-size:13px;'>
                    Plataforma de Gestão de Projetos
                </p>
            </div>

            <!-- Body -->
            <div style='padding: 36px 32px; background:#ffffff;'>
                <h2 style='margin:0 0 6px; font-size:18px; color:#111827;'>{$titulo_safe}</h2>
                <p style='color:#6b7280; margin:0 0 20px; font-size:14px;'>Olá, {$nome_safe}!</p>

                <div style='background:#f9fafb; border-left:4px solid #4f46e5; border-radius:6px;
                            padding:16px 20px; margin-bottom:24px;'>
                    <p style='margin:0; color:#374151; font-size:15px; line-height:1.6;'>{$msg_safe}</p>
                </div>

                {$btn_html}

                <p style='font-size:12px; color:#9ca3af; text-align:center; margin-top:24px;'>
                    Você está recebendo este e-mail porque tem uma conta ativa no ClientFlow.
                </p>
            </div>

            <!-- Footer -->
            <div style='background:#f9fafb; padding:18px 32px; text-align:center;
                        border-top:1px solid #e5e7eb;'>
                <p style='margin:0; font-size:12px; color:#9ca3af;'>
                    © ClientFlow — Não responda a este e-mail.
                </p>
            </div>
        </div>
    ";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = CF_MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = CF_MAIL_USER;
        $mail->Password   = CF_MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = CF_MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(CF_MAIL_FROM, CF_MAIL_FROM_NAME);
        $mail->addAddress($email, $nome);

        $mail->isHTML(true);
        $mail->Subject = $titulo;
        $mail->Body    = $html;
        $mail->AltBody = "{$titulo}\n\n{$mensagem}" . ($link_btn ? "\n\nLink: {$link_btn}" : '');

        $mail->send();
        return true;
    } catch (MailerException $e) {
        error_log("CF Notificação e-mail ERRO para {$email}: " . $e->getMessage());
        return false;
    }
}
?>
