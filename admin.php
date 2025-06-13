<?php
require_once 'config.php';
require_once 'header.php';
require_once 'includes/notifications_helper.php';

// Verificar se o usuário está logado e se é admin
if (!isset($_SESSION['user_id'])) {
    // Set alert message in session
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Você precisa estar logado para acessar esta página.'
    ];
    header('Location: index.php');
    exit();
}

// Verificar se o usuário é admin
$admin_query = "SELECT permissoes FROM usuarios WHERE id_usuario = ?";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// Verificar se o usuário tem permissões de admin
$is_admin = false;
if ($user_data && isset($user_data['permissoes'])) {
    $permissoes = json_decode($user_data['permissoes'], true);
    $is_admin = isset($permissoes['admin']) && $permissoes['admin'] === true;
}

if (!$is_admin) {
    // Set alert message in session
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Você não tem permissões de administrador para acessar esta página.'
    ];
    header('Location: index.php');
    exit();
}

// Verificar se a tabela empresas existe
$check_empresas_table = $conn->query("SHOW TABLES LIKE 'empresas'");
if ($check_empresas_table->num_rows == 0) {
    // Criar a tabela empresas
    $create_empresas_table = "CREATE TABLE empresas (
        id_empresa INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cnpj VARCHAR(20) UNIQUE,
        endereco TEXT,
        telefone VARCHAR(20),
        email VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ativo TINYINT(1) DEFAULT 1
    )";
    $conn->query($create_empresas_table);
    
    // Log da criação da tabela
    error_log("Tabela 'empresas' criada automaticamente");
}

// Verificar se a tabela usuario_empresa existe
$check_usuario_empresa_table = $conn->query("SHOW TABLES LIKE 'usuario_empresa'");
if ($check_usuario_empresa_table->num_rows == 0) {
    // Criar a tabela usuario_empresa
    $create_usuario_empresa_table = "CREATE TABLE usuario_empresa (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        id_empresa INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
        FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa),
        UNIQUE KEY unique_usuario_empresa (id_usuario, id_empresa)
    )";
    $conn->query($create_usuario_empresa_table);
    
    // Log da criação da tabela
    error_log("Tabela 'usuario_empresa' criada automaticamente");
}

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar novo usuário ou admin
    if (isset($_POST['add_user']) || isset($_POST['add_admin'])) {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $cargo = $_POST['cargo'];
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        
        // Definir permissões
        $is_admin_user = isset($_POST['add_admin']);
        $permissoes = json_encode(['admin' => $is_admin_user]);
        
        // Inserir no banco de dados
        $query = "INSERT INTO usuarios (nome, email, senha, cargo, permissoes) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $nome, $email, $senha, $cargo, $permissoes);
        
        if (mysqli_stmt_execute($stmt)) {
            $tipo_usuario = $is_admin_user ? "Administrador" : "Usuário";
            $mensagem = "$tipo_usuario $nome adicionado com sucesso.";
            // sendNotification("$tipo_usuario Adicionado", $mensagem, $email);
            
            // Redirecionar com mensagem de sucesso
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => $mensagem
            ];
            header('Location: admin.php');
            exit();
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => "Erro ao adicionar usuário: " . mysqli_error($conn)
            ];
        }
    }
    
    // Adicionar nova empresa
    if (isset($_POST['add_empresa'])) {
        $nome = $_POST['nome_empresa'];
        $cnpj = $_POST['cnpj'];
        $endereco = $_POST['endereco'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email_empresa'];
        
        // Validar CNPJ (opcionalmente, pode adicionar validação mais robusta)
        if (empty($cnpj)) {
            $cnpj = null; // Permite CNPJ nulo no banco de dados se a coluna permitir
        }

        // Inserir no banco de dados
        $query = "INSERT INTO empresas (nome, cnpj, endereco, telefone, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $nome, $cnpj, $endereco, $telefone, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => "Empresa $nome adicionada com sucesso."
            ];
            header('Location: admin.php');
            exit();
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => "Erro ao adicionar empresa: " . mysqli_error($conn)
            ];
        }
    }
    
    // Vincular usuário a empresa
    if (isset($_POST['link_user_empresa'])) {
        $id_usuario = $_POST['id_usuario'];
        $id_empresa = $_POST['id_empresa'];
        
        // Verificar se o vínculo já existe
        $check_query = "SELECT * FROM usuario_empresa WHERE id_usuario = ? AND id_empresa = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $id_usuario, $id_empresa);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if ($check_result->num_rows > 0) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'message' => "Este usuário já está vinculado a esta empresa."
            ];
        } else {
            // Inserir no banco de dados
            $query = "INSERT INTO usuario_empresa (id_usuario, id_empresa) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_empresa);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => "Usuário vinculado à empresa com sucesso."
                ];
                header('Location: admin.php');
                exit();
            } else {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'message' => "Erro ao vincular usuário à empresa: " . mysqli_error($conn)
                ];
            }
        }
    }

    // Adicionar nova OAB
    if (isset($_POST['add_oab'])) {
        $nome_advogado = $_POST['nome_advogado'];
        $oab_numero = $_POST['oab_numero'];
        $oab_uf = $_POST['oab_uf'];
        $id_empresa = $_POST['id_empresa'];

        // Inserir no banco de dados
        $query = "INSERT INTO advogados (nome_advogado, oab_numero, oab_uf, id_empresa) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $nome_advogado, $oab_numero, $oab_uf, $id_empresa);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => "OAB $oab_numero-$oab_uf adicionada com sucesso."
            ];
            header('Location: admin.php');
            exit();
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => "Erro ao adicionar OAB: " . mysqli_error($conn)
            ];
        }
    }
    
    // Ativar/Desativar usuário
    if (isset($_POST['deactivate_user'])) {
        $user_id = $_POST['user_id'];
        $query = "UPDATE usuarios SET ativo = 0 WHERE id_usuario = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "Usuário desativado com sucesso."
        ];
        header('Location: admin.php');
        exit();
    }

    if (isset($_POST['activate_user'])) {
        $user_id = $_POST['user_id'];
        $query = "UPDATE usuarios SET ativo = 1 WHERE id_usuario = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "Usuário ativado com sucesso."
        ];
        header('Location: admin.php');
        exit();
    }
}

// Buscar usuários
$query = "SELECT id_usuario, nome, email, cargo, permissoes, ativo FROM usuarios";
$result = mysqli_query($conn, $query);
$usuarios = [];
while ($row = mysqli_fetch_assoc($result)) {
    $permissoes = json_decode($row['permissoes'], true);
    $row['is_admin'] = isset($permissoes['admin']) && $permissoes['admin'] === true;
    $usuarios[] = $row;
}

// Buscar empresas
$query = "SELECT * FROM empresas";
$result = mysqli_query($conn, $query);
$empresas = [];
while ($row = mysqli_fetch_assoc($result)) {
    $empresas[] = $row;
}

// Buscar vínculos usuário-empresa
$query = "SELECT ue.*, u.nome as nome_usuario, e.nome as nome_empresa 
          FROM usuario_empresa ue 
          JOIN usuarios u ON ue.id_usuario = u.id_usuario 
          JOIN empresas e ON ue.id_empresa = e.id_empresa";
$result = mysqli_query($conn, $query);
$vinculos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $vinculos[] = $row;
}

// Buscar OABs
$query = "SELECT a.*, e.nome as nome_empresa FROM advogados a JOIN empresas e ON a.id_empresa = e.id_empresa";
$result = mysqli_query($conn, $query);
$oabs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $oabs[] = $row;
}
?>
    <style>
        .admin-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .admin-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .admin-tab.active {
            border-color: #ddd;
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        
        .admin-tab-content {
            display: none;
        }
        
        .admin-tab-content.active {
            display: block;
        }
        
        .admin-section {
            margin-bottom: 30px;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .admin-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-grid-full {
            grid-column: 1 / -1;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
    <div class="content">
        <h1>Painel Administrativo</h1>
        
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?>">
                <?php echo $_SESSION['alert']['message']; ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>
        
        <div class="admin-tabs">
            <div class="admin-tab active" data-tab="usuarios">Usuários</div>
            <div class="admin-tab" data-tab="empresas">Empresas</div>
            <div class="admin-tab" data-tab="vinculos">Vínculos</div>
            <div class="admin-tab" data-tab="oabs">OABs</div>
            <div class="admin-tab" data-tab="manutencao">Manutenção</div>
        </div>
        
        <!-- Tab OABs -->
        <div class="admin-tab-content" id="tab-oabs">
            <div class="admin-section">
                <h2>Adicionar OAB</h2>
                <form action="" method="post">
                    <div class="form-grid">
                        <div>
                            <label for="nome_advogado">Nome</label>
                            <input type="text" id="nome_advogado" name="nome_advogado" required>
                        </div>
                        <div>
                            <label for="oab_numero">Número OAB</label>
                            <input type="text" id="oab_numero" name="oab_numero" required>
                        </div>
                        <div>
                            <label for="oab_uf">UF</label>
                            <select id="oab_uf" name="oab_uf" required>
                                <option value="">Selecione</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</RJ></option>
                                <option value="RN">RN</RN></option>
                                <option value="RS">RS</RS></option>
                                <option value="RO">RO</RO></option>
                                <option value="RR">RR</RR></option>
                                <option value="SC">SC</SC></option>
                                <option value="SP">SP</SP></option>
                                <option value="SE">SE</SE></option>
                                <option value="TO">TO</TO></option>
                            </select>
                        </div>
                        <div>
                            <label for="id_empresa_oab">Empresa</label>
                            <select id="id_empresa_oab" name="id_empresa" required>
                                <option value="">Selecione</option>
                                <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo htmlspecialchars($empresa['id_empresa']); ?>">
                                    <?php echo htmlspecialchars($empresa['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_oab" class="btn btn-primary">Adicionar OAB</button>
                </form>
            </div>
            <div class="admin-section">
                <h2>OABs Cadastradas</h2>
                <table class="improved-table">
                    <thead>
                        <tr>
                            <th>Número OAB</th>
                            <th>UF</th>
                            <th>Empresa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($oabs as $oab): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($oab['oab_numero']); ?></td>
                            <td><?php echo htmlspecialchars($oab['oab_uf']); ?></td>
                            <td><?php echo htmlspecialchars($oab['nome_empresa']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Usuários -->
        <div class="admin-tab-content active" id="tab-usuarios">
            <div class="admin-section">
                <h2>Adicionar Administrador</h2>
                <form action="" method="post">
                    <div class="form-grid">
                        <div>
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div>
                            <label for="cargo">Cargo</label>
                            <input type="text" id="cargo" name="cargo" required>
                        </div>
                        <div>
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha" required>
                        </div>
                    </div>
                    <button type="submit" name="add_admin" class="btn btn-primary">Adicionar Admin</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Adicionar Usuário</h2>
                <form action="" method="post">
                    <div class="form-grid">
                        <div>
                            <label for="nome_user">Nome</label>
                            <input type="text" id="nome_user" name="nome" required>
                        </div>
                        <div>
                            <label for="email_user">Email</label>
                            <input type="email" id="email_user" name="email" required>
                        </div>
                        <div>
                            <label for="cargo_user">Cargo</label>
                            <input type="text" id="cargo_user" name="cargo" required>
                        </div>
                        <div>
                            <label for="senha_user">Senha</label>
                            <input type="password" id="senha_user" name="senha" required>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Adicionar Usuário</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Usuários do Sistema</h2>
                <table class="striped-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Cargo</th>
                            <th>Função</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cargo']); ?></td>
                            <td><?php echo $usuario['is_admin'] ? 'Admin' : 'Usuário'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $usuario['ativo'] ? 'ativo' : 'inativo'; ?>">
                                    <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usuario['ativo']): ?>
                                <form method="POST" action="admin.php">
                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                                    <button type="submit" name="deactivate_user" class="btn btn-danger btn-sm">
                                        <i class="fas fa-user-slash"></i> Desativar
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" action="admin.php">
                                    <input type="hidden" name="user_id" value="<?php echo $usuario['id_usuario']; ?>">
                                    <button type="submit" name="activate_user" class="btn btn-success btn-sm">
                                        <i class="fas fa-user-check"></i> Ativar
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Empresas -->
        <div class="admin-tab-content" id="tab-empresas">
            <div class="admin-section">
                <h2>Adicionar Empresa</h2>
                <form action="" method="post">
                    <div class="form-grid">
                        <div>
                            <label for="nome_empresa">Nome da Empresa</label>
                            <input type="text" id="nome_empresa" name="nome_empresa" required>
                        </div>
                        <div>
                            <label for="cnpj">CNPJ</label>
                            <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00">
                        </div>
                        <div>
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone">
                        </div>
                        <div>
                            <label for="email_empresa">Email</label>
                            <input type="email" id="email_empresa" name="email_empresa">
                        </div>
                        <div class="form-grid-full">
                            <label for="endereco">Endereço</label>
                            <textarea id="endereco" name="endereco" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_empresa" class="btn btn-primary">Adicionar Empresa</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Empresas Cadastradas</h2>
                <table class="striped-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($empresa['nome']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['cnpj']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['email']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $empresa['ativo'] ? 'ativo' : 'inativo'; ?>">
                                    <?php echo $empresa['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Vínculos -->
        <div class="admin-tab-content" id="tab-vinculos">
            <div class="admin-section">
                <h2>Vincular Usuário a Empresa</h2>
                <form action="" method="post">
                    <div class="form-grid">
                        <div>
                            <label for="id_usuario">Usuário</label>
                            <select id="id_usuario" name="id_usuario" required>
                                <option value="">Selecione um usuário</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <?php if ($usuario['ativo']): ?>
                                    <option value="<?php echo $usuario['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($usuario['nome']); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="id_empresa">Empresa</label>
                            <select id="id_empresa" name="id_empresa" required>
                                <option value="">Selecione uma empresa</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <?php if ($empresa['ativo']): ?>
                                    <option value="<?php echo $empresa['id_empresa']; ?>">
                                        <?php echo htmlspecialchars($empresa['nome']); ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="link_user_empresa" class="btn btn-primary">Vincular</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Vínculos Existentes</h2>
                <table class="improved-table">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Empresa</th>
                            <th>Data de Vinculação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vinculos as $vinculo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vinculo['nome_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($vinculo['nome_empresa']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($vinculo['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="admin.php">
                                    <input type="hidden" name="vinculo_id" value="<?php echo $vinculo['id']; ?>">
                                    <button type="submit" name="remove_vinculo" class="btn btn-danger btn-sm">
                                        <i class="fas fa-unlink"></i> Remover
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab Manutenção -->
        <div class="admin-tab-content" id="tab-manutencao">
            <div class="admin-section">
                <h2>Manutenção do Banco de Dados</h2>
                <div class="maintenance-actions">
                    <a href="includes/setup_database.php" class="btn btn-primary">Verificar e Atualizar Estrutura do Banco</a>
                    <p class="maintenance-description">Esta ação verificará e criará tabelas ausentes no banco de dados.</p>
                </div>
                
                <div class="maintenance-actions" style="margin-top: 20px;">
                    <a href="rename_client_folders.php" class="btn btn-warning">Renomear Pastas de Clientes</a>
                    <p class="maintenance-description">Esta ação renomeará as pastas de clientes para o formato "Nome do cliente (ID X)".</p>
                </div>
            </div>

            <div class="admin-section">
                <h2>Documentação</h2>
                <div class="maintenance-actions">
                    <a href="docs/CHANGELOG.md" class="btn btn-outline" target="_blank">Ver Documentação do Sistema</a>
                    <p class="maintenance-description">Visualize a documentação do sistema, changelog e estrutura do banco de dados.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tabs functionality
            const tabs = document.querySelectorAll('.admin-tab');
            const tabContents = document.querySelectorAll('.admin-tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = 'tab-' + this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s';
                        setTimeout(() => alert.style.display = 'none', 500);
                    });
                }, 5000);
            }
        });
    </script>