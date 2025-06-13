<?php
/**
 * mobile_detect.php
 * Função para detectar dispositivos móveis e redirecionar para a interface mobile.
 * Autor: Kilo Code
 * Data: 2025-05-18
 */

/**
 * Detecta se o dispositivo atual é um dispositivo móvel
 * @return bool Retorna true se for um dispositivo móvel, false caso contrário
 */
function is_mobile() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Lista de palavras-chave comuns em dispositivos móveis
    $mobile_agents = array(
        'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone',
        'webOS', 'Mobile', 'Phone', 'Tablet', 'Opera Mini', 'Opera Mobi'
    );
    
    // Verifica se alguma das palavras-chave está presente no user agent
    foreach ($mobile_agents as $agent) {
        if (stripos($user_agent, $agent) !== false) {
            return true;
        }
    }
    
    // Verifica se o cabeçalho HTTP_X_WAP_PROFILE está presente (comum em dispositivos móveis)
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
        return true;
    }
    
    // Verifica se o cabeçalho HTTP_ACCEPT contém wap
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false) {
        return true;
    }
    
    // Alguns dispositivos móveis enviam o cabeçalho HTTP_X_OPERAMINI_PHONE_UA
    if (isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])) {
        return true;
    }
    
    return false;
}

/**
 * Redireciona para a versão mobile de uma página se o usuário estiver em um dispositivo móvel
 * @param string $current_page Nome da página atual
 * @return void
 */
function redirect_to_mobile($current_page) {
    if (is_mobile()) {
        // Obtém o nome do arquivo atual sem a extensão
        $page_name = pathinfo($current_page, PATHINFO_FILENAME);
        
        // Constrói o nome do arquivo mobile
        $mobile_page = "mobile/{$page_name}.php";
        
        // Verifica se o arquivo mobile existe
        if (file_exists($mobile_page)) {
            // Passa todos os parâmetros GET para a página mobile
            $query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
            
            // Redireciona para a versão mobile
            header("Location: {$mobile_page}{$query_string}");
            exit();
        }
    }
}