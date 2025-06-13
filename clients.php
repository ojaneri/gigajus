<?php
session_start();
require 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';

// Verificar se o usuário é admin
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Variáveis de busca e exibição
$search = '';
$show_inactive = false;
$active_clients = [];
$inactive_clients = [];

// Verificar se a busca e a opção de mostrar inativos foram definidas
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search = $_GET['search'] ?? '';
    $show_inactive = isset($_GET['show_inactive']);
    
    $searchTerm = "%$search%";

    // Buscar clientes ativos
    $sql_active = "SELECT * FROM clientes WHERE (nome LIKE ? OR cpf_cnpj LIKE ? OR email LIKE ?) AND ativo = 1";
    $stmt_active = $conn->prepare($sql_active);
    $stmt_active->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt_active->execute();
    $result_active = $stmt_active->get_result();
    $active_clients = $result_active->fetch_all(MYSQLI_ASSOC);
    
    // DEBUG: Mostrar inativos sempre para admin (independente do checkbox)
    if ($is_admin) {
        $sql_inactive = "SELECT * FROM clientes WHERE (nome LIKE ? OR cpf_cnpj LIKE ? OR email LIKE ?) AND ativo = 0";
        $stmt_inactive = $conn->prepare($sql_inactive);
        $stmt_inactive->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt_inactive->execute();
        $result_inactive = $stmt_inactive->get_result();
        $inactive_clients = $result_inactive->fetch_all(MYSQLI_ASSOC);
    }
} else {
    // Buscar clientes ativos sem filtro
    $sql_active = "SELECT * FROM clientes WHERE ativo = 1";
    $stmt_active = $conn->prepare($sql_active);
    $stmt_active->execute();
    $result_active = $stmt_active->get_result();
    $active_clients = $result_active->fetch_all(MYSQLI_ASSOC);
    
    // DEBUG: Mostrar inativos sempre para admin (independente do checkbox)
    if ($is_admin) {
        $sql_inactive = "SELECT * FROM clientes WHERE ativo = 0";
        $stmt_inactive = $conn->prepare($sql_inactive);
        $stmt_inactive->execute();
        $result_inactive = $stmt_inactive->get_result();
        $inactive_clients = $result_inactive->fetch_all(MYSQLI_ASSOC);
    }
}
?>


    <div class="content">
        <form method="GET" action="clients.php">
            <div class="filters">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Pesquisar cliente..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="campo_ordem">
                        <option value="data_cadastro">Data de Cadastro</option>
                        <option value="nome">Nome do Cliente</option>
                        <option value="cpf_cnpj">CPF/CNPJ</option>
                        <option value="email">Email</option>
                    </select>
                    <select name="ordem">
                        <option value="DESC">Decrescente</option>
                        <option value="ASC">Crescente</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <?php if ($is_admin): ?>
                        <label for="show_inactive" class="checkbox-label">
                            <input type="checkbox" name="show_inactive" id="show_inactive" <?php echo $show_inactive ? 'checked' : ''; ?>>
                            Mostrar inativos
                        </label>
                        <!-- DEBUG: Mostrar status admin e show_inactive -->
                        <span style="color: #c00; font-weight: bold; margin-left: 20px;">
                            [DEBUG] $is_admin=<?php var_export($is_admin); ?> | $show_inactive=<?php var_export($show_inactive); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <a href="create_client.php" class="btn-new-process">Novo Cliente</a>
            </div>
        </form>
        
        <!-- Seção de Clientes Ativos -->
        <div class="section-header">
            <h2><i class="fas fa-user-check"></i> Clientes Ativos</h2>
        </div>
        
        <div class="process-list">
            <table class="improved-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF/CNPJ</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Cadastro</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($active_clients)): ?>
                    <tr>
                        <td colspan="7" class="empty-table">Nenhum cliente ativo encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($active_clients as $client) : ?>
                        <tr>
                            <td><?= htmlspecialchars($client['nome']) ?></td>
                            <td><?= htmlspecialchars($client['cpf_cnpj']) ?></td>
                            <td><?= htmlspecialchars($client['email']) ?></td>
                            <td><?= htmlspecialchars($client['telefone']) ?></td>
                            <td><?= isset($client['data_cadastro']) ? htmlspecialchars(date('d/m/Y', strtotime($client['data_cadastro']))) : 'N/A' ?></td>
                            <td>
                                <span class="status-badge status-ativo">
                                    Ativo
                                </span>
                            </td>
                            <td class="actions-column">
                                <a href="edit_client.php?id=<?php echo $client['id_cliente']; ?>" class="btn-icon btn-edit" title="Editar Cliente">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="arquivos_client.php?id=<?php echo $client['id_cliente']; ?>" class="btn-icon btn-files" title="Arquivos do Cliente">
                                    <i class="fas fa-folder-open"></i>
                                </a>
                                <a href="client_details.php?id=<?php echo $client['id_cliente']; ?>" class="btn-icon btn-details" title="Detalhes do Cliente">
                                    <i class="fas fa-info-circle"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Seção de Clientes Inativos (apenas para admin) -->
        <?php if ($is_admin): ?>
            <div class="section-header inactive-section">
                <h2><i class="fas fa-user-slash"></i> Clientes Inativos</h2>
            </div>
            <?php if ($show_inactive): ?>
                <div class="process-list">
                    <table class="improved-table inactive-table">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF/CNPJ</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($inactive_clients)): ?>
                            <tr>
                                <td colspan="7" class="empty-table">Nenhum cliente inativo encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inactive_clients as $client) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['nome']) ?></td>
                                    <td><?= htmlspecialchars($client['cpf_cnpj']) ?></td>
                                    <td><?= htmlspecialchars($client['email']) ?></td>
                                    <td><?= htmlspecialchars($client['telefone']) ?></td>
                                    <td><?= isset($client['data_cadastro']) ? htmlspecialchars(date('d/m/Y', strtotime($client['data_cadastro']))) : 'N/A' ?></td>
                                    <td>
                                        <span class="status-badge status-inativo">
                                            Inativo
                                        </span>
                                    </td>
                                    <td class="actions-column">
                                        <a href="edit_client.php?id=<?php echo $client['id_cliente']; ?>" class="btn-icon btn-edit" title="Editar Cliente">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="arquivos_client.php?id=<?php echo $client['id_cliente']; ?>" class="btn-icon btn-files" title="Arquivos do Cliente">
                                            <i class="fas fa-folder-open"></i>
                                        </a>
                                        <a href="client_details.php?id=<?php echo $client['id_cliente']; ?>" class="btn-icon btn-details" title="Detalhes do Cliente">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="margin: 20px 0; color: #666; font-style: italic;">
                    Marque "Mostrar inativos" e clique em Filtrar para visualizar clientes inativos.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<style>
/* Estilos para as seções de clientes */
.section-header {
    background-color: var(--bg-secondary);
    padding: 10px 15px;
    margin: 20px 0 10px 0;
    border-radius: 5px;
    border-left: 4px solid var(--btn-primary-bg);
}

.section-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
}

.section-header h2 i {
    margin-right: 10px;
}

.inactive-section {
    border-left-color: var(--status-late-text);
}

.inactive-table thead {
    background-color: var(--status-late-bg);
}

.empty-table {
    text-align: center;
    padding: 20px;
    color: var(--text-secondary);
    font-style: italic;
}

/* Estilos para o formulário de filtro */
.filters {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}
</style>
