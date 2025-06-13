<?php
include 'includes/auth_check.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['texto']) || isset($_FILES['pdf'])) {
        // Handle form submission
        $texto = isset($_POST['texto']) ? $_POST['texto'] : '';
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0) {
            $texto = file_get_contents($_FILES['pdf']['tmp_name']);  // Simple PDF handling
        }
        
        include 'functions/functions_openai.php';
        $result = processDecision($texto);
        
        if ($result) {
            include 'functions/functions_bd.php';
            $data = [
                'numero_processo' => $result['numero_processo'] ?? '',
                'tipo_documento' => $result['tipo_documento'] ?? '',
                'relator' => $result['relator'] ?? '',
                'ementa' => $result['ementa'] ?? '',
                'inteiro_teor' => $result['inteiro_teor'] ?? '',
                'principais_argumentos' => $result['principais_argumentos'] ?? '',
                'procedente' => $result['procedente'] ?? false,
                'dano_moral' => $result['dano_moral'] ?? false,
                'valor_dano_moral' => $result['valor_dano_moral'] ?? 0,
                'categoria' => $result['categoria'] ?? '',
                'resultado_json' => json_encode($result)
            ];
            $id = insertDecisao($data);
            echo "Decisão inserida com ID: $id";
        } else {
            echo "Falha na análise.";
        }
    }
}
?>

<main>
    <h2>Analisar Decisão Judicial</h2>
    <form method="POST" enctype="multipart/form-data">
        <textarea name="texto" placeholder="Cole o texto da decisão aqui"></textarea><br>
        <input type="file" name="pdf"><br>
        <button type="submit">Analisar</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>