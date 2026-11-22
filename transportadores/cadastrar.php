
<?php
require_once "../config/conexao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome         = trim($_POST["nome"] ?? "");
    $documento    = trim($_POST["documento"] ?? "");
    $telefone     = trim($_POST["telefone"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $placa        = strtoupper(trim($_POST["placa_veiculo"] ?? ""));
    $tipoVeiculo  = trim($_POST["tipo_veiculo"] ?? "");

    if ($nome === "") {

        $mensagem = "Informe o nome do transportador.";

    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = "Informe um e-mail válido.";

    } else {

        try {

            $sql = "INSERT INTO transportadores
                    (nome, documento, telefone, email,
                     placa_veiculo, tipo_veiculo, ativo)
                    VALUES
                    (:nome, :documento, :telefone, :email,
                     :placa_veiculo, :tipo_veiculo, 1)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ":nome" => $nome,
                ":documento" => $documento,
                ":telefone" => $telefone,
                ":email" => $email,
                ":placa_veiculo" => $placa,
                ":tipo_veiculo" => $tipoVeiculo
            ]);

            header("Location: listar.php?sucesso=1");
            exit;

        } catch (PDOException $e) {

            $mensagem = "Erro ao cadastrar transportador: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Transportador</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .campo {
            margin-bottom: 15px;
        }

        .campo label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .campo input,
        .campo select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        .mensagem {
            color: red;
            margin-bottom: 15px;
        }
    </style>

</head>

<body>

<div class="container">

    <h1>Cadastrar Transportador</h1>

    <?php if ($mensagem !== ""): ?>

        <div class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="">

        <div class="campo">
            <label>Nome do Transportador *</label>

            <input type="text"
                   name="nome"
                   maxlength="120"
                   value="<?= htmlspecialchars($_POST["nome"] ?? "") ?>"
                   required>
        </div>

        <div class="campo">
            <label>CPF / CNPJ</label>

            <input type="text"
                   name="documento"
                   maxlength="20"
                   value="<?= htmlspecialchars($_POST["documento"] ?? "") ?>">
        </div>

        <div class="campo">
            <label>Telefone</label>

            <input type="text"
                   name="telefone"
                   maxlength="20"
                   value="<?= htmlspecialchars($_POST["telefone"] ?? "") ?>">
        </div>

        <div class="campo">
            <label>E-mail</label>

            <input type="email"
                   name="email"
                   maxlength="120"
                   value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
        </div>

        <div class="campo">
            <label>Placa do Veículo</label>

            <input type="text"
                   name="placa_veiculo"
                   maxlength="10"
                   placeholder="ABC1D23"
                   value="<?= htmlspecialchars($_POST["placa_veiculo"] ?? "") ?>">
        </div>

        <div class="campo">
            <label>Tipo de Veículo</label>

            <select name="tipo_veiculo">

                <option value="">Selecione</option>

                <?php
                $tipos = [
                    "Carreta",
                    "Caminhão",
                    "Truck",
                    "Toco",
                    "Van",
                    "Utilitário",
                    "Outro"
                ];
                ?>

                <?php foreach ($tipos as $tipo): ?>

                    <option value="<?= htmlspecialchars($tipo) ?>"
                        <?= (($_POST["tipo_veiculo"] ?? "") === $tipo) ? "selected" : "" ?>>
                        <?= htmlspecialchars($tipo) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <br>

        <button type="submit">
            Cadastrar Transportador
        </button>

        <a href="listar.php">
            Voltar
        </a>

    </form>

</div>

</body>
</html>
