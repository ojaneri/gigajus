<?php
function getPDOConnection() {
    $dsn = 'mysql:host=localhost;dbname=smartjuris;charset=utf8mb4';
    $username = 'smartjuris_user';
    $password = 'SecurePassword2025';  // This should be in config.php or environment variables
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}

function insertDecisao($data) {
    $pdo = getPDOConnection();
    $sql = "INSERT INTO decisoes (numero_processo, tipo_documento, relator, ementa, inteiro_teor, principais_argumentos, procedente, dano_moral, valor_dano_moral, categoria, resultado_json) VALUES (:numero_processo, :tipo_documento, :relator, :ementa, :inteiro_teor, :principais_argumentos, :procedente, :dano_moral, :valor_dano_moral, :categoria, :resultado_json)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return $pdo->lastInsertId();
}

// Add more functions as needed, e.g., for reading data
function getDecisoes() {
    $pdo = getPDOConnection();
    $sql = "SELECT * FROM decisoes";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>