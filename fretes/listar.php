
<?php
require_once "../config/conexao.php";

$sql = "SELECT
            f.id,
            f.data_frete,
            f.status,
            f.observacao,
            t.nome AS transportador,
            o.descricao AS origem,
            o.cidade AS cidade_origem,
            d.descricao AS destino,
            d.cidade AS cidade_destino
        FROM fretes f
        LEFT JOIN transportadores t
            ON t.id = f.transportador_id
        LEFT JOIN enderecos o
            ON o.id = f.endereco_origem_id
        LEFT JOIN enderecos d
            ON d.id = f.endereco_destino_id
        ORDER BY f.data_frete DESC, f.id DESC";

$stmt = $conexao->prepare($sql);
$stmt->execute();

$fretes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Fretes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <h1>Fretes Cadastrados</h1>

    <p>
        <a href="cadastrar.php">+ Cadastrar Novo Frete</a>
        |
        <a href="relatorio.php">Relatório de Fretes</a>
        |
        <a href="../index.php">Voltar ao início</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Transportador</th>
                <th>Origem</th>
                <th>Destino</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

        <?php if (count($fretes) > 0): ?>

            <?php foreach ($fretes as $frete): ?>

                <tr>
                    <td><?= htmlspecialchars($frete['id']) ?></td>

                    <td>
                        <?= date('d/m/Y', strtotime($frete['data_frete'])) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['transportador'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['cidade_origem'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['cidade_destino'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['status']) ?>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">Nenhum frete cadastrado.</td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>
