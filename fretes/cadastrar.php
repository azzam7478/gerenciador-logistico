
<?php
require_once "../config/conexao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data_frete = $_POST["data_frete"];
    $transportador_id = $_POST["transportador_id"];
    $endereco_origem_id = $_POST["endereco_origem_id"];
    $endereco_destino_id = $_POST["endereco_destino_id"];
    $observacao = $_POST["observacao"];
    $status = $_POST["status"];

    if (
        empty($data_frete) ||
        empty($transportador_id) ||
        empty($endereco_origem_id) ||
        empty($endereco_destino_id)
    ) {
        $mensagem = "Preencha todos os campos obrigatórios.";
    } else {

        try {
            $sql = "INSERT INTO fretes
                    (data_frete, transportador_id,
                     endereco_origem_id, endereco_destino_id,
                     observacao, status)
                    VALUES
                    (:data_frete, :transportador_id,
                     :origem, :destino, :observacao, :status)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ":data_frete" => $data_frete,
                ":transportador_id" => $transportador_id,
                ":origem" => $endereco_origem_id,
                ":destino" => $endereco_destino_id,
                ":observacao" => $observacao,
                ":status" => $status
            ]);

            header("Location: listar.php?sucesso=1");
            exit;

        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar frete: " . $e->getMessage();
        }
    }
}

// Buscar transportadores ativos
$stmt = $conexao->query(
    "SELECT id, nome FROM transportadores
     WHERE ativo = 1 ORDER BY nome"
);
$transportadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar endereços ativos
$stmt = $conexao->query(
    "SELECT id, descricao, cidade, estado
     FROM enderecos
     WHERE ativo = 1
     ORDER BY descricao"
);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Frete</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Cadastrar Frete</h1>

    <?php if ($mensagem != ""): ?>
        <p style="color:red;">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Data do Frete *</label>
        <input
            type="date"
            name="data_frete"
            value="<?= date('Y-m-d') ?>"
            required
        >

        <label>Transportador *</label>
        <select name="transportador_id" required>
            <option value="">Selecione o transportador</option>

            <?php foreach ($transportadores as $transportador): ?>
                <option value="<?= $transportador["id"] ?>">
                    <?= htmlspecialchars($transportador["nome"]) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Endereço de Origem *</label>
        <select name="endereco_origem_id" required>
            <option value="">Selecione o endereço de origem</option>

            <?php foreach ($enderecos as $endereco): ?>
                <option value="<?= $endereco["id"] ?>">
                    <?= htmlspecialchars(
                        $endereco["descricao"] . " - " .
                        $endereco["cidade"] . "/" .
                        $endereco["estado"]
                    ) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Endereço de Destino *</label>
        <select name="endereco_destino_id" required>
            <option value="">Selecione o endereço de destino</option>

            <?php foreach ($enderecos as $endereco): ?>
                <option value="<?= $endereco["id"] ?>">
                    <?= htmlspecialchars(
                        $endereco["descricao"] . " - " .
                        $endereco["cidade"] . "/" .
                        $endereco["estado"]
                    ) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Observação</label>
        <textarea
            name="observacao"
            rows="4"
            placeholder="Digite alguma observação sobre o frete"
        ></textarea>

        <label>Status</label>
        <select name="status" required>
            <option value="PENDENTE">Pendente</option>
            <option value="EM ANDAMENTO">Em andamento</option>
            <option value="CONCLUIDO">Concluído</option>
            <option value="CANCELADO">Cancelado</option>
        </select>

        <br><br>

        <button type="submit">Cadastrar Frete</button>

        <a href="listar.php">Voltar</a>

    </form>

</div>

</body>
</html>
