<?php
require 'config.php';
logMessage("[add_appointment2.php] Início do processamento do formulário de novo atendimento.");

// Verificar se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método de requisição inválido."]);
    exit();
}

// Verificar se todos os dados necessários foram enviados
$required_fields = ['id_cliente', 'data', 'descricao', 'responsavel'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        logMessage("[add_appointment2.php] Campo obrigatório '$field' não preenchido.");
        echo json_encode(["success" => false, "message" => "Campo obrigatório '$field' não foi preenchido."]);
        exit();
    }
}

// Coletar dados do formulário
$id_cliente = $_POST['id_cliente'];
$data = $_POST['data'];
$descricao = $_POST['descricao'];
$responsavel = $_POST['responsavel'];
$observacoes = $_POST['observacoes'] ?? '';

logMessage("[add_appointment2.php] Dados coletados: Cliente ID - $id_cliente, Data - $data, Descrição - $descricao, Responsável - $responsavel, Observações - $observacoes");

// Inserir dados no banco de dados
$stmt = $conn->prepare("INSERT INTO atendimentos (id_cliente, data, descricao, responsavel, observacoes) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $id_cliente, $data, $descricao, $responsavel, $observacoes);

if ($stmt->execute()) {
    logMessage("[add_appointment2.php] Atendimento adicionado com sucesso.");
    echo json_encode([
        "success" => true,
        "message" => "Atendimento adicionado com sucesso.",
        "clientId" => $id_cliente,
        "redirect" => "appointments.php?client_id=" . $id_cliente
    ]);
} else {
    logMessage("[add_appointment2.php] Erro ao adicionar atendimento. Erro: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "Erro ao adicionar atendimento."]);
}
$stmt->close();
$conn->close();
?>
