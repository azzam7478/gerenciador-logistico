
<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "config/conexao.php";

// Consultar quantidade de produtos
$totalProdutos = $conexao->query(
    "SELECT COUNT(*) FROM produtos WHERE ativo = 1"
)->fetchColumn();

// Consultar transportadores
$totalTransportadores = $conexao->query(
    "SELECT COUNT(*) FROM transportadores WHERE ativo = 1"
)->fetchColumn();

// Consultar total de fretes
$totalFretes = $conexao->query(
    "SELECT COUNT(*) FROM fretes"
)->fetchColumn();

// Consultar fretes pendentes
$totalPendentes = $conexao->query(
    "SELECT COUNT(*) FROM fretes WHERE status = 'PENDENTE'"
)->fetchColumn();

// Consultar os últimos fretes
$sql = "SELECT
            f.id,
            f.data_frete,
            f.status,
            t.nome AS transportador
        FROM fretes f
        INNER JOIN transportadores t
            ON t.id = f.transportador_id
        ORDER BY f.id DESC
        LIMIT 5";

$ultimosFretes = $conexao->query($sql)->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciador Logístico</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<!-- MENU LATERAL -->

<div class="sidebar">

    <h4>
        <i class="bi bi-truck"></i>
        LOGÍSTICA WEB
    </h4>

    <a href="index.php" class="active">
        <i class="bi bi-speedometer2 me-2"></i>
        Dashboard
    </a>

    <a href="produtos/listar.php">
        <i class="bi bi-box-seam me-2"></i>
        Produtos
    </a>

    <a href="enderecos/listar.php">
        <i class="bi bi-geo-alt me-2"></i>
        Endereços
    </a>

    <a href="transportadores/listar.php">
        <i class="bi bi-truck me-2"></i>
        Transportadores
    </a>

    <a href="fretes/listar.php">
        <i class="bi bi-clipboard-check me-2"></i>
        Fretes
    </a>

    <a href="fretes/relatorio.php">
        <i class="bi bi-file-earmark-text me-2"></i>
        Relatórios
    </a>

    <hr>

    <div class="text-center small text-secondary">
        Sistema de Gerenciamento Logístico
    </div>

</div>


<!-- CONTEÚDO PRINCIPAL -->

<div class="main-content">

    <div class="topbar d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-1">Painel de Controle</h4>
            <small class="text-muted">
                Bem-vindo ao Gerenciador Logístico
            </small>
        </div>

        <div class="text-end">
            <i class="bi bi-calendar3"></i>
            <?php echo date("d/m/Y"); ?>
        </div>

    </div>


    <!-- INDICADORES -->

    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-3">

            <div class="card-dashboard">

                <h6>
                    <i class="bi bi-box-seam text-primary"></i>
                    Produtos cadastrados
                </h6>

                <h2>
                    <?php echo $totalProdutos; ?>
                </h2>

                <small class="text-muted">
                    Produtos ativos
                </small>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="card-dashboard">

                <h6>
                    <i class="bi bi-truck text-success"></i>
                    Transportadores
                </h6>

                <h2>
                    <?php echo $totalTransportadores; ?>
                </h2>

                <small class="text-muted">
                    Transportadores ativos
                </small>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="card-dashboard">

                <h6>
                    <i class="bi bi-clipboard-check text-info"></i>
                    Total de fretes
                </h6>

                <h2>
                    <?php echo $totalFretes; ?>
                </h2>

                <small class="text-muted">
                    Fretes registrados
                </small>

            </div>

        </div>


        <div class="col-md-6 col-xl-3">

            <div class="card-dashboard">

                <h6>
                    <i class="bi bi-clock-history text-warning"></i>
                    Fretes pendentes
                </h6>

                <h2>
                    <?php echo $totalPendentes; ?>
                </h2>

                <small class="text-muted">
                    Aguardando andamento
                </small>

            </div>

        </div>

    </div>


    <!-- ATALHOS -->

    <h5 class="mb-3">Acesso rápido</h5>

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <a href="produtos/cadastrar.php" class="atalho">

                <i class="bi bi-plus-circle fs-2"></i>

                <h6 class="mt-2">
                    Cadastrar produto
                </h6>

            </a>

        </div>


        <div class="col-md-4">

            <a href="transportadores/cadastrar.php" class="atalho">

                <i class="bi bi-truck fs-2"></i>

                <h6 class="mt-2">
                    Cadastrar transportador
                </h6>

            </a>

        </div>


        <div class="col-md-4">

            <a href="fretes/cadastrar.php" class="atalho">

                <i class="bi bi-plus-square fs-2"></i>

                <h6 class="mt-2">
                    Gerar novo frete
                </h6>

            </a>

        </div>

    </div>


    <!-- ÚLTIMOS FRETES -->

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0">
                <i class="bi bi-clock-history"></i>
                Últimos fretes cadastrados
            </h5>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Código</th>
                            <th>Data</th>
                            <th>Transportador</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($ultimosFretes) > 0): ?>

                            <?php foreach ($ultimosFretes as $frete): ?>

                                <tr>

                                    <td>
                                        #<?php echo $frete["id"]; ?>
                                    </td>

                                    <td>
                                        <?php echo date(
                                            "d/m/Y",
                                            strtotime($frete["data_frete"])
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(
                                            $frete["transportador"]
                                        ); ?>
                                    </td>

                                    <td>

                                        <span class="badge bg-warning text-dark">
                                            <?php echo htmlspecialchars(
                                                $frete["status"]
                                            ); ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="4" class="text-center text-muted py-4">

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
