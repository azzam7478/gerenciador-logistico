
<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../config/conexao.php";

$mensagem = "";

// Recuperar os dados enviados, inclusive quando houver erro
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

    if (
        empty($data_frete) ||
        empty($transportador_id) ||
        empty($endereco_origem_id) ||
        empty($endereco_destino_id) ||
        empty($produtos)
    ) {

        $mensagem = "Preencha os campos obrigatórios e selecione pelo menos um produto.";

    } else {

        try {

            // Validar IDs
            $transportador_id = filter_var(
                $transportador_id,
                FILTER_VALIDATE_INT
            );

            $origem_id = filter_var(
                $endereco_origem_id,
                FILTER_VALIDATE_INT
            );

            $destino_id = filter_var(
                $endereco_destino_id,
                FILTER_VALIDATE_INT
            );

            if (!$transportador_id || !$origem_id || !$destino_id) {
                throw new Exception("Transportador ou endereço inválido.");
            }

            // Origem e destino devem ser diferentes
            if ($origem_id === $destino_id) {
                throw new Exception("A origem e o destino devem ser diferentes.");
            }

            // Verificar transportador cadastrado e ativo
            $verifica = $conexao->prepare(
                "SELECT id FROM transportadores
                 WHERE id = :id AND ativo = 1"
            );

            $verifica->execute([
                ":id" => $transportador_id
            ]);

            if (!$verifica->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Transportador não encontrado ou inativo.");
            }

            // Verificar os dois endereços na tabela enderecos
            $verifica = $conexao->prepare(
                "SELECT id FROM enderecos
                 WHERE id IN (:origem, :destino)
                 AND ativo = 1"
            );

            $verifica->execute([
                ":origem" => $origem_id,
                ":destino" => $destino_id
            ]);

            $enderecosEncontrados = $verifica->fetchAll(PDO::FETCH_COLUMN);

            if (
                !in_array($origem_id, array_map('intval', $enderecosEncontrados), true)
            ) {
                throw new Exception(
                    "O endereço de origem não foi encontrado ou está inativo na tabela de endereços."
                );
            }

            if (
                !in_array($destino_id, array_map('intval', $enderecosEncontrados), true)
            ) {
                throw new Exception(
                    "O endereço de destino não foi encontrado ou está inativo na tabela de endereços."
                );
            }

            // Validar status
            $statusPermitidos = [
                "PENDENTE",
                "EM ANDAMENTO",
                "CONCLUIDO",
                "CANCELADO"
            ];

            if (!in_array($status, $statusPermitidos, true)) {
                throw new Exception("Status inválido.");
            }

            // Iniciar transação somente depois das validações
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
                    VALUES
                    (
                        :data_frete,
                        :transportador_id,
                        :origem,
                        :destino,
                        :observacao,
                        :status
                    )";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ":data_frete" => $data_frete,
                ":transportador_id" => $transportador_id,
                ":origem" => $origem_id,
                ":destino" => $destino_id,
                ":observacao" => $observacao,
                ":status" => $status
            ]);

            $frete_id = $conexao->lastInsertId();

            // Preparar cadastro dos produtos
            $sqlProduto = "INSERT INTO frete_produtos
                           (frete_id, produto_id, quantidade)
                           VALUES
                           (:frete_id, :produto_id, :quantidade)";

            $stmtProduto = $conexao->prepare($sqlProduto);

            $produtosInseridos = [];

            foreach ($produtos as $indice => $produto_id) {

                $produto_id = filter_var(
                    $produto_id,
                    FILTER_VALIDATE_INT
                );

                $quantidade = filter_var(
                    $quantidades[$indice] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (
                    !$produto_id ||
                    !$quantidade ||
                    $quantidade < 1
                ) {
                    throw new Exception(
                        "Produto ou quantidade inválida."
                    );
                }

                if (isset($produtosInseridos[$produto_id])) {
                    throw new Exception(
                        "Não selecione o mesmo produto duas vezes."
                    );
                }

                // Verificar se o produto existe e está ativo
                $verifica = $conexao->prepare(
                    "SELECT id FROM produtos
                     WHERE id = :id AND ativo = 1"
                );

                $verifica->execute([
                    ":id" => $produto_id
                ]);

                if (!$verifica->fetch(PDO::FETCH_ASSOC)) {
                    throw new Exception(
                        "Produto não encontrado ou inativo."
                    );
                }

                $stmtProduto->execute([
                    ":frete_id" => $frete_id,
                    ":produto_id" => $produto_id,
                    ":quantidade" => $quantidade
                ]);

                $produtosInseridos[$produto_id] = true;
            }

            // Confirmar cadastro
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
}

// Buscar transportadores ativos
$stmt = $conexao->query(
    "SELECT id, nome
     FROM transportadores
     WHERE ativo = 1
     ORDER BY nome"
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

// Buscar produtos ativos
$stmt = $conexao->query(
    "SELECT id, codigo, descricao
     FROM produtos
     WHERE ativo = 1
     ORDER BY descricao"
);

$listaProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Garantir pelo menos uma linha de produto
if (count($produtos) === 0) {
    $produtos = [""];
    $quantidades = [1];
}

// Função para proteger a exibição dos dados
function h($valor) {
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
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

    <?php if ($mensagem != ""): ?>
        <p style="color:red;">
            <?= h($mensagem) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Data do Frete *</label>

        <input
            type="date"
            name="data_frete"
            value="<?= h($data_frete) ?>"
            required
        >

        <label>Transportador *</label>

        <select name="transportador_id" required>
            <option value="">Selecione o transportador</option>

            <?php foreach ($transportadores as $transportador): ?>
                <option
                    value="<?= h($transportador["id"]) ?>"
                    <?= (string)$transportador_id === (string)$transportador["id"] ? "selected" : "" ?>
                >
                    <?= h($transportador["nome"]) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Endereço de Origem *</label>

        <select name="endereco_origem_id" required>
            <option value="">Selecione a origem</option>

            <?php foreach ($enderecos as $endereco): ?>
                <option
                    value="<?= h($endereco["id"]) ?>"
                    <?= (string)$endereco_origem_id === (string)$endereco["id"] ? "selected" : "" ?>
                >
                    <?= h(
                        $endereco["descricao"] . " - " .
                        $endereco["cidade"] . "/" .
                        $endereco["estado"]
                    ) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Endereço de Destino *</label>

        <select name="endereco_destino_id" required>
            <option value="">Selecione o destino</option>

            <?php foreach ($enderecos as $endereco): ?>
                <option
                    value="<?= h($endereco["id"]) ?>"
                    <?= (string)$endereco_destino_id === (string)$endereco["id"] ? "selected" : "" ?>
                >
                    <?= h(
                        $endereco["descricao"] . " - " .
                        $endereco["cidade"] . "/" .
                        $endereco["estado"]
                    ) ?>
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

                        <?php foreach ($listaProdutos as $produto): ?>

                            <option
                                value="<?= h($produto["id"]) ?>"
                                <?= (string)$produtoSelecionado === (string)$produto["id"] ? "selected" : "" ?>
                            >
                                <?= h(
                                    $produto["codigo"] . " - " .
                                    $produto["descricao"]
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <label>Quantidade *</label>

                    <input
                        type="number"
                        name="quantidades[]"
                        min="1"
                        value="<?= h($quantidades[$indice] ?? 1) ?>"
                        required
                    >

                    <button
                        type="button"
                        onclick="removerProduto(this)"
                    >
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

        <textarea
            name="observacao"
            rows="4"
            placeholder="Digite alguma observação sobre o frete"
        ><?= h($observacao) ?></textarea>

        <label>Status</label>

        <select name="status" required>

            <option value="PENDENTE" <?= $status === "PENDENTE" ? "selected" : "" ?>>
                Pendente
            </option>

            <option value="EM ANDAMENTO" <?= $status === "EM ANDAMENTO" ? "selected" : "" ?>>
                Em andamento
            </option>

            <option value="CONCLUIDO" <?= $status === "CONCLUIDO" ? "selected" : "" ?>>
                Concluído
            </option>

            <option value="CANCELADO" <?= $status === "CANCELADO" ? "selected" : "" ?>>
                Cancelado
            </option>

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
        novo.querySelector("input").value = 1;

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
