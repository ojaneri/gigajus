<?php
require 'config.php';
include 'header.php';

// Verificar se há filtro ou ordenação
$filtro = $_GET['filtro'] ?? '';
$ordem = $_GET['ordem'] ?? 'DESC';
$campo_ordem = $_GET['campo_ordem'] ?? 'data_abertura';

// Construir consulta SQL com filtro e ordenação
$sql = "SELECT p.*, c.nome AS nome_cliente 
        FROM processos p 
        JOIN clientes c ON p.id_cliente = c.id_cliente 
        WHERE p.numero_processo LIKE ? OR c.nome LIKE ? OR p.tribunal LIKE ? OR p.status LIKE ?
        ORDER BY $campo_ordem $ordem";
$stmt = $conn->prepare($sql);
$searchTerm = "%$filtro%";
$stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>
    <div class="content">
        <div class="filters">
            <div class="filter-group">
                <input type="text" name="filtro" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($filtro); ?>">
                <select name="campo_ordem">
                    <option value="data_abertura">Data de Abertura</option>
                    <option value="numero_processo">Número do Processo</option>
                    <option value="nome_cliente">Nome do Cliente</option>
                    <option value="tribunal">Tribunal</option>
                </select>
                <select name="ordem">
                    <option value="DESC">Decrescente</option>
                    <option value="ASC">Crescente</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
            <a href="add_process.php" class="btn-new-process">Novo Processo</a>
        </div>
        <div class="process-list">
            <table class="improved-table">
                <thead>
                <tr>
                    <th>Número do Processo</th>
                    <th>Nome do Cliente</th>
                    <th>Tribunal</th>
                    <th>Status</th>
                    <th>Data de Abertura</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['numero_processo']); ?></td>
                        <td><?php echo htmlspecialchars($row['nome_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($row['tribunal']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['data_abertura']); ?></td>
                        <td>
                            <a href="edit_process.php?id=<?php echo $row['id_processo']; ?>" class="btn btn-edit">Editar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
