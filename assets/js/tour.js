/**
 * tour.js
 * Script para o tour guiado do sistema
 * Utiliza a biblioteca Intro.js para criar um tour passo a passo
 */

// Função para iniciar o tour
function startTour() {
    // Configurar os passos do tour
    const steps = [
        {
            element: document.querySelector('.sidebar-logo'),
            intro: 'Bem-vindo ao GigaJus! Este é um tour guiado pelo sistema.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="index.php"]'),
            intro: 'A página inicial mostra um resumo das suas atividades e estatísticas do sistema.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="clients.php"]'),
            intro: 'Gerencie seus clientes: cadastre, edite e visualize informações detalhadas.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="processes.php"]'),
            intro: 'Acompanhe todos os processos judiciais dos seus clientes.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="appointments.php"]'),
            intro: 'Registre e consulte atendimentos realizados para seus clientes.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="calendar.php"]'),
            intro: 'Gerencie suas tarefas e prazos em um calendário integrado.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="pending.php"]'),
            intro: 'Visualize itens pendentes que requerem sua atenção.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="notifications.php"]'),
            intro: 'Acompanhe intimações e notificações judiciais automaticamente.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="billing.php"]'),
            intro: 'Gerencie cobranças e pagamentos dos seus clientes.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="feedback.php"]'),
            intro: 'Envie e receba feedback sobre o sistema.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-menu a[href="admin.php"]'),
            intro: 'Configure o sistema de acordo com suas necessidades (apenas para administradores).',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-footer a[href="profile.php"]'),
            intro: 'Acesse e edite suas informações pessoais e preferências.',
            position: 'right'
        },
        {
            element: document.querySelector('.sidebar-footer a[href="logout.php"]'),
            intro: 'Encerre sua sessão no sistema quando terminar.',
            position: 'right'
        },
        {
            element: document.querySelector('#tour-button'),
            intro: 'Você pode iniciar este tour novamente a qualquer momento clicando neste botão.',
            position: 'left'
        }
    ];

    // Inicializar o tour
    introJs().setOptions({
        steps: steps,
        nextLabel: 'Próximo',
        prevLabel: 'Anterior',
        skipLabel: 'Pular',
        doneLabel: 'Concluir',
        showProgress: true,
        showBullets: false,
        showStepNumbers: true,
        exitOnOverlayClick: false,
        disableInteraction: false,
        scrollToElement: true
    }).start();
}

// Inicializar o botão de tour quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    const tourButton = document.getElementById('tour-button');
    if (tourButton) {
        tourButton.addEventListener('click', startTour);
    }
});