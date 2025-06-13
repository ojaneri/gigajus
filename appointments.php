<?php
require 'config.php';
include 'header.php';

// Verificar se há filtro ou ordenação
$filtro = $_GET['filtro'] ?? '';
$ordem = $_GET['ordem'] ?? 'DESC';
$campo_ordem = $_GET['campo_ordem'] ?? 'data';

// Construir consulta SQL com filtro e ordenação
$sql = "SELECT a.*, c.nome AS nome_cliente 
        FROM atendimentos a 
        JOIN clientes c ON a.id_cliente = c.id_cliente 
        WHERE c.nome LIKE ? OR a.descricao LIKE ?
        ORDER BY $campo_ordem $ordem";
$stmt = $conn->prepare($sql);
$searchTerm = "%$filtro%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>
    <div class="content">
        <div class="filters">
            <div class="filter-group">
                <input type="text" name="filtro" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($filtro); ?>">
                <select name="campo_ordem">
                    <option value="data">Data</option>
                    <option value="nome_cliente">Nome do Cliente</option>
                    <option value="descricao">Descrição</option>
                </select>
                <select name="ordem">
                    <option value="DESC">Decrescente</option>
                    <option value="ASC">Crescente</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
            <a href="add_appointment.php" class="btn-new-appointment">Novo Atendimento</a>
        </div>
        <div class="appointment-list">
            <table class="improved-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Nome do Cliente</th>
                    <th>Descrição</th>
                    <th>Responsável</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['data']); ?></td>
                        <td><?php echo htmlspecialchars($row['nome_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($row['responsavel']); ?></td>
                        <td>
                            <a href="edit_appointment.php?id=<?php echo $row['id_atendimento']; ?>" class="btn btn-edit">Editar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<script>
    // Script para relistar atendimentos após adicionar ou editar
    function reloadAppointments() {
        location.reload();
    }
</script>
