
<?php
require_once "../config/conexao.php";

$mensagem = "";

// Exclusão lógica ou reativação do endereço
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $acao = $_POST["acao"] ?? "";

    if ($id && in_array($acao, ["inativar", "ativar"], true)) {

        $ativo = ($acao === "ativar") ? 1 : 0;

        $sql = "UPDATE enderecos
                SET ativo = :ativo
                WHERE id = :id";

        $stmt = $conexao->prepare($sql);

        $stmt->execute([
            ":ativo" => $ativo,
            ":id" => $id
        ]);

        header("Location: listar.php?alterado=1");
        exit;
    }
}

// Mensagens
if (isset($_GET["sucesso"])) {
    $mensagem = "Endereço cadastrado com sucesso!";
}

if (isset($_GET["alterado"])) {
    $mensagem = "Status do endereço atualizado com sucesso!";
}

// Buscar endereços
$sql = "SELECT * FROM enderecos ORDER BY id DESC";

$stmt = $conexao->query($sql);

$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lista de Endereços</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        .tabela {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tabela th,
        .tabela td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .tabela th {
            background-color: #eeeeee;
        }

        .ativo {
            color: green;
            font-weight: bold;
        }

        .inativo {
            color: red;
            font-weight: bold;
        }

        .mensagem {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            margin-bottom: 15px;
        }

        .acoes {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .acoes form {
            margin: 0;
        }

        .botao {
            padding: 7px 12px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            border-radius: 4px;
        }

        .editar {
            background-color: #007bff;
            color: white;
        }

        .inativar {
            background-color: #dc3545;
            color: white;
        }

        .ativar {
            background-color: #28a745;
            color: white;
        }

    </style>

</head>

<body>

    <div class="container">

        <h1>Lista de Endereços</h1>

        <?php if ($mensagem !== ""): ?>

            <div class="mensagem">
                <?= htmlspecialchars($mensagem) ?>
            </div>

        <?php endif; ?>

        <a href="cadastrar.php" class="botao editar">
            + Cadastrar Endereço
        </a>

        <?php if (count($enderecos) > 0): ?>

            <table class="tabela">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>CEP</th>
                        <th>Logradouro</th>
                        <th>Número</th>
                        <th>Bairro</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($enderecos as $endereco): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($endereco["id"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["descricao"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["cep"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["logradouro"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["numero"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["bairro"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["cidade"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($endereco["estado"]) ?>
                            </td>

                            <td>

                                <?php if ($endereco["ativo"] == 1): ?>

                                    <span class="ativo">Ativo</span>

                                <?php else: ?>

                                    <span class="inativo">Inativo</span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="acoes">

                                    <a class="botao editar"
                                       href="editar.php?id=<?= urlencode($endereco["id"]) ?>">
                                        Editar
                                    </a>

                                    <form method="POST" action="listar.php">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= htmlspecialchars($endereco["id"]) ?>"
                                        >

                                        <?php if ($endereco["ativo"] == 1): ?>

                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="inativar"
                                            >

                                            <button
                                                type="submit"
                                                class="botao inativar"
                                                onclick="return confirm('Deseja realmente desativar este endereço?')"
                                            >
                                                Excluir
                                            </button>

                                        <?php else: ?>

                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="ativar"
                                            >

                                            <button
                                                type="submit"
                                                class="botao ativar"
                                            >
                                                Reativar
                                            </button>

                                        <?php endif; ?>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>Nenhum endereço cadastrado.</p>

        <?php endif; ?>

        <br>

        <a href="../index.php">Voltar ao menu principal</a>

    </div>

</body>

</html>
