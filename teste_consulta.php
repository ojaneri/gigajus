<?php
require 'config.php';

// Obter o ID do usuário logado (para teste, usaremos o ID 1)
$user_id = 1;

// Consulta para obter atividades recentes
$sqlAtividades = "
    (SELECT 'tarefa' as tipo, id_tarefa as id_item, descricao as titulo, data_hora_criacao as data, status
     FROM tarefas
     WHERE id_usuario = ?)
    UNION
    (SELECT 'processo' as tipo, id_processo as id_item, numero_processo as titulo, data_abertura as data, status
     FROM processos)
    ORDER BY data DESC
    LIMIT 5
";
$stmtAtividades = $conn->prepare($sqlAtividades);
$stmtAtividades->bind_param("i", $user_id);
$stmtAtividades->execute();
$resultAtividades = $stmtAtividades->get_result();
$atividades = $resultAtividades->fetch_all(MYSQLI_ASSOC);

// Exibir resultados
echo "<h1>Teste de Consulta SQL</h1>";
echo "<h2>Atividades Recentes</h2>";
echo "<pre>";
print_r($atividades);
echo "</pre>";

// Verificar estrutura das tabelas
echo "<h2>Estrutura da Tabela 'tarefas'</h2>";
$result = $conn->query("DESCRIBE tarefas");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

echo "<h2>Estrutura da Tabela 'processos'</h2>";
$result = $conn->query("DESCRIBE processos");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>