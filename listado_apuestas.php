<?php
require __DIR__ . '/db/conexion.php';
require __DIR__ . '/lib/premio.php';

$sorteo_id = (int)($_GET['sorteo_id'] ?? 0);

$sorteo = null;
if ($sorteo_id) {
    $stmt = $mysqli->prepare("SELECT id, fecha, turno FROM sorteos WHERE id = ?");
    $stmt->bind_param('i', $sorteo_id);
    $stmt->execute();
    $sorteo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$extracciones = [];
if ($sorteo_id) {
    $stmt = $mysqli->prepare("SELECT posicion, numero FROM extracciones WHERE sorteo_id = ? ORDER BY posicion");
    $stmt->bind_param('i', $sorteo_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $extracciones[] = $row;
    }
    $stmt->close();
}

$apuestas = [];
if ($sorteo_id) {
    // Orden ascendente por id: el area operativa coteja este listado contra
    // el talonario fisico correlativo, que se numera en el mismo orden.
    // No cambiar el orden sin relevar ese contrato con el referente funcional.
    $stmt = $mysqli->prepare("SELECT id, nombre_apostador, numero_apostado, modalidad, monto FROM apuestas WHERE sorteo_id = ? ORDER BY id ASC");
    $stmt->bind_param('i', $sorteo_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['premio'] = calcularPremio($row, $extracciones);
        $apuestas[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de apuestas - Quiniela</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <h1>Listado de apuestas</h1>
    <form method="get">
        <label>Sorteo (ID): <input type="number" name="sorteo_id" value="<?= htmlspecialchars($sorteo_id) ?>" required></label>
        <button type="submit">Ver</button>
    </form>
    <?php if ($sorteo): ?>
        <h2><?= htmlspecialchars($sorteo['fecha']) ?> - <?= htmlspecialchars($sorteo['turno']) ?></h2>
        <table border="1" cellpadding="4">
            <tr><th>ID</th><th>Apostador</th><th>Número</th><th>Modalidad</th><th>Monto</th><th>Premio</th></tr>
            <?php foreach ($apuestas as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['nombre_apostador']) ?></td>
                    <td><?= htmlspecialchars($a['numero_apostado']) ?></td>
                    <td><?= htmlspecialchars($a['modalidad']) ?></td>
                    <td><?= number_format($a['monto'], 2) ?></td>
                    <td><?= number_format($a['premio'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php elseif ($sorteo_id): ?>
        <p>No se encontró el sorteo.</p>
    <?php endif; ?>
    <p><a href="index.php">Volver</a></p>
</body>
</html>
