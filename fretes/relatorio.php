
<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../config/conexao.php";

// Data selecionada
$data = $_GET['data'] ?? date('Y-m-d');

// Consulta dos fretes do dia
$sql = "SELECT
            f.id,
            f.data_frete,
            f.status,
            f.observacao,
            t.nome AS transportador,
            o.cidade AS origem,
            d.cidade AS destino
        FROM fretes f
        LEFT JOIN transportadores t
            ON t.id = f.transportador_id
        LEFT JOIN enderecos o
            ON o.id = f.endereco_origem_id
        LEFT JOIN enderecos d
            ON d.id = f.endereco_destino_id
        WHERE f.data_frete = :data
        ORDER BY f.id DESC";

$stmt = $conexao->prepare($sql);
$stmt->execute([':data' => $data]);

$fretes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_fretes = count($fretes);

// Busca os produtos dos fretes consultados
$sqlProdutos = "SELECT
                    fp.frete_id,
                    p.codigo,
                    p.descricao,
                    p.unidade,
                    fp.quantidade
                FROM frete_produtos fp
                INNER JOIN produtos p
                    ON p.id = fp.produto_id
                INNER JOIN fretes f
                    ON f.id = fp.frete_id
                WHERE f.data_frete = :data
                ORDER BY p.descricao";

$stmtProdutos = $conexao->prepare($sqlProdutos);
$stmtProdutos->execute([':data' => $data]);

$produtosPorFrete = [];

while ($produto = $stmtProdutos->fetch(PDO::FETCH_ASSOC)) {
    $produtosPorFrete[$produto['frete_id']][] = $produto;
}

// ==================================================
// EXPORTAR RELATORIO CSV PARA EXCEL
// ==================================================

if (isset($_GET['exportar'])) {

    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');

    header(
        'Content-Disposition: attachment; filename="relatorio_fretes_' .
        $data . '.csv"'
    );

    header('Pragma: no-cache');
    header('Expires: 0');

    $arquivo = fopen('php://output', 'w');

    // BOM UTF-8 para preservar acentos no Excel
    fwrite($arquivo, "\xEF\xBB\xBF");

    // Cabeçalho do CSV
    fputcsv($arquivo, [
        'ID',
        'Data',
        'Transportador',
        'Origem',
        'Destino',
        'Codigo Produto',
        'Descricao Produto',
        'Quantidade',
        'Unidade',
        'Status',
        'Observacao'
    ], ';');

    // Exportar cada frete
    foreach ($fretes as $frete) {

        $produtos = $produtosPorFrete[$frete['id']] ?? [];

        // Se o frete não tiver produtos
        if (count($produtos) == 0) {

            fputcsv($arquivo, [
                $frete['id'],
                date('d/m/Y', strtotime($frete['data_frete'])),
                $frete['transportador'] ?? '',
                $frete['origem'] ?? '',
                $frete['destino'] ?? '',
                '',
                'Nenhum produto vinculado',
                '',
                '',
                $frete['status'] ?? '',
                $frete['observacao'] ?? ''
            ], ';');

        } else {

            // Uma linha para cada produto
            foreach ($produtos as $produto) {

                fputcsv($arquivo, [
                    $frete['id'],
                    date('d/m/Y', strtotime($frete['data_frete'])),
                    $frete['transportador'] ?? '',
                    $frete['origem'] ?? '',
                    $frete['destino'] ?? '',
                    $produto['codigo'],
                    $produto['descricao'],
                    number_format(
                        (float)$produto['quantidade'],
                        2,
                        ',',
                        '.'
                    ),
                    $produto['unidade'],
                    $frete['status'] ?? '',
                    $frete['observacao'] ?? ''
                ], ';');

            }
        }
    }

    fclose($arquivo);
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Fretes</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Relatório de Fretes</h1>

    <p>
        <a href="listar.php">Lista de Fretes</a> |
        <a href="cadastrar.php">Cadastrar Frete</a> |
        <a href="../index.php">Página Inicial</a>
    </p>

    <hr>

    <h2>Consultar Fretes por Data</h2>

    <form method="GET">

        <label>Selecione a data:</label>

        <input
            type="date"
            name="data"
            value="<?= htmlspecialchars($data) ?>"
            required
        >

        <button type="submit">
            Consultar
        </button>

    </form>

    <hr>

    <h2>Resultado do Relatório</h2>

    <p>
        <strong>Data consultada:</strong>
        <?= date('d/m/Y', strtotime($data)) ?>
    </p>

    <p>
        <strong>Total de fretes:</strong>
        <?= $total_fretes ?>
    </p>

    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Transportador</th>
                <th>Origem</th>
                <th>Destino</th>
                <th>Produtos</th>
                <th>Status</th>
                <th>Observação</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($total_fretes > 0): ?>

            <?php foreach ($fretes as $frete): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($frete['id']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['transportador'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['origem'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['destino'] ?? '') ?>
                    </td>

                    <td>
                        <?php
                        $produtos = $produtosPorFrete[$frete['id']] ?? [];

                        if (count($produtos) > 0):

                            foreach ($produtos as $produto):
                        ?>
                                <div>
                                    <strong>
                                        <?= htmlspecialchars($produto['codigo']) ?>
                                    </strong>
                                    -
                                    <?= htmlspecialchars($produto['descricao']) ?>
                                    <br>
                                    Quantidade:
                                    <?= number_format(
                                        (float)$produto['quantidade'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                    <?= htmlspecialchars($produto['unidade']) ?>
                                </div>
                        <?php
                            endforeach;

                        else:
                            echo "Nenhum produto vinculado";
                        endif;
                        ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['status'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($frete['observacao'] ?? '') ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="7">
                    Nenhum frete encontrado para esta data.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <br>

    <a href="relatorio.php?data=<?= urlencode($data) ?>&exportar=1">
        <button type="button">
            Baixar Relatório CSV
        </button>
    </a>

    <button onclick="window.print()">
        Imprimir Relatório
    </button>

</div>

</body>
</html>
