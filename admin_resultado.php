<?php
require __DIR__ . '/db/conexion.php';

$sorteos = $mysqli->query("SELECT id, fecha, turno FROM sorteos ORDER BY fecha DESC");

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sorteo_id = (int)$_POST['sorteo_id'];
    for ($pos = 1; $pos <= 20; $pos++) {
        $numero = str_pad(trim($_POST['numero_' . $pos]), 2, '0', STR_PAD_LEFT);
        $stmt = $mysqli->prepare("UPDATE extracciones SET numero = ? WHERE sorteo_id = ? AND posicion = ?");
        $stmt->bind_param('sii', $numero, $sorteo_id, $pos);
        $stmt->execute();
        $stmt->close();
    }
    $mensaje = 'Resultado cargado.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar resultado - Quiniela</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <h1>Cargar resultado de sorteo</h1>
    <?php if ($mensaje): ?><p class="ok"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <form method="post">
        <label>Sorteo:
            <select name="sorteo_id" required>
                <?php while ($s = $sorteos->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['fecha']) ?> - <?= htmlspecialchars($s['turno']) ?></option>
                <?php endwhile; ?>
            </select>
        </label><br>
        <?php for ($pos = 1; $pos <= 20; $pos++): ?>
            <label>Posición <?= $pos ?>: <input type="text" name="numero_<?= $pos ?>" maxlength="2" pattern="[0-9]{2}" required></label><br>
        <?php endfor; ?>
        <button type="submit">Guardar resultado</button>
    </form>
    <p><a href="index.php">Volver</a></p>
</body>
</html>
