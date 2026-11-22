
<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../config/conexao.php";

$mensagem = "";

$data_frete = $_POST["data_frete"] ?? date("Y-m-d");
$transportador_id = $_POST["transportador_id"] ?? "";
$endereco_origem_id = $_POST["endereco_origem_id"] ?? "";
$endereco_destino_id = $_POST["endereco_destino_id"] ?? "";
$observacao = trim($_POST["observacao"] ?? "");
$status = $_POST["status"] ?? "PENDENTE";

$produtos = $_POST["produtos"] ?? [""];
$quantidades = $_POST["quantidades"] ?? [1];

if (!is_array($produtos)) {
    $produtos = [""];
}

if (!is_array($quantidades)) {
    $quantidades = [1];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        if (
            empty($data_frete) ||
            empty($transportador_id) ||
            empty($endereco_origem_id) ||
            empty($endereco_destino_id)
        ) {
            throw new Exception("Preencha todos os campos obrigatórios.");
        }

        $transportador_id = filter_var($transportador_id, FILTER_VALIDATE_INT);
        $origem_id = filter_var($endereco_origem_id, FILTER_VALIDATE_INT);
        $destino_id = filter_var($endereco_destino_id, FILTER_VALIDATE_INT);

        if (!$transportador_id || !$origem_id || !$destino_id) {
            throw new Exception("Transportador ou endereço inválido.");
        }

        if ($origem_id === $destino_id) {
            throw new Exception("A origem e o destino devem ser diferentes.");
        }

        $dataObj = DateTime::createFromFormat('!Y-m-d', $data_frete);

        if (!$dataObj || $dataObj->format('Y-m-d') !== $data_frete) {
            throw new Exception("Data do frete inválida.");
        }

        $statusPermitidos = [
            "PENDENTE",
            "EM ANDAMENTO",
            "CONCLUIDO",
            "CANCELADO"
        ];

        if (!in_array($status, $statusPermitidos, true)) {
            throw new Exception("Status inválido.");
        }

        if (count($produtos) === 0 || count($produtos) !== count($quantidades)) {
            throw new Exception("Selecione pelo menos um produto e informe sua quantidade.");
        }

        // Validar transportador
        $stmt = $conexao->prepare(
            "SELECT id FROM transportadores
             WHERE id = ? AND ativo = 1"
        );
        $stmt->execute([$transportador_id]);

        if (!$stmt->fetch()) {
            throw new Exception("Transportador não encontrado ou inativo.");
        }

        // Validar endereços
        $stmt = $conexao->prepare(
            "SELECT id FROM enderecos
             WHERE id IN (?, ?) AND ativo = 1"
        );
        $stmt->execute([$origem_id, $destino_id]);

        $enderecos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if (!in_array($origem_id, $enderecos, true)) {
            throw new Exception("Endereço de origem inválido ou inativo.");
        }

        if (!in_array($destino_id, $enderecos, true)) {
            throw new Exception("Endereço de destino inválido ou inativo.");
        }

        // Validar produtos antes de iniciar a gravação
        $produtosValidados = [];
        $produtosInseridos = [];

        foreach ($produtos as $indice => $produto) {

            $produto_id = filter_var($produto, FILTER_VALIDATE_INT);

            $quantidade = trim((string)($quantidades[$indice] ?? ""));
            $quantidade = str_replace(',', '.', $quantidade);

            if (
                !$produto_id ||
                !is_numeric($quantidade) ||
                (float)$quantidade <= 0
            ) {
                throw new Exception("Produto ou quantidade inválida.");
            }

            if (isset($produtosInseridos[$produto_id])) {
                throw new Exception("Não selecione o mesmo produto duas vezes.");
            }

            if (strlen(explode('.', $quantidade)[0]) > 8) {
                throw new Exception("Quantidade acima do limite permitido.");
            }

            $partes = explode('.', $quantidade);

            if (isset($partes[1]) && strlen($partes[1]) > 2) {
                throw new Exception("A quantidade aceita no máximo duas casas decimais.");
            }

            $stmt = $conexao->prepare(
                "SELECT id FROM produtos
                 WHERE id = ? AND ativo = 1"
            );
            $stmt->execute([$produto_id]);

            if (!$stmt->fetch()) {
                throw new Exception("Produto não encontrado ou inativo.");
            }

            $produtosValidados[] = [
                'id' => $produto_id,
                'quantidade' => (float)$quantidade
            ];

            $produtosInseridos[$produto_id] = true;
        }

        // Iniciar transação
        $conexao->beginTransaction();

        // Cadastrar frete
        $sql = "INSERT INTO fretes
                (
                    data_frete,
                    transportador_id,
                    endereco_origem_id,
                    endereco_destino_id,
                    observacao,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($sql);

        $stmt->execute([
            $data_frete,
            $transportador_id,
            $origem_id,
            $destino_id,
            $observacao,
            $status
        ]);

        $frete_id = $conexao->lastInsertId();

        // Vincular produtos ao frete
        $sqlProduto = "INSERT INTO frete_produtos
                       (frete_id, produto_id, quantidade)
                       VALUES (?, ?, ?)";

        $stmtProduto = $conexao->prepare($sqlProduto);

        foreach ($produtosValidados as $produto) {

            $stmtProduto->execute([
                $frete_id,
                $produto['id'],
                $produto['quantidade']
            ]);

        }

        // Confirmar operação
        $conexao->commit();

        header("Location: listar.php?sucesso=1");
        exit;

    } catch (Throwable $e) {

        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }

        $mensagem = "Erro ao cadastrar frete: " . $e->getMessage();
    }
}

// Buscar transportadores
$stmt = $conexao->query(
    "SELECT id, nome FROM transportadores
     WHERE ativo = 1 ORDER BY nome"
);
$transportadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar endereços
$stmt = $conexao->query(
    "SELECT id, descricao, cidade, estado FROM enderecos
     WHERE ativo = 1 ORDER BY descricao"
);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$stmt = $conexao->query(
    "SELECT id, codigo, descricao FROM produtos
     WHERE ativo = 1 ORDER BY descricao"
);
$listaProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($produtos) === 0) {
    $produtos = [""];
    $quantidades = [1];
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
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

    <?php if ($mensagem !== ""): ?>
        <p style="color:red;">
            <?= h($mensagem) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Data do Frete *</label>
        <input type="date" name="data_frete"
               value="<?= h($data_frete) ?>" required>

        <label>Transportador *</label>
        <select name="transportador_id" required>
            <option value="">Selecione o transportador</option>

            <?php foreach ($transportadores as $t): ?>
                <option value="<?= h($t['id']) ?>"
                    <?= (string)$transportador_id === (string)$t['id'] ? 'selected' : '' ?>>
                    <?= h($t['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Endereço de Origem *</label>
        <select name="endereco_origem_id" required>
            <option value="">Selecione a origem</option>

            <?php foreach ($enderecos as $e): ?>
                <option value="<?= h($e['id']) ?>"
                    <?= (string)$endereco_origem_id === (string)$e['id'] ? 'selected' : '' ?>>
                    <?= h($e['descricao'] . ' - ' . $e['cidade'] . '/' . $e['estado']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Endereço de Destino *</label>
        <select name="endereco_destino_id" required>
            <option value="">Selecione o destino</option>

            <?php foreach ($enderecos as $e): ?>
                <option value="<?= h($e['id']) ?>"
                    <?= (string)$endereco_destino_id === (string)$e['id'] ? 'selected' : '' ?>>
                    <?= h($e['descricao'] . ' - ' . $e['cidade'] . '/' . $e['estado']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <hr>

        <h3>Produtos do Frete</h3>
        <p>Selecione os produtos e informe suas quantidades.</p>

        <div id="lista-produtos">

            <?php foreach ($produtos as $indice => $produtoSelecionado): ?>

                <div class="produto-item">

                    <label>Produto *</label>

                    <select name="produtos[]" required>
                        <option value="">Selecione um produto</option>

                        <?php foreach ($listaProdutos as $p): ?>
                            <option value="<?= h($p['id']) ?>"
                                <?= (string)$produtoSelecionado === (string)$p['id'] ? 'selected' : '' ?>>
                                <?= h($p['codigo'] . ' - ' . $p['descricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Quantidade *</label>

                    <input type="number"
                           name="quantidades[]"
                           min="0.01"
                           step="0.01"
                           value="<?= h($quantidades[$indice] ?? 1) ?>"
                           required>

                    <button type="button" onclick="removerProduto(this)">
                        Remover produto
                    </button>

                    <hr>

                </div>

            <?php endforeach; ?>

        </div>

        <button type="button" id="adicionar-produto">
            + Adicionar outro produto
        </button>

        <label>Observação</label>
        <textarea name="observacao" rows="4"><?= h($observacao) ?></textarea>

        <label>Status</label>
        <select name="status" required>
            <?php
            $opcoes = [
                "PENDENTE" => "Pendente",
                "EM ANDAMENTO" => "Em andamento",
                "CONCLUIDO" => "Concluído",
                "CANCELADO" => "Cancelado"
            ];
            foreach ($opcoes as $valor => $descricao):
            ?>
                <option value="<?= h($valor) ?>"
                    <?= $status === $valor ? 'selected' : '' ?>>
                    <?= h($descricao) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br><br>

        <button type="submit">Cadastrar Frete</button>
        <a href="listar.php">Voltar</a>

    </form>

</div>

<script>

const listaProdutos = document.getElementById("lista-produtos");

document.getElementById("adicionar-produto")
.addEventListener("click", function () {

    const primeiro = listaProdutos.querySelector(".produto-item");
    const novo = primeiro.cloneNode(true);

    novo.querySelector("select").value = "";
    novo.querySelector("input").value = "1";

    listaProdutos.appendChild(novo);

});

function removerProduto(botao) {

    const itens = listaProdutos.querySelectorAll(".produto-item");

    if (itens.length > 1) {
        botao.closest(".produto-item").remove();
    } else {
        alert("O frete precisa ter pelo menos um produto.");
    }

}

</script>

</body>
</html>
