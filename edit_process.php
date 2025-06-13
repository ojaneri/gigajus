<?php
require 'config.php';
include 'header.php';

// Verificar se o ID do processo foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID do processo não fornecido.";
    exit();
}

$id_processo = $_GET['id'];

// Obter os detalhes do processo do banco de dados
$stmt = $conn->prepare("SELECT * FROM processos WHERE id_processo = ?");
$stmt->bind_param("i", $id_processo);
$stmt->execute();
$result = $stmt->get_result();
$processo = $result->fetch_assoc();

if (!$processo) {
    echo "Processo não encontrado.";
    exit();
}

// Obter a lista de clientes para o formulário
$stmt = $conn->prepare("SELECT id_cliente, nome FROM clientes WHERE ativo = 1");
$stmt->execute();
$clientes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Processo</title>
    <link rel="stylesheet" href="assets/css/unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .process-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            max-width: 700px;
            margin: 40px auto 0 auto;
            padding: 32px 36px 28px 36px;
        }
        .process-card h2 {
            margin-bottom: 24px;
            font-size: 2rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 32px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            font-weight: 600;
            color: #34495e;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .checkbox-group {
            display: flex;
            gap: 18px;
            margin-top: 4px;
        }
        .text-center {
            text-align: center;
        }
        .btn-salvar {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-salvar:hover {
            background: #218838;
        }
        .form-control {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 1rem;
        }
        @media (max-width: 700px) {
            .process-card {
                padding: 18px 6vw 18px 6vw;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="process-card">
        <h2><i class="fas fa-edit"></i> Editar Processo</h2>
        <form action="edit_process_action.php" method="POST">
            <input type="hidden" name="id_processo" value="<?php echo $id_processo; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_cliente"><i class="fas fa-user"></i> Cliente</label>
                    <select id="id_cliente" name="id_cliente" required class="form-control">
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $cliente['id_cliente']; ?>" <?php echo $cliente['id_cliente'] == $processo['id_cliente'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="numero_processo"><i class="fas fa-hashtag"></i> Número do Processo</label>
                    <input type="text" id="numero_processo" name="numero_processo" value="<?php echo htmlspecialchars($processo['numero_processo']); ?>" required class="form-control">
                </div>
                <div class="form-group full-width">
                    <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="descricao" name="descricao" required class="form-control"><?php echo htmlspecialchars($processo['descricao']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                    <input type="text" id="status" name="status" value="<?php echo htmlspecialchars($processo['status']); ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="tribunal"><i class="fas fa-university"></i> Tribunal</label>
                    <input type="text" id="tribunal" name="tribunal" value="<?php echo htmlspecialchars($processo['tribunal']); ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="data_abertura"><i class="fas fa-calendar-plus"></i> Data de Abertura</label>
                    <input type="date" id="data_abertura" name="data_abertura" value="<?php echo $processo['data_abertura']; ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="data_fechamento"><i class="fas fa-calendar-check"></i> Data de Fechamento</label>
                    <input type="date" id="data_fechamento" name="data_fechamento" value="<?php echo $processo['data_fechamento']; ?>" class="form-control">
                </div>
                <div class="form-group full-width">
                    <label for="status_externo"><i class="fas fa-external-link-alt"></i> Status Externo</label>
                    <textarea id="status_externo" name="status_externo" class="form-control"><?php echo htmlspecialchars($processo['status_externo']); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label for="observacoes"><i class="fas fa-sticky-note"></i> Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control"><?php echo htmlspecialchars($processo['observacoes']); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Notificações</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" id="notificar_whatsapp" name="notificar[]" value="whatsapp" <?php echo in_array('whatsapp', explode(',', $processo['notificar'])) ? 'checked' : ''; ?>> WhatsApp</label>
                        <label><input type="checkbox" id="notificar_sms" name="notificar[]" value="sms" <?php echo in_array('sms', explode(',', $processo['notificar'])) ? 'checked' : ''; ?>> SMS</label>
                        <label><input type="checkbox" id="notificar_email" name="notificar[]" value="email" <?php echo in_array('email', explode(',', $processo['notificar'])) ? 'checked' : ''; ?>> E-mail</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="periodicidade_notificacao"><i class="fas fa-clock"></i> Periodicidade (dias)</label>
                    <input type="number" id="periodicidade_notificacao" name="periodicidade_notificacao" value="<?php echo $processo['periodicidade_notificacao']; ?>" required class="form-control">
                </div>
                <div class="form-group full-width text-center" style="margin-top: 18px;">
                    <button type="submit" class="btn btn-salvar"><i class="fas fa-save"></i> Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
