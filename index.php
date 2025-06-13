<?php
session_start();
require 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obter o ID do usuário logado
$user_id = $_SESSION['user_id'];

// Obter o nome do usuário logado
$sqlUsuario = "SELECT nome FROM usuarios WHERE id_usuario = ?";
$stmtUsuario = $conn->prepare($sqlUsuario);
$stmtUsuario->bind_param("i", $user_id);
$stmtUsuario->execute();
$resultUsuario = $stmtUsuario->get_result();
$usuario = $resultUsuario->fetch_assoc();
$nomeUsuario = $usuario['nome'] ?? 'Usuário';

// Consultas para obter as estatísticas do sistema
$sqlClientes = "SELECT COUNT(*) as total FROM clientes";
$resultClientes = $conn->query($sqlClientes);
$totalClientes = $resultClientes->fetch_assoc()['total'];

$sqlProcessos = "SELECT COUNT(*) as total FROM processos";
$resultProcessos = $conn->query($sqlProcessos);
$totalProcessos = $resultProcessos->fetch_assoc()['total'];

// Consulta para obter tarefas pendentes
$sqlTarefasPendentes = "SELECT COUNT(*) as total FROM tarefas WHERE id_usuario = ? AND status = 'Pendente'";
$stmtTarefasPendentes = $conn->prepare($sqlTarefasPendentes);
$stmtTarefasPendentes->bind_param("i", $user_id);
$stmtTarefasPendentes->execute();
$resultTarefasPendentes = $stmtTarefasPendentes->get_result();
$totalTarefasPendentes = $resultTarefasPendentes->fetch_assoc()['total'];

// Consulta para obter atendimentos futuros
$sqlAtendimentos = "SELECT COUNT(*) as total FROM atendimentos WHERE responsavel = ? AND data > NOW()";
$stmtAtendimentos = $conn->prepare($sqlAtendimentos);
$stmtAtendimentos->bind_param("s", $_SESSION['username']);
$stmtAtendimentos->execute();
$resultAtendimentos = $stmtAtendimentos->get_result();
$totalAtendimentos = $resultAtendimentos->fetch_assoc()['total'];

// Consulta para obter as tarefas do usuário
$sqlTarefas = "SELECT * FROM tarefas WHERE id_usuario = ? ORDER BY data_horario_final ASC LIMIT 5";
$stmtTarefas = $conn->prepare($sqlTarefas);
$stmtTarefas->bind_param("i", $user_id);
$stmtTarefas->execute();
$resultTarefas = $stmtTarefas->get_result();
$tarefas = $resultTarefas->fetch_all(MYSQLI_ASSOC);

// Consulta para obter atividades recentes
$sqlAtividades = "
    (SELECT 'tarefa' as tipo, id_tarefa as id_item, descricao as titulo, data_hora_criacao as data, status
     FROM tarefas
     WHERE id_usuario = ?)
    UNION
    (SELECT 'processo' as tipo, id_processo as id_item, numero_processo as titulo, CONCAT(data_abertura, ' 00:00:00') as data, status
     FROM processos)
    ORDER BY data DESC
    LIMIT 10
";
$stmtAtividades = $conn->prepare($sqlAtividades);
$stmtAtividades->bind_param("i", $user_id);
$stmtAtividades->execute();
$resultAtividades = $stmtAtividades->get_result();
$atividades = $resultAtividades->fetch_all(MYSQLI_ASSOC);

// Função para formatar data
function formatarData($data) {
    $timestamp = strtotime($data);
    return date('d/m/Y H:i', $timestamp);
}

// Função para obter classe de status
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'concluído':
        case 'concluido':
            return 'status-completed';
        case 'em andamento':
            return 'status-in-progress';
        case 'pendente':
            return 'status-pending';
        case 'atrasado':
            return 'status-late';
        default:
            return 'status-default';
    }
}

// Função para obter ícone de tipo
function getTipoIcon($tipo) {
    switch(strtolower($tipo)) {
        case 'tarefa':
            return 'fa-tasks';
        case 'processo':
            return 'fa-gavel';
        case 'compromisso':
            return 'fa-calendar-alt';
        default:
            return 'fa-file';
    }
}
?>

<?php include 'header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-welcome">
        <h1>Bem-vindo ao <img src="https://i.ibb.co/DgrxVHRC/Logotipo-Giga-Jus.png" alt="Logotipo" style="max-height: 30px; vertical-align: middle;"></h1>
        <p class="dashboard-date"><?php
            $formatter = new IntlDateFormatter(
                'pt_BR',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                'America/Sao_Paulo',
                IntlDateFormatter::GREGORIAN,
                "EEEE, d 'de' MMMM 'de' Y"
            );
            echo mb_convert_case($formatter->format(new DateTime()), MB_CASE_TITLE, "UTF-8");
        ?></p>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-info">
                <h3>Clientes</h3>
                <p class="stat-number"><?php echo $totalClientes; ?></p>
                <a href="clients.php" class="stat-link">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-gavel"></i>
            </div>
            <div class="stat-info">
                <h3>Processos</h3>
                <p class="stat-number"><?php echo $totalProcessos; ?></p>
                <a href="processes.php" class="stat-link">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <h3>Tarefas Pendentes</h3>
                <p class="stat-number"><?php echo $totalTarefasPendentes; ?></p>
                <a href="calendar.php" class="stat-link">Ver todas <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-info">
                <h3>Atendimentos</h3>
                <p class="stat-number"><?php echo $totalAtendimentos; ?></p>
                <a href="appointments.php" class="stat-link">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="dashboard-column">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-tasks"></i> Próximas Tarefas</h2>
                    <a href="calendar.php" class="view-all">Ver todas</a>
                </div>
                <div class="card-content">
                    <?php if (count($tarefas) > 0): ?>
                        <ul class="task-list">
                            <?php foreach ($tarefas as $tarefa): ?>
                                <li class="task-item">
                                    <div class="task-info">
                                        <h3><?php echo $tarefa['descricao']; ?></h3>
                                        <p class="task-date"><i class="far fa-clock"></i> <?php echo formatarData($tarefa['data_horario_final']); ?></p>
                                    </div>
                                    <div class="task-status">
                                        <span class="status-badge <?php echo getStatusClass($tarefa['status']); ?>">
                                            <?php echo $tarefa['status']; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>Não há tarefas pendentes.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Distribuição de Processos</h2>
                </div>
                <div class="card-content">
                    <div class="chart-container">
                        <canvas id="processosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-column">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Atividades Recentes</h2>
                </div>
                <div class="card-content">
                    <?php if (count($atividades) > 0): ?>
                        <ul class="activity-list">
                            <?php foreach ($atividades as $atividade): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas <?php echo getTipoIcon($atividade['tipo']); ?>"></i>
                                    </div>
                                    <div class="activity-info">
                                        <h3><?php echo $atividade['titulo']; ?></h3>
                                        <p class="activity-meta">
                                            <span class="activity-type"><?php echo ucfirst($atividade['tipo']); ?></span>
                                            <span class="activity-date"><?php echo formatarData($atividade['data']); ?></span>
                                        </p>
                                    </div>
                                    <div class="activity-status">
                                        <span class="status-badge <?php echo getStatusClass($atividade['status']); ?>">
                                            <?php echo $atividade['status']; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>Nenhuma atividade recente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-week"></i> Agenda da Semana</h2>
                    <a href="calendar.php" class="view-all">Ver calendário</a>
                </div>
                <div class="card-content">
                    <div id="mini-calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de distribuição de processos
    const ctxProcessos = document.getElementById('processosChart').getContext('2d');
    const processosChart = new Chart(ctxProcessos, {
        type: 'doughnut',
        data: {
            labels: ['Cível', 'Criminal', 'Trabalhista', 'Tributário', 'Outros'],
            datasets: [{
                data: [30, 15, 25, 20, 10],
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
    
    // Mini calendário
    const hoje = new Date();
    const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    const miniCalendar = document.getElementById('mini-calendar');
    
    // Criar cabeçalho dos dias da semana
    let calendarHTML = '<div class="mini-calendar-header">';
    diasSemana.forEach(dia => {
        calendarHTML += `<div class="mini-calendar-day-name">${dia}</div>`;
    });
    calendarHTML += '</div><div class="mini-calendar-grid">';
    
    // Determinar o primeiro dia do mês
    const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    const ultimoDia = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
    
    // Adicionar dias vazios até o primeiro dia do mês
    for (let i = 0; i < primeiroDia.getDay(); i++) {
        calendarHTML += '<div class="mini-calendar-day empty"></div>';
    }
    
    // Adicionar todos os dias do mês
    for (let i = 1; i <= ultimoDia.getDate(); i++) {
        const isToday = i === hoje.getDate();
        const hasEvent = [3, 7, 12, 18, 25].includes(i); // Dias de exemplo com eventos
        
        calendarHTML += `<div class="mini-calendar-day ${isToday ? 'today' : ''} ${hasEvent ? 'has-event' : ''}">
            ${i}
            ${hasEvent ? '<span class="event-indicator"></span>' : ''}
        </div>`;
    }
    
    calendarHTML += '</div>';
    miniCalendar.innerHTML = calendarHTML;
    
    // Mostrar notificação de boas-vindas
    setTimeout(() => {
        showNotification('Bem-vindo ao GigaJus, <?php echo $nomeUsuario; ?>!', 'success');
        
        // Verificar se é a primeira visita do usuário
        if (!localStorage.getItem('tourShown')) {
            // Marcar que o tour foi mostrado
            localStorage.setItem('tourShown', 'true');
            
            // Iniciar o tour após 2 segundos (após a notificação de boas-vindas)
            setTimeout(() => {
                startTour();
            }, 2000);
        }
    }, 1000);
});
</script>

<!-- Script do dashboard -->
<script src="assets/js/dashboard.js"></script>

</div> <!-- .content -->
</div> <!-- .main-content -->
</body>
</html>
