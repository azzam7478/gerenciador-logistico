
<?php
require_once "../config/conexao.php";

$pesquisa = trim($_GET["pesquisa"] ?? "");

$sql = "SELECT * FROM produtos
        WHERE codigo LIKE :pesquisa
           OR descricao LIKE :pesquisa
        ORDER BY id DESC";

$stmt = $conexao->prepare($sql);

$stmt->execute([
    ":pesquisa" => "%" . $pesquisa . "%"
]);

$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos - Logística Web</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="sidebar">

    <h4><i class="bi bi-truck"></i> LOGÍSTICA WEB</h4>

    <a href="../index.php">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>

    <a href="listar.php" class="active">
        <i class="bi bi-box-seam me-2"></i> Produtos
    </a>

    <a href="../enderecos/listar.php">
        <i class="bi bi-geo-alt me-2"></i> Endereços
    </a>

    <a href="../transportadores/listar.php">
        <i class="bi bi-truck me-2"></i> Transportadores
    </a>

    <a href="../fretes/listar.php">
        <i class="bi bi-clipboard-check me-2"></i> Fretes
    </a>

    <a href="../fretes/relatorio.php">
        <i class="bi bi-file-earmark-text me-2"></i> Relatórios
    </a>

</div>

<div class="main-content">

    <div class="topbar d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-1">Produtos</h4>
            <small class="text-muted">
                Consulta e gerenciamento de produtos.
            </small>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Novo Produto
        </a>

    </div>

    <?php if (($_GET["sucesso"] ?? "") === "cadastro"): ?>

        <div class="alert alert-success">
            Produto cadastrado com sucesso!
        </div>

    <?php elseif (($_GET["sucesso"] ?? "") === "edicao"): ?>

        <div class="alert alert-success">
            Produto atualizado com sucesso!
        </div>

    <?php elseif (($_GET["sucesso"] ?? "") === "exclusao"): ?>

        <div class="alert alert-success">
            Produto inativado com sucesso!
        </div>

    <?php endif; ?>

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <form method="GET" class="row g-2 mb-4">

                <div class="col-md-8">

                    <input type="text"
                           name="pesquisa"
                           class="form-control"
                           placeholder="Pesquisar por código ou descrição..."
                           value="<?php echo htmlspecialchars($pesquisa); ?>">

                </div>

                <div class="col-md-4">

                    <button class="btn btn-primary">
                        <i class="bi bi-search"></i> Pesquisar
                    </button>

                    <a href="listar.php" class="btn btn-secondary">
                        Limpar
                    </a>

                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Peso (kg)</th>
                            <th>Unidade</th>
                            <th>Situação</th>
                            <th>Ações</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($produtos) > 0): ?>

                            <?php foreach ($produtos as $produto): ?>

                                <tr>

                                    <td>
                                        <?php echo htmlspecialchars($produto["codigo"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($produto["descricao"]); ?>
                                    </td>

                                    <td>
                                        <?php echo number_format(
                                            (float)$produto["peso"],
                                            2,
                                            ",",
                                            "."
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($produto["unidade"]); ?>
                                    </td>

                                    <td>

                                        <?php if ($produto["ativo"] == 1): ?>

                                            <span class="badge bg-success">
                                                Ativo
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                Inativo
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <a href="editar.php?id=<?php echo $produto["id"]; ?>"
                                           class="btn btn-sm btn-warning"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php if ($produto["ativo"] == 1): ?>

                                            <form method="POST"
                                                  action="excluir.php"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Deseja realmente inativar este produto?');">

                                                <input type="hidden"
                                                       name="id"
                                                       value="<?php echo $produto["id"]; ?>">

                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        title="Inativar">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6"
                                    class="text-center text-muted py-4">
                                    Nenhum produto encontrado.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small">
                Total de registros encontrados:
                <?php echo count($produtos); ?>
            </div>

        </div>

    </div>

</div>

</body>
</html>
