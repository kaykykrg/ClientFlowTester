<?php
include_once("db_conexao.php");
session_start();

$retorno = ["status" => "nok", "mensagem" => "Usuário não autenticado", "data" => null];

$usuario_id = $_SESSION['usuario_id'] ?? null;
$tipo       = $_SESSION['usuario_tipo'] ?? null;
$agencia_id = $_SESSION['agencia_id'] ?? null;

if (empty($usuario_id)) {
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

if ($tipo !== 'agency') {
    $retorno["mensagem"] = "Sem permissão para editar dados da empresa.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

if (empty($agencia_id)) {
    $retorno["mensagem"] = "Agência não encontrada.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

$nome_empresa = trim($_POST['nome_empresa'] ?? '');
$cnpj         = trim($_POST['cnpj'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');
$site         = trim($_POST['site'] ?? '');
$descricao    = trim($_POST['descricao'] ?? '');

function mensagem_duplicidade_empresa(mysqli_sql_exception $e)
{
    $mensagem = strtolower($e->getMessage());

    if (str_contains($mensagem, 'cnpj')) {
        return 'Este CNPJ já está cadastrado em outra conta.';
    }

    return 'Dados já cadastrados. Verifique as informações informadas.';
}

if (empty($nome_empresa)) {
    $retorno["mensagem"] = "O nome da empresa é obrigatório.";
    header("Content-type: application/json;charset:utf-8");
    echo json_encode($retorno);
    exit();
}

$stmt = $conexao->prepare(
    "UPDATE agencias SET nome_empresa = ?, cnpj = ?, telefone = ?, site = ?, descricao = ? WHERE id = ?"
);
$stmt->bind_param("sssssi", $nome_empresa, $cnpj, $telefone, $site, $descricao, $agencia_id);

try {
    if ($stmt->execute()) {
        $retorno["status"]   = "ok";
        $retorno["mensagem"] = "Dados da empresa atualizados com sucesso!";
    } else {
        $retorno["mensagem"] = ($stmt->errno == 1062)
            ? mensagem_duplicidade_empresa(new mysqli_sql_exception($stmt->error, $stmt->errno))
            : "Erro ao atualizar dados da empresa.";
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        $retorno["mensagem"] = mensagem_duplicidade_empresa($e);
    } else {
        $retorno["mensagem"] = "Erro ao atualizar dados da empresa.";
    }
}

$stmt->close();
$conexao->close();
header("Content-type: application/json;charset:utf-8");
echo json_encode($retorno);
?>
