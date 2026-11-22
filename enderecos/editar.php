
<?php
require_once "../config/conexao.php";

$mensagem = "";

// Verificar ID recebido
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id && $_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id) {
    die("Endereço não informado.");
}

// Buscar endereço
$sql = "SELECT * FROM enderecos WHERE id = :id";
$stmt = $conexao->prepare($sql);
$stmt->execute([":id" => $id]);

$endereco = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$endereco) {
    die("Endereço não encontrado.");
}

// Atualizar endereço
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

            $sql = "UPDATE enderecos SET
                        descricao = :descricao,
                        cep = :cep,
                        logradouro = :logradouro,
                        numero = :numero,
                        complemento = :complemento,
                        bairro = :bairro,
                        cidade = :cidade,
                        estado = :estado
                    WHERE id = :id";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ":descricao" => $descricao,
                ":cep" => $cep,
                ":logradouro" => $logradouro,
                ":numero" => $numero,
                ":complemento" => $complemento,
                ":bairro" => $bairro,
                ":cidade" => $cidade,
                ":estado" => $estado,
                ":id" => $id
            ]);

            header("Location: listar.php?sucesso_edicao=1");
            exit;

        } catch (PDOException $e) {

            $mensagem = "Erro ao atualizar endereço: " . $e->getMessage();
        }
    }

    // Manter os dados digitados caso ocorra erro
    $endereco["descricao"] = $descricao;
    $endereco["cep"] = $cep;
    $endereco["logradouro"] = $logradouro;
    $endereco["numero"] = $numero;
    $endereco["complemento"] = $complemento;
    $endereco["bairro"] = $bairro;
    $endereco["cidade"] = $cidade;
    $endereco["estado"] = $estado;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Endereço</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="container">

    <h1>Editar Endereço</h1>

    <?php if ($mensagem !== ""): ?>

        <p style="color:red;">
            <?= htmlspecialchars($mensagem) ?>
        </p>

    <?php endif; ?>

    <form method="POST" action="">

        <input type="hidden" name="id"
               value="<?= htmlspecialchars((string)$id) ?>">

        <label>Descrição *</label>
        <input type="text" name="descricao"
               value="<?= htmlspecialchars($endereco["descricao"]) ?>"
               required>

        <label>CEP *</label>
        <input type="text" id="cep" name="cep"
               maxlength="9"
               value="<?= htmlspecialchars($endereco["cep"]) ?>"
               required>

        <small id="mensagemCep"></small>

        <label>Logradouro *</label>
        <input type="text" id="logradouro" name="logradouro"
               value="<?= htmlspecialchars($endereco["logradouro"]) ?>"
               required>

        <label>Número *</label>
        <input type="text" name="numero"
               value="<?= htmlspecialchars($endereco["numero"]) ?>"
               required>

        <label>Complemento</label>
        <input type="text" name="complemento"
               value="<?= htmlspecialchars($endereco["complemento"] ?? "") ?>">

        <label>Bairro *</label>
        <input type="text" id="bairro" name="bairro"
               value="<?= htmlspecialchars($endereco["bairro"]) ?>"
               required>

        <label>Cidade *</label>
        <input type="text" id="cidade" name="cidade"
               value="<?= htmlspecialchars($endereco["cidade"]) ?>"
               required>

        <label>Estado (UF) *</label>
        <input type="text" id="estado" name="estado"
               maxlength="2"
               value="<?= htmlspecialchars($endereco["estado"]) ?>"
               required>

        <br><br>

        <button type="submit">Salvar Alterações</button>

        <a href="listar.php">Cancelar</a>

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
            mensagem.textContent = "Não foi possível consultar o CEP.";
        });

});

</script>

</body>
</html>
