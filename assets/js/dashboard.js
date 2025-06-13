/**
 * Dashboard JavaScript
 * Adiciona funcionalidades interativas ao dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar contadores animados
    initCounters();
    
    // Inicializar tooltips já está sendo chamado no common.js
    
    // Adicionar interatividade ao mini calendário
    initMiniCalendar();
    
    // Adicionar interatividade aos cards
    initCards();
});

/**
 * Inicializa contadores animados para os números estatísticos
 */
function initCounters() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(statNumber => {
        const targetValue = parseInt(statNumber.textContent);
        const duration = 1500; // duração da animação em ms
        const frameDuration = 1000 / 60; // 60fps
        const totalFrames = Math.round(duration / frameDuration);
        const easeOutQuad = t => t * (2 - t);
        
        let frame = 0;
        const countTo = targetValue;
        
        // Iniciar com zero
        statNumber.textContent = '0';
        
        // Função de animação
        const counter = setInterval(() => {
            frame++;
            const progress = easeOutQuad(frame / totalFrames);
            const currentCount = Math.round(countTo * progress);
            
            if (currentCount === countTo) {
                clearInterval(counter);
            } else {
                statNumber.textContent = currentCount;
            }
            
            if (frame === totalFrames) {
                clearInterval(counter);
            }
        }, frameDuration);
    });
}

// A função initTooltips foi movida para common.js

/**
 * Inicializa interatividade para o mini calendário
 */
function initMiniCalendar() {
    const calendarDays = document.querySelectorAll('.mini-calendar-day:not(.empty)');
    
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            // Aqui você pode adicionar a lógica para mostrar eventos do dia
            // Por exemplo, abrir um modal com os eventos
            const dayNumber = this.textContent.trim();
            
            // Exemplo: mostrar alerta com o dia clicado
            if (this.classList.contains('has-event')) {
                showNotification(`Eventos para o dia ${dayNumber}`, 'info');
            } else {
                showNotification(`Nenhum evento para o dia ${dayNumber}`, 'info');
            }
        });
    });
}

/**
 * Inicializa interatividade para os cards do dashboard
 */
function initCards() {
    const cards = document.querySelectorAll('.dashboard-card');
    
    cards.forEach(card => {
        // Adicionar efeito de hover
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 15px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
    
    // Adicionar interatividade aos itens de tarefa
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(item => {
        item.addEventListener('click', function() {
            const taskTitle = this.querySelector('h3').textContent;
            // Aqui você pode adicionar a lógica para abrir a tarefa
            // Por exemplo, redirecionar para a página de detalhes da tarefa
            
            // Exemplo: mostrar notificação
            showNotification(`Tarefa selecionada: ${taskTitle}`, 'info');
        });
    });
    
    // Adicionar interatividade aos itens de atividade
    const activityItems = document.querySelectorAll('.activity-item');
    
    activityItems.forEach(item => {
        item.addEventListener('click', function() {
            const activityTitle = this.querySelector('h3').textContent;
            const activityType = this.querySelector('.activity-type').textContent;
            
            // Exemplo: mostrar notificação
            showNotification(`${activityType}: ${activityTitle}`, 'info');
        });
    });
}

// As funções showNotification e closeNotification foram movidas para common.js

// Os estilos CSS para notificações e tooltips foram movidos para o arquivo unified.css