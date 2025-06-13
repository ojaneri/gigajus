<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
//if (!isset($_SESSION['user_id'])) {
//    header("Location: login.php");
//    exit();
//}
?>
<?php
// Get user's theme from session or default to 'law'
$userTheme = $_SESSION['theme'] ?? 'law';
?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="<?php echo htmlspecialchars($userTheme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Jurídico</title>
    <link rel="stylesheet" href="assets/css/unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
    <link rel="stylesheet" href="assets/css/tour.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
    <script src="assets/js/common.js" defer></script>
    <script src="assets/js/script.js" defer></script>
    <script src="assets/js/tour.js" defer></script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <a href="javascript:void(0);" id="menuToggle"><i class="fas fa-bars"></i></a>
            <img src="https://i.ibb.co/DgrxVHRC/Logotipo-Giga-Jus.png" alt="Logotipo" class="logo-img">
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="index.php" data-tooltip="Home" data-description="Página inicial do sistema"><i class="fas fa-home"></i> <span class="menu-text">Home</span></a></li>
                <li><a href="clients.php" data-tooltip="Clientes" data-description="Gerenciar cadastro de clientes"><i class="fas fa-user-friends"></i> <span class="menu-text">Clientes</span></a></li>
                <li><a href="processes.php" data-tooltip="Processos" data-description="Gerenciar processos judiciais"><i class="fas fa-gavel"></i> <span class="menu-text">Processos</span></a></li>
                <li><a href="appointments.php" data-tooltip="Atendimentos" data-description="Registrar e consultar atendimentos"><i class="fas fa-calendar-alt"></i> <span class="menu-text">Atendimentos</span></a></li>
                <li><a href="calendar.php" data-tooltip="Tarefas" data-description="Gerenciar tarefas e prazos"><i class="fas fa-tasks"></i> <span class="menu-text">Tarefas</span></a></li>
                <li><a href="pending.php" data-tooltip="Pendências" data-description="Visualizar itens pendentes"><i class="fas fa-exclamation-circle"></i> <span class="menu-text">Pendências</span></a></li>
                <li><a href="notifications.php" data-tooltip="Notificações" data-description="Acompanhar intimações e notificações"><i class="fas fa-bell"></i> <span class="menu-text">Notificações</span></a></li>
                <li><a href="billing.php" data-tooltip="Faturamento" data-description="Gerenciar cobranças e pagamentos"><i class="fas fa-file-invoice-dollar"></i> <span class="menu-text">Faturamento</span></a></li>
                <li><a href="feedback.php" data-tooltip="Feedback" data-description="Enviar e receber feedback"><i class="fas fa-comment-dots"></i> <span class="menu-text">Feedback</span></a></li>
                <li><a href="admin.php" data-tooltip="Administração" data-description="Configurações do sistema"><i class="fas fa-cogs"></i> <span class="menu-text">Administração</span></a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <ul>
                <li><a href="profile.php" data-tooltip="Meu Perfil" data-description="Editar informações pessoais"><i class="fas fa-user"></i> <span class="menu-text">Meu Perfil</span></a></li>
                <li><a href="logout.php" data-tooltip="Sair" data-description="Encerrar sessão no sistema"><i class="fas fa-sign-out-alt"></i> <span class="menu-text">Sair</span></a></li>
            </ul>
        </div>
    </div>
    <div class="main-content">
        <header>
            <!-- Logo removido e movido para a sidebar -->
            <div class="tour-button-container">
                <button id="tour-button" title="Iniciar tour guiado">
                    <i class="fas fa-question-circle"></i> Guia
                </button>
            </div>
        </header>
        <div class="content">
