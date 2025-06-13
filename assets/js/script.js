document.addEventListener('DOMContentLoaded', function() {
    // Theme Management
    const root = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'law';
    root.setAttribute('data-theme', savedTheme);
    
    // Adicionar botão de alternância de tema
    const header = document.querySelector('header');
    if (header) {
        const themeToggleBtn = document.createElement('button');
        themeToggleBtn.className = 'theme-toggle';
        themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        themeToggleBtn.title = 'Alternar tema claro/escuro';
        
        // Atualizar ícone baseado no tema atual
        updateThemeIcon(themeToggleBtn, savedTheme);
        
        // Adicionar ao header
        header.appendChild(themeToggleBtn);
        
        // Adicionar evento de clique
        themeToggleBtn.addEventListener('click', function() {
            const currentTheme = root.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Aplicar novo tema
            root.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Atualizar ícone
            updateThemeIcon(themeToggleBtn, newTheme);
        });
    }
    
    // Função para atualizar o ícone do botão de tema
    function updateThemeIcon(button, theme) {
        if (theme === 'dark') {
            button.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            button.innerHTML = '<i class="fas fa-moon"></i>';
        }
    }

    // As funções setCookie e getCookie foram movidas para common.js

    // Sidebar Management
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (!menuToggle || !sidebar || !mainContent) {
        console.error('Sidebar elements not found');
        return;
    }
    
    // Create mobile menu button
    const mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.className = 'mobile-menu-btn';
    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(mobileMenuBtn);
    
    // Initialize sidebar state - default to collapsed
    const sidebarState = getCookie('sidebarState') || 'collapsed';
    
    // Apply initial state
    if (sidebarState === 'collapsed' || !sidebarState) {
        sidebar.classList.add('collapsed');
        // Let CSS handle the margin through the .sidebar.collapsed ~ .main-content selector
    }
    
    // Toggle sidebar function
    function toggleSidebar(e) {
        if (e) e.preventDefault();
        
        const isCollapsed = sidebar.classList.contains('collapsed');
        sidebar.classList.toggle('collapsed');
        
        if (window.innerWidth > 1024) {
            // Let CSS handle the margin through the .sidebar.collapsed ~ .main-content selector
            // Save state in cookie
            setCookie('sidebarState', isCollapsed ? 'expanded' : 'collapsed', 365);
        } else {
            sidebar.classList.toggle('mobile-visible');
        }
    }
    
    // Event listeners for sidebar toggle
    menuToggle.addEventListener('click', toggleSidebar);
    mobileMenuBtn.addEventListener('click', toggleSidebar);
    
    // Handle window resize
    let timeout;
    window.addEventListener('resize', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('mobile-visible');
                const isCollapsed = getCookie('sidebarState') === 'collapsed';
                sidebar.classList.toggle('collapsed', isCollapsed);
                // Let CSS handle the margin through the .sidebar.collapsed ~ .main-content selector
            } else {
                sidebar.classList.remove('collapsed');
                // Let CSS handle the margin through media queries
            }
        }, 100);
    });

    // Preserve sidebar state in navigation
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Only modify links that are actual navigation (not javascript:void(0))
            if (!link.getAttribute('href').startsWith('javascript')) {
                const currentState = getCookie('sidebarState');
                // Add state to URL if it's collapsed
                if (currentState === 'collapsed') {
                    const url = new URL(link.href);
                    url.searchParams.set('sidebar', 'collapsed');
                    link.href = url.toString();
                }
            }
        });
    });

    // Check URL for sidebar state
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('sidebar') === 'collapsed') {
        sidebar.classList.add('collapsed');
        // Let CSS handle the margin through the .sidebar.collapsed ~ .main-content selector
        setCookie('sidebarState', 'collapsed', 365);
    }
});
// Função para alternar o modal de detalhes
function toggleIntimacao(row) {
    const cells = row.querySelectorAll('td');
    if (cells.length < 7) return; // Verifica se tem todas colunas (7 colunas na tabela)
    
    // Extrai dados das células conforme estrutura da tabela:
    // Extrai dados das células conforme índice correto:
    const dataPublicacao = cells[1]?.textContent || '';  // Índice 1: Publicação
    const dataDivulgacao = cells[2]?.textContent || '';   // Índice 2: Divulgação
    const processo = cells[3]?.textContent || '';         // Índice 3: Processo
    const advogado = cells[4]?.textContent || '';         // Índice 4: Advogado
    const jornal = cells[5]?.textContent || '';           // Índice 5: Jornal
    const descricaoElem = cells[6]?.querySelector('.descricao-completa'); // Índice 6: Descrição
    const descricao = descricaoElem ? descricaoElem.innerHTML : '';
    
    let modal = document.getElementById('detail-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'detail-modal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content" onclick="event.stopPropagation()">
                <button class="close-modal">&times;</button>
                <h3>Detalhes da Intimação</h3>
                <div class="modal-grid">
                    <div><strong>Data Publicação:</strong> <span id="modal-data-publicacao">${dataPublicacao}</span></div>
                    <div><strong>Data Divulgação:</strong> <span id="modal-data-divulgacao">${dataDivulgacao}</span></div>
                    <div><strong>Processo:</strong> <span id="modal-processo">${processo}</span></div>
                    <div><strong>Advogado:</strong> <span id="modal-advogado">${advogado}</span></div>
                    <div><strong>Jornal:</strong> <span id="modal-jornal">${jornal}</span></div>
                    <div class="full-width">
                        <strong>Descrição:</strong>
                        <div class="modal-descricao" id="modal-descricao">${descricao}</div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        modal.querySelector('.close-modal').addEventListener('click', closeModal);
        modal.addEventListener('click', closeModal);
    } else {
        // Atualiza conteúdo se o modal já existir
        // Atualiza todos os campos incluindo as datas
        document.getElementById('modal-data-publicacao').textContent = dataPublicacao;
        document.getElementById('modal-data-divulgacao').textContent = dataDivulgacao;
        document.getElementById('modal-processo').textContent = processo;
        document.getElementById('modal-advogado').textContent = advogado;
        document.getElementById('modal-jornal').textContent = jornal;
        document.getElementById('modal-descricao').innerHTML = descricao;
    }
    
    modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
}

function closeModal() {
    const modal = document.getElementById('detail-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}
