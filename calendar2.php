<?php
session_start();
require 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Buscar tarefas do dia
$stmt = $conn->prepare("SELECT id_evento, titulo, hora, descricao FROM eventos WHERE data = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Adicionar nova tarefa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_event'])) {
    $titulo = $_POST['titulo'];
    $hora = $_POST['hora'];
    $descricao = $_POST['descricao'];
    $stmt = $conn->prepare("INSERT INTO eventos (data, hora, titulo, descricao) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $date, $hora, $titulo, $descricao);
    $stmt->execute();
    $stmt->close();
    header("Location: calendar2.php?date=$date");
    exit();
}

// Marcar tarefa como concluída
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $stmt = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: calendar2.php?date=$date");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos do Dia - <?php echo $date; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Eventos de <?php echo date('d/m/Y', strtotime($date)); ?></h2>
    <ul>
        <?php if ($events): ?>
            <?php foreach ($events as $event): ?>
                <li>
                    <strong><?php echo htmlspecialchars($event['titulo']); ?></strong> - <?php echo $event['hora']; ?>
                    <p><?php echo htmlspecialchars($event['descricao']); ?></p>
                    <a href="calendar2.php?date=<?php echo $date; ?>&complete=<?php echo $event['id_evento']; }">Marcar como Concluído</a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Não há eventos agendados para este dia.</p>
        <?php endif; ?>
    </ul>
    
    <h3>Adicionar Novo Evento</h3>
    <form method="post">
        <label for="titulo">Título:</label>
        <input type="text" name="titulo" required>
        <label for="hora">Hora:</label>
        <input type="time" name="hora" required>
        <label for="descricao">Descrição:</label>
        <textarea name="descricao" required></textarea>
        <button type="submit" name="new_event">Adicionar Evento</button>
    </form>
</div>
</body>
</html>
