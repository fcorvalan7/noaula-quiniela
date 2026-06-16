<?php
require __DIR__ . '/db/conexion.php';

$sql = "
    SELECT s.id, s.fecha, s.turno,
           (SELECT COUNT(*) FROM extracciones e WHERE e.sorteo_id = s.id AND e.numero IS NOT NULL) AS extracciones_cargadas
    FROM sorteos s
    ORDER BY s.fecha DESC
";
$sorteos = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de sorteos - Quiniela</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <h1>Historial de sorteos</h1>
    <table border="1" cellpadding="4">
        <tr><th>ID</th><th>Fecha</th><th>Turno</th><th>Resultado</th><th></th></tr>
        <?php while ($s = $sorteos->fetch_assoc()): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['fecha']) ?></td>
                <td><?= htmlspecialchars($s['turno']) ?></td>
                <td><?= ((int)$s['extracciones_cargadas'] === 20) ? 'Cargado' : 'Pendiente' ?></td>
                <td><a href="listado_apuestas.php?sorteo_id=<?= $s['id'] ?>">Ver apuestas</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <p><a href="index.php">Volver</a></p>
</body>
</html>
