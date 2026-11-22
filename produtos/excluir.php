
<?php

require_once "../config/conexao.php";

// Permitir somente requisições POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: listar.php");
    exit;
}

$sql = "UPDATE produtos
        SET ativo = 0
        WHERE id = :id
          AND ativo = 1";

$stmt = $conexao->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

if ($stmt->rowCount() > 0) {
    header("Location: listar.php?sucesso=exclusao");
} else {
    header("Location: listar.php");
}

exit;

?>
