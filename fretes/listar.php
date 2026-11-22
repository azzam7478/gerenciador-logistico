
<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../config/conexao.php";

// ==================================================
// ATUALIZAR STATUS DO FRETE
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {

    $freteId = filter_input(INPUT_POST, 'frete_id', FILTER_VALIDATE_INT);
    $novoStatus = $_POST['status'] ?? '';

    $statusPermitidos = [
        'PENDENTE',
        'EM ANDAMENTO',
        'CONCLUIDO'
    ];

    if ($freteId && in_array($novoStatus, $statusPermitidos, true)) {

        $sqlUpdate = "UPDATE fretes
                      SET status = :status
                      WHERE id = :id";

        $stmtUpdate = $conexao->prepare($sqlUpdate);

        $stmtUpdate->execute([
            ':status' => $novoStatus,
            ':id' => $freteId
        ]);

        header("Location: listar.php?atualizado=1");
        exit;

    } else {

        header("Location: listar.php?erro=1");
        exit;

    }
}

// ==================================================
// CONSULTAR FRETES
// ==================================================

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

// ==================================================
// BUSCAR PRODUTOS DOS FRETES
// ==================================================

$sqlProdutos = "SELECT
                    fp.frete_id,
                    p.codigo,
                    p.descricao,
                    p.unidade,
                    fp.quantidade
                FROM frete_produtos fp
                INNER JOIN produtos p
                    ON p.id = fp.produto_id
                ORDER BY p.descricao";

$stmtProdutos = $conexao->prepare($sqlProdutos);
$stmtProdutos->execute();

$produtosPorFrete = [];

while ($produto = $stmtProdutos->fetch(PDO::FETCH_ASSOC)) {

    $produtosPorFrete[$produto['frete_id']][] = $produto;

}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lista de Fretes</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .status-form {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 145px;
        }

        .status-form select {
            padding: 7px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .status-form button {
            padding: 7px;
            cursor: pointer;
        }

        .mensagem {
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .sucesso {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .erro {
            background-color: #f8d7da;
            color: #842029;
        }

        .btn-detalhes {
            white-space: nowrap;
        }

        .produtos-lista {
            min-width: 180px;
        }
    </style>

</head>

<body>

<div class="container-fluid p-4">

    <h1 class="mb-3">Fretes Cadastrados</h1>

    <p class="mb-4">

        <a href="cadastrar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>
            Cadastrar Novo Frete
        </a>

        <a href="relatorio.php" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text"></i>
            Relatório de Fretes
        </a>

        <a href="../index.php" class="btn btn-outline-secondary">
            <i class="bi bi-house"></i>
            Voltar ao início
        </a>

    </p>

    <!-- MENSAGENS -->

    <?php if (isset($_GET['atualizado'])): ?>

        <div class="mensagem sucesso">
            Status do frete atualizado com sucesso!
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>

        <div class="mensagem erro">
            Não foi possível atualizar o status. Verifique os dados.
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['nao_encontrado'])): ?>

        <div class="mensagem erro">
            O frete solicitado não foi encontrado.
        </div>

    <?php endif; ?>

    <!-- TABELA DE FRETES -->

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Transportador</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Produtos</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($fretes) > 0): ?>

                        <?php foreach ($fretes as $frete): ?>

                            <tr>

                                <td>
                                    #<?= htmlspecialchars($frete['id']) ?>
                                </td>

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

                                <td class="produtos-lista">

                                    <?php
                                    $produtos = $produtosPorFrete[$frete['id']] ?? [];

                                    if (count($produtos) > 0):

                                        foreach ($produtos as $produto):
                                    ?>

                                            <div class="mb-2">

                                                <strong>
                                                    <?= htmlspecialchars($produto['codigo']) ?>
                                                </strong>

                                                -
                                                <?= htmlspecialchars($produto['descricao']) ?>

                                                <br>

                                                <small class="text-muted">
                                                    Quantidade:

                                                    <?= number_format(
                                                        (float)$produto['quantidade'],
                                                        2,
                                                        ',',
                                                        '.'
                                                    ) ?>

                                                    <?= htmlspecialchars($produto['unidade']) ?>
                                                </small>

                                            </div>

                                    <?php
                                        endforeach;

                                    else:

                                        echo "Nenhum produto vinculado";

                                    endif;
                                    ?>

                                </td>

                                <!-- ALTERAR STATUS -->

                                <td>

                                    <form method="POST" class="status-form">

                                        <input
                                            type="hidden"
                                            name="frete_id"
                                            value="<?= htmlspecialchars($frete['id']) ?>"
                                        >

                                        <select name="status" required>

                                            <option value="PENDENTE"
                                                <?= $frete['status'] == 'PENDENTE' ? 'selected' : '' ?>>
                                                Pendente
                                            </option>

                                            <option value="EM ANDAMENTO"
                                                <?= $frete['status'] == 'EM ANDAMENTO' ? 'selected' : '' ?>>
                                                Em andamento
                                            </option>

                                            <option value="CONCLUIDO"
                                                <?= in_array($frete['status'], ['CONCLUIDO', 'CONCLUÍDO'], true) ? 'selected' : '' ?>>
                                                Concluído
                                            </option>

                                        </select>

                                        <button
                                            type="submit"
                                            name="atualizar_status"
                                            value="1"
                                            class="btn btn-sm btn-primary"
                                        >
                                            Salvar status
                                        </button>

                                    </form>

                                </td>

                                <!-- BOTÃO DETALHES -->

                                <td>

                                    <a
                                        href="detalhes.php?id=<?= urlencode((string)$frete['id']) ?>"
                                        class="btn btn-sm btn-outline-primary btn-detalhes"
                                    >
                                        <i class="bi bi-eye"></i>
                                        Visualizar
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="text-center text-muted py-4">
                                Nenhum frete cadastrado.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <footer class="text-center text-muted mt-4 small">
        Gerenciador Logístico | Projeto APO - UNIPAR
    </footer>

</div>

</body>
</html>
