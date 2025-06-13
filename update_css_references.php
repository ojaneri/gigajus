<?php
/**
 * Script para atualizar referências de CSS
 * Este script substitui referências a style.css por unified.css em todos os arquivos PHP
 */

// Diretório base
$baseDir = __DIR__;

// Função para buscar arquivos PHP recursivamente
function findPhpFiles($dir) {
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $result = array_merge($result, findPhpFiles($path));
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $result[] = $path;
        }
    }
    
    return $result;
}

// Encontrar todos os arquivos PHP
$phpFiles = findPhpFiles($baseDir);
$updatedFiles = 0;

// Padrão a ser substituído
$search = 'href="assets/css/unified.css"';
$replace = 'href="assets/css/unified.css"';

// Processar cada arquivo
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    
    if (strpos($content, $search) !== false) {
        $newContent = str_replace($search, $replace, $content);
        file_put_contents($file, $newContent);
        $updatedFiles++;
        echo "Atualizado: " . basename($file) . "\n";
    }
}

echo "\nTotal de arquivos atualizados: $updatedFiles\n";
echo "Concluído!\n";