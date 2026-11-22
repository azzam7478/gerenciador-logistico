
<?php
require_once "../config/conexao.php";

$mensagem = "";

// Inativar ou reativar transportador
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $acao = $_POST["acao"] ?? "";

    if ($id && in_array($acao, ["inativar", "ativar"], true)) {

        $ativo = ($acao === "ativar") ? 1 : 0;

        $sql = "UPDATE transportadores
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
    $mensagem = "Transportador cadastrado com sucesso!";
}

if (isset($_GET["alterado"])) {
    $mensagem = "Status do transportador atualizado com sucesso!";
}

if (isset($_GET["sucesso_edicao"])) {
    $mensagem = "Transportador atualizado com sucesso!";
}

// Buscar transportadores
$sql = "SELECT * FROM transportadores ORDER BY id DESC";

$stmt = $conexao->query($sql);

$transportadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lista de Transportadores</title>

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
            flex-wrap: wrap;
        }

        .acoes form {
            margin: 0;
        }

        .botao {
            display: inline-block;
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

        .tabela-container {
            overflow-x: auto;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Lista de Transportadores</h1>

    <?php if ($mensagem !== ""): ?>

        <div class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>

    <a href="cadastrar.php" class="botao editar">
        + Cadastrar Transportador
    </a>

    <?php if (count($transportadores) > 0): ?>

        <div class="tabela-container">

            <table class="tabela">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Documento</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <th>Placa</th>
                        <th>Tipo de Veículo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($transportadores as $transportador): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars((string)$transportador["id"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($transportador["nome"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($transportador["documento"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($transportador["telefone"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($transportador["email"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($transportador["placa_veiculo"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($transportador["tipo_veiculo"] ?? "") ?>
                            </td>

                            <td>

                                <?php if ($transportador["ativo"] == 1): ?>

                                    <span class="ativo">Ativo</span>

                                <?php else: ?>

                                    <span class="inativo">Inativo</span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="acoes">

                                    <a
                                        class="botao editar"
                                        href="editar.php?id=<?= urlencode((string)$transportador["id"]) ?>"
                                    >
                                        Editar
                                    </a>

                                    <form method="POST" action="listar.php">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= htmlspecialchars((string)$transportador["id"]) ?>"
                                        >

                                        <?php if ($transportador["ativo"] == 1): ?>

                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="inativar"
                                            >

                                            <button
                                                type="submit"
                                                class="botao inativar"
                                                onclick="return confirm('Deseja realmente inativar este transportador?')"
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

        </div>

    <?php else: ?>

        <p>Nenhum transportador cadastrado.</p>

    <?php endif; ?>

    <br>

    <a href="../index.php">Voltar ao menu principal</a>

</div>

</body>
</html>
