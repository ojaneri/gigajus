/**
 * Arquivo JavaScript comum para funções compartilhadas
 * Este arquivo contém funções que são usadas em várias partes do sistema
 */

/**
 * Mostra uma notificação temporária
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo de notificação (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Verificar se já existe um container de notificações
    let notificationContainer = document.querySelector('.notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // Criar notificação
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Ícone baseado no tipo
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'times-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 10);
    
    // Configurar botão de fechar
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // Fechar automaticamente após 5 segundos
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

/**
 * Fecha uma notificação com animação
 * @param {HTMLElement} notification - Elemento da notificação
 */
function closeNotification(notification) {
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
    
    setTimeout(() => {
        notification.remove();
        
        // Remover container se não houver mais notificações
        const notificationContainer = document.querySelector('.notification-container');
        if (notificationContainer && notificationContainer.children.length === 0) {
            notificationContainer.remove();
        }
    }, 300);
}

/**
 * Inicializa tooltips para elementos com data-tooltip
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltipText = this.getAttribute('data-tooltip');
            
            // Criar elemento tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            document.body.appendChild(tooltip);
            
            // Posicionar tooltip
            const rect = this.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 10}px`;
            tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
            tooltip.style.opacity = '1';
            
            // Armazenar referência ao tooltip
            this.tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltip) {
                this.tooltip.remove();
                this.tooltip = null;
            }
        });
    });
}

/**
 * Funções para gerenciamento de cookies
 */
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Inicializar tooltips quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initInputTypingAnimation(); // Inicializar animação de digitação
});

/**
 * Adiciona animação de digitação suave aos inputs na página de login.
 */
function initInputTypingAnimation() {
    const inputs = document.querySelectorAll('.login-form-container input.form-control');
    inputs.forEach(input => {
        input.setAttribute('placeholder', ''); // Limpa o placeholder original
        const placeholderText = input.getAttribute('placeholder-text');
        if (placeholderText) {
            typeWriter(input, placeholderText, 50); // Inicia a animação
        }
    });
}

/**
 * Efeito de máquina de escrever para placeholders de inputs.
 * @param {HTMLElement} el - Elemento input.
 * @param {string} text - Texto do placeholder.
 * @param {number} speed - Velocidade da digitação (ms por caractere).
 */
function typeWriter(el, text, speed) {
    let i = 0;
    function frame() {
        if (i < text.length) {
            el.setAttribute('placeholder', el.getAttribute('placeholder') + text.charAt(i));
            i++;
            setTimeout(frame, speed);
        }
    }
    frame();
}