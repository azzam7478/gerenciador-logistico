
<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../config/conexao.php";

// Receber ID do frete
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

// Consultar informações do frete
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
        WHERE f.id = :id";

$stmt = $conexao->prepare($sql);
$stmt->execute([':id' => $id]);

$frete = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o frete não existir
if (!$frete) {
    header("Location: listar.php?nao_encontrado=1");
    exit;
}

// Consultar produtos vinculados
$sqlProdutos = "SELECT
                    p.codigo,
                    p.descricao,
                    p.unidade,
                    fp.quantidade
                FROM frete_produtos fp
                INNER JOIN produtos p
                    ON p.id = fp.produto_id
                WHERE fp.frete_id = :id
                ORDER BY p.descricao";

$stmtProdutos = $conexao->prepare($sqlProdutos);
$stmtProdutos->execute([':id' => $id]);

$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Configurar cor do status
$status = strtoupper(trim($frete['status'] ?? ''));

$corStatus = 'secondary';

if ($status == 'PENDENTE') {
    $corStatus = 'warning';
} elseif ($status == 'EM ANDAMENTO') {
    $corStatus = 'primary';
} elseif ($status == 'CONCLUIDO' || $status == 'CONCLUÍDO') {
    $corStatus = 'success';
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detalhes do Frete #<?= htmlspecialchars($frete['id']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .detalhe-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #edf0f4;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .detalhe-card h5 {
            color: #172b4d;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .campo-label {
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .campo-valor {
            font-weight: 600;
            color: #263445;
            margin-bottom: 18px;
        }

        .local-card {
            background: #f7f9fc;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
        }

        .local-card i {
            font-size: 25px;
            color: #2685d8;
        }

        .tabela-produtos th {
            background: #f7f9fc;
            color: #536174;
            font-size: 13px;
        }

        .observacao {
            background: #f7f9fc;
            border-radius: 8px;
            padding: 15px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>

</head>

<body>

<div class="main-content" style="margin-left: 0;">

    <div class="topbar d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-1">
                <i class="bi bi-clipboard-check"></i>
                Detalhes do Frete #<?= htmlspecialchars($frete['id']) ?>
            </h4>

            <small class="text-muted">
                Informações completas do transporte
            </small>
        </div>

        <span class="badge bg-<?= $corStatus ?> fs-6">
            <?= htmlspecialchars($frete['status'] ?? '') ?>
        </span>

    </div>

    <!-- DADOS GERAIS -->

    <div class="detalhe-card">

        <h5>
            <i class="bi bi-info-circle"></i>
            Informações do transporte
        </h5>

        <div class="row">

            <div class="col-md-4">

                <div class="campo-label">Código do frete</div>

                <div class="campo-valor">
                    #<?= htmlspecialchars($frete['id']) ?>
                </div>

            </div>

            <div class="col-md-4">

                <div class="campo-label">Data do frete</div>

                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($frete['data_frete'])) ?>
                </div>

            </div>

            <div class="col-md-4">

                <div class="campo-label">Transportador</div>

                <div class="campo-valor">
                    <?= htmlspecialchars($frete['transportador'] ?? 'Não informado') ?>
                </div>

            </div>

        </div>

    </div>

    <!-- ORIGEM E DESTINO -->

    <div class="row g-3 mb-3">

        <div class="col-md-6">

            <div class="detalhe-card h-100">

                <div class="local-card">

                    <i class="bi bi-geo-alt-fill"></i>

                    <h5 class="mt-3">Origem</h5>

                    <div class="campo-label">Endereço</div>

                    <div class="campo-valor">
                        <?= htmlspecialchars($frete['origem'] ?? 'Não informado') ?>
                    </div>

                    <div class="campo-label">Cidade</div>

                    <div class="campo-valor mb-0">
                        <?= htmlspecialchars($frete['cidade_origem'] ?? 'Não informada') ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="detalhe-card h-100">

                <div class="local-card">

                    <i class="bi bi-geo-alt-fill text-success"></i>

                    <h5 class="mt-3">Destino</h5>

                    <div class="campo-label">Endereço</div>

                    <div class="campo-valor">
                        <?= htmlspecialchars($frete['destino'] ?? 'Não informado') ?>
                    </div>

                    <div class="campo-label">Cidade</div>

                    <div class="campo-valor mb-0">
                        <?= htmlspecialchars($frete['cidade_destino'] ?? 'Não informada') ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- PRODUTOS -->

    <div class="detalhe-card">

        <h5>
            <i class="bi bi-box-seam"></i>
            Produtos transportados
        </h5>

        <div class="table-responsive">

            <table class="table table-hover align-middle tabela-produtos">

                <thead>

                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Unidade</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($produtos) > 0): ?>

                        <?php foreach ($produtos as $produto): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($produto['codigo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produto['descricao']) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$produto['quantidade'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produto['unidade']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Nenhum produto vinculado a este frete.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="text-end text-muted small">
            Total de produtos diferentes: <?= count($produtos) ?>
        </div>

    </div>

    <!-- OBSERVAÇÕES -->

    <div class="detalhe-card">

        <h5>
            <i class="bi bi-chat-left-text"></i>
            Observações
        </h5>

        <div class="observacao">

            <?php if (!empty($frete['observacao'])): ?>

                <?= htmlspecialchars($frete['observacao']) ?>

            <?php else: ?>

                <span class="text-muted">
                    Nenhuma observação cadastrada.
                </span>

            <?php endif; ?>

        </div>

    </div>

    <!-- BOTÕES -->

    <div class="d-flex gap-2 mb-4">

        <a href="listar.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Voltar para fretes
        </a>

        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i>
            Imprimir detalhes
        </button>

    </div>

    <footer class="text-center text-muted small">
        Gerenciador Logístico | Projeto APO - UNIPAR
    </footer>

</div>

</body>
</html>
