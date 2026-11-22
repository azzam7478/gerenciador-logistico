
<?php
require_once "../config/conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

// Buscar produto
$stmt = $conexao->prepare(
    "SELECT * FROM produtos WHERE id = :id"
);

$stmt->execute([":id" => $id]);

$produto = $stmt->fetch();

if (!$produto) {
    header("Location: listar.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $codigo = trim($_POST["codigo"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $peso = str_replace(",", ".", trim($_POST["peso"] ?? ""));
    $unidade = $_POST["unidade"] ?? "";
    $ativo = $_POST["ativo"] ?? "";

    $unidadesPermitidas = ["UN", "KG", "MT", "CX", "PC"];

    if ($codigo === "" || $descricao === "" || $peso === "") {

        $erro = "Preencha todos os campos obrigatórios.";

    } elseif (!is_numeric($peso) || (float)$peso < 0) {

        $erro = "Informe um peso válido.";

    } elseif (!in_array($unidade, $unidadesPermitidas, true)) {

        $erro = "Unidade inválida.";

    } elseif (!in_array($ativo, ["0", "1"], true)) {

        $erro = "Situação inválida.";

    } else {

        try {

            $sql = "UPDATE produtos SET
                        codigo = :codigo,
                        descricao = :descricao,
                        peso = :peso,
                        unidade = :unidade,
                        ativo = :ativo
                    WHERE id = :id";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ":codigo" => $codigo,
                ":descricao" => $descricao,
                ":peso" => $peso,
                ":unidade" => $unidade,
                ":ativo" => $ativo,
                ":id" => $id
            ]);

            header("Location: listar.php?sucesso=edicao");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == "23000") {
                $erro = "Este código já pertence a outro produto.";
            } else {
                $erro = "Não foi possível atualizar o produto.";
            }

        }

    }

    // Manter os dados preenchidos caso ocorra erro
    $produto["codigo"] = $codigo;
    $produto["descricao"] = $descricao;
    $produto["peso"] = $peso;
    $produto["unidade"] = $unidade;
    $produto["ativo"] = $ativo;

}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Produto - Logística Web</title>

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

    <div class="topbar">
        <h4 class="mb-1">Editar Produto</h4>
        <small class="text-muted">
            Alteração dos dados do produto.
        </small>
    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <?php if ($erro !== ""): ?>

                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erro); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-4">

                        <label class="form-label">Código *</label>

                        <input type="text"
                               name="codigo"
                               class="form-control"
                               maxlength="30"
                               value="<?php echo htmlspecialchars($produto["codigo"]); ?>"
                               required>

                    </div>

                    <div class="col-md-8">

                        <label class="form-label">Descrição *</label>

                        <input type="text"
                               name="descricao"
                               class="form-control"
                               maxlength="150"
                               value="<?php echo htmlspecialchars($produto["descricao"]); ?>"
                               required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">Peso (kg) *</label>

                        <input type="number"
                               name="peso"
                               class="form-control"
                               step="0.01"
                               min="0"
                               value="<?php echo htmlspecialchars((string)$produto["peso"]); ?>"
                               required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">Unidade *</label>

                        <select name="unidade" class="form-select" required>

                            <?php
                            $unidades = ["UN", "KG", "MT", "CX", "PC"];

                            foreach ($unidades as $opcao):
                            ?>

                                <option value="<?php echo $opcao; ?>"
                                    <?php echo $produto["unidade"] === $opcao ? "selected" : ""; ?>>
                                    <?php echo $opcao; ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">Situação *</label>

                        <select name="ativo" class="form-select" required>

                            <option value="1"
                                <?php echo $produto["ativo"] == 1 ? "selected" : ""; ?>>
                                Ativo
                            </option>

                            <option value="0"
                                <?php echo $produto["ativo"] == 0 ? "selected" : ""; ?>>
                                Inativo
                            </option>

                        </select>

                    </div>

                </div>

                <hr class="my-4">

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>
                    Atualizar Produto
                </button>

                <a href="listar.php" class="btn btn-secondary">
                    Voltar
                </a>

            </form>

        </div>

    </div>

</div>

</body>
</html>
