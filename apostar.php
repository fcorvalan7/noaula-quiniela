<?php
require __DIR__ . '/db/conexion.php';

$sorteos = $mysqli->query("SELECT id, fecha, turno FROM sorteos ORDER BY fecha DESC");

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sorteo_id = (int)$_POST['sorteo_id'];
    $nombre_apostador = trim($_POST['nombre_apostador']);
    // NOTA: cast a int trunca el cero a la izquierda (ej. "05" -> 5).
    // Este es el punto de entrada del defecto correctivo del curso (E2/E3):
    // admin_resultado.php sí conserva el formato de 2 cifras; esta pantalla no.
    $numero_apostado = (int)$_POST['numero_apostado'];
    $modalidad = $_POST['modalidad'];
    $monto = (float)$_POST['monto'];

    $stmt = $mysqli->prepare("INSERT INTO apuestas (sorteo_id, nombre_apostador, numero_apostado, modalidad, monto, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('isssd', $sorteo_id, $nombre_apostador, $numero_apostado, $modalidad, $monto);
    $stmt->execute();
    $stmt->close();

    $mensaje = 'Apuesta registrada.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar apuesta - Quiniela</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <h1>Cargar apuesta</h1>
    <?php if ($mensaje): ?><p class="ok"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <form method="post">
        <label>Sorteo:
            <select name="sorteo_id" required>
                <?php while ($s = $sorteos->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['fecha']) ?> - <?= htmlspecialchars($s['turno']) ?></option>
                <?php endwhile; ?>
            </select>
        </label><br>
        <label>Nombre: <input type="text" name="nombre_apostador" required></label><br>
        <label>Número (2 cifras): <input type="text" name="numero_apostado" maxlength="2" pattern="[0-9]{2}" required></label><br>
        <label>Modalidad:
            <select name="modalidad" required>
                <option value="cabeza">A la cabeza (premio x70)</option>
                <option value="numero">Al número (premio x7)</option>
            </select>
        </label><br>
        <label>Monto: <input type="number" name="monto" step="0.01" min="1" required></label><br>
        <button type="submit">Apostar</button>
    </form>
    <p><a href="index.php">Volver</a></p>
    <script src="assets/js/legacy-formato.js"></script>
</body>
</html>
