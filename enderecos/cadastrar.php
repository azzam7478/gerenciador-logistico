
<?php
require_once "../config/conexao.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $descricao   = trim($_POST["descricao"] ?? "");
    $cep         = trim($_POST["cep"] ?? "");
    $logradouro  = trim($_POST["logradouro"] ?? "");
    $numero      = trim($_POST["numero"] ?? "");
    $complemento = trim($_POST["complemento"] ?? "");
    $bairro      = trim($_POST["bairro"] ?? "");
    $cidade      = trim($_POST["cidade"] ?? "");
    $estado      = strtoupper(trim($_POST["estado"] ?? ""));

    if (
        $descricao === "" ||
        $cep === "" ||
        $logradouro === "" ||
        $numero === "" ||
        $bairro === "" ||
        $cidade === "" ||
        $estado === ""
    ) {

        $mensagem = "Preencha todos os campos obrigatórios.";

    } elseif (!preg_match('/^[A-Z]{2}$/', $estado)) {

        $mensagem = "Informe uma sigla de estado válida.";

    } else {

        try {

            $sql = "INSERT INTO enderecos
                    (descricao, cep, logradouro, numero, complemento,
                     bairro, cidade, estado, ativo, data_cadastro)
                    VALUES
                    (:descricao, :cep, :logradouro, :numero, :complemento,
                     :bairro, :cidade, :estado, 1, NOW())";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ":descricao" => $descricao,
                ":cep" => $cep,
                ":logradouro" => $logradouro,
                ":numero" => $numero,
                ":complemento" => $complemento,
                ":bairro" => $bairro,
                ":cidade" => $cidade,
                ":estado" => $estado
            ]);

            header("Location: listar.php?sucesso=1");
            exit;

        } catch (PDOException $e) {

            $mensagem = "Erro ao cadastrar endereço: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Endereço</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h1>Cadastrar Endereço</h1>

    <?php if ($mensagem !== ""): ?>
        <p style="color:red;">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Descrição *</label>
        <input type="text" name="descricao"
            value="<?= htmlspecialchars($_POST['descricao'] ?? '') ?>"
            placeholder="Ex: Matriz, Filial, Depósito" required>

        <label>CEP *</label>
        <input type="text" id="cep" name="cep"
            value="<?= htmlspecialchars($_POST['cep'] ?? '') ?>"
            placeholder="00000-000" maxlength="9" required>

        <small id="mensagemCep"></small>

        <label>Logradouro *</label>
        <input type="text" id="logradouro" name="logradouro"
            value="<?= htmlspecialchars($_POST['logradouro'] ?? '') ?>"
            required>

        <label>Número *</label>
        <input type="text" name="numero"
            value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>"
            required>

        <label>Complemento</label>
        <input type="text" name="complemento"
            value="<?= htmlspecialchars($_POST['complemento'] ?? '') ?>">

        <label>Bairro *</label>
        <input type="text" id="bairro" name="bairro"
            value="<?= htmlspecialchars($_POST['bairro'] ?? '') ?>"
            required>

        <label>Cidade *</label>
        <input type="text" id="cidade" name="cidade"
            value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>"
            required>

        <label>Estado (UF) *</label>
        <input type="text" id="estado" name="estado"
            maxlength="2"
            value="<?= htmlspecialchars($_POST['estado'] ?? '') ?>"
            placeholder="PR" required>

        <br><br>

        <button type="submit">Cadastrar Endereço</button>

        <a href="listar.php">Voltar</a>

    </form>

</div>

<script>

document.getElementById("cep").addEventListener("blur", function () {

    let cep = this.value.replace(/\D/g, "");

    let mensagem = document.getElementById("mensagemCep");

    if (cep.length !== 8) {
        mensagem.textContent = "Digite um CEP válido com 8 números.";
        return;
    }

    mensagem.textContent = "Consultando CEP...";

    fetch("https://viacep.com.br/ws/" + cep + "/json/")
        .then(response => {
            if (!response.ok) {
                throw new Error("Erro na consulta.");
            }
            return response.json();
        })
        .then(dados => {

            if (dados.erro) {
                mensagem.textContent = "CEP não encontrado.";
                return;
            }

            document.getElementById("logradouro").value = dados.logradouro || "";
            document.getElementById("bairro").value = dados.bairro || "";
            document.getElementById("cidade").value = dados.localidade || "";
            document.getElementById("estado").value = dados.uf || "";

            mensagem.textContent = "Endereço localizado com sucesso!";

        })
        .catch(() => {
            mensagem.textContent = "Não foi possível consultar o CEP. Preencha manualmente.";
        });

});

</script>

</body>
</html>
