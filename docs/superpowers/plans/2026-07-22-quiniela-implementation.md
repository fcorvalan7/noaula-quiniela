# Quiniela (Mantenimiento de Software I) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir el repositorio "quiniela" — código ajeno pedagógico en PHP + JS vanilla + MySQL — con un defecto correctivo, un pedido evolutivo pendiente y deuda técnica preventiva plantados a propósito, según `docs/superpowers/specs/2026-07-22-quiniela-design.md`.

**Architecture:** PHP 8.x procedural sin framework, páginas server-rendered con formularios post/redirect/get, mysqli directo, MySQL/MariaDB con esquema + seed estático. JS vanilla mínimo. Sin login.

**Tech Stack:** PHP 8.x, MySQL/MariaDB (mysqli), HTML/CSS, JavaScript vanilla, XAMPP.

## Global Constraints

- PHP 8.x procedural, sin framework ni Composer (spec §3, §8)
- Frontend JS vanilla, sin Angular ni build tools (spec §3, §8)
- Sin login/roles/autenticación en ninguna pantalla (spec §2, §8)
- **Sin tests automatizados en el repositorio entregado** — es deuda técnica deliberada (spec §6.3). Cada tarea de este plan reemplaza "test automatizado" por un paso de **verificación manual** con comando exacto y resultado esperado; esos comandos no se commitean como suite de tests.
- Acceso a datos vía mysqli, sin ORM
- `numero_apostado` y `numero` de extracción son strings de 2 cifras, `"00"` a `"99"` (spec §4)
- Multiplicadores de premio: cabeza ×70, número ×7 (spec §5)
- `listado_apuestas.php` ordena `ORDER BY id ASC` — contrato implícito, no se toca. No se agrega filtro por modalidad: es el pedido evolutivo reservado para el taller E4/E5 (spec §6.2)
- Estructura de carpetas exacta según spec §3
- El historial de commits usa fechas escalonadas por módulo (spec §6.3): cada tarea especifica `GIT_AUTHOR_DATE`/`GIT_COMMITTER_DATE` explícitos — nunca la fecha real del sistema

## Execution Notes — paralelización

Los commits llevan fechas escalonadas (Global Constraints) y viven en una sola rama: **se aplican en orden secuencial** (Task 1 → Task 12), no en paralelo, para no romper la coherencia del historial simulado.

Lo que sí puede redactarse en paralelo por agentes distintos, antes de aplicar los commits en orden: el contenido de Task 1 (SQL+conexión), Task 2 (JS legacy) y Task 3 (premio.php) no dependen entre sí — pueden escribirse simultáneamente. Igual con Task 7 (historial.php) y Task 8 (index+css), que no dependen una de la otra ni de Task 6. Un único coordinador aplica luego los `git add`/`commit` de cada tarea en el orden de fechas ya fijado.

---

### Task 1: Esquema de base de datos y conexión

**Files:**
- Create: `db/seed.sql`
- Create: `db/conexion.php`

**Interfaces:**
- Consumes: nada (primera tarea)
- Produces: base `quiniela` con tablas `sorteos(id, fecha, turno)`, `extracciones(sorteo_id, posicion, numero)`, `apuestas(id, sorteo_id, nombre_apostador, numero_apostado, modalidad, monto, fecha)`. Variable global `$mysqli` (objeto `mysqli`) disponible tras `require 'db/conexion.php'`.

- [ ] **Step 1: Crear `db/seed.sql`**

```sql
CREATE DATABASE IF NOT EXISTS quiniela CHARACTER SET utf8mb4;
USE quiniela;

CREATE TABLE sorteos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    turno ENUM('previa','matutina','vespertina','nocturna') NOT NULL
);

CREATE TABLE extracciones (
    sorteo_id INT NOT NULL,
    posicion INT NOT NULL,
    numero CHAR(2) DEFAULT NULL,
    PRIMARY KEY (sorteo_id, posicion),
    FOREIGN KEY (sorteo_id) REFERENCES sorteos(id)
);

CREATE TABLE apuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sorteo_id INT NOT NULL,
    nombre_apostador VARCHAR(100) NOT NULL,
    numero_apostado CHAR(2) NOT NULL,
    modalidad ENUM('cabeza','numero') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL,
    FOREIGN KEY (sorteo_id) REFERENCES sorteos(id)
);

INSERT INTO sorteos (id, fecha, turno) VALUES
    (1, '2026-07-20', 'vespertina'),
    (2, '2026-07-21', 'nocturna');

-- Sorteo 1: resultado ya cargado (20 extracciones)
INSERT INTO extracciones (sorteo_id, posicion, numero) VALUES
    (1,1,'47'),(1,2,'12'),(1,3,'83'),(1,4,'05'),(1,5,'99'),
    (1,6,'21'),(1,7,'34'),(1,8,'56'),(1,9,'78'),(1,10,'09'),
    (1,11,'60'),(1,12,'71'),(1,13,'82'),(1,14,'93'),(1,15,'14'),
    (1,16,'25'),(1,17,'36'),(1,18,'47'),(1,19,'58'),(1,20,'69');

-- Sorteo 2: sin resultado todavía (para probar admin_resultado.php)
INSERT INTO extracciones (sorteo_id, posicion, numero) VALUES
    (2,1,NULL),(2,2,NULL),(2,3,NULL),(2,4,NULL),(2,5,NULL),
    (2,6,NULL),(2,7,NULL),(2,8,NULL),(2,9,NULL),(2,10,NULL),
    (2,11,NULL),(2,12,NULL),(2,13,NULL),(2,14,NULL),(2,15,NULL),
    (2,16,NULL),(2,17,NULL),(2,18,NULL),(2,19,NULL),(2,20,NULL);

-- Apuestas de ejemplo sobre el sorteo 1
INSERT INTO apuestas (sorteo_id, nombre_apostador, numero_apostado, modalidad, monto, fecha) VALUES
    (1, 'Juan Perez', '47', 'cabeza', 100.00, '2026-07-20 10:15:00'),
    (1, 'Maria Gomez', '05', 'numero', 50.00, '2026-07-20 11:00:00'),
    (1, 'Carlos Ruiz', '99', 'numero', 200.00, '2026-07-20 12:30:00');
```

- [ ] **Step 2: Crear `db/conexion.php`**

```php
<?php
$mysqli = new mysqli('localhost', 'root', '', 'quiniela');
if ($mysqli->connect_errno) {
    die('Error de conexión: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
```

- [ ] **Step 3: Verificación manual**

Ejecutar `db/seed.sql` en phpMyAdmin o por CLI:

Run: `mysql -u root < db/seed.sql`
Expected: sin errores; luego `mysql -u root quiniela -e "SELECT COUNT(*) FROM apuestas;"` devuelve `3`.

Verificar conexión:

Run: `php -r "require 'db/conexion.php'; echo $mysqli->host_info;"`
Expected: imprime algo como `Localhost via UNIX socket` o `127.0.0.1 via TCP/IP` sin error de conexión.

- [ ] **Step 4: Commit**

```bash
git add db/seed.sql db/conexion.php
GIT_AUTHOR_DATE="2026-04-10T10:00:00-03:00" GIT_COMMITTER_DATE="2026-04-10T10:00:00-03:00" \
  git commit -m "feat: esquema inicial de base de datos y conexión"
```

---

### Task 2: Utilitario JS legacy con deuda de dependencia documentada

**Files:**
- Create: `assets/js/legacy-formato.js`

**Interfaces:**
- Consumes: nada
- Produces: funciones globales `formatearMoneda(valor)` y `formatearFecha(fechaIso)`, disponibles para cualquier página que incluya `<script src="assets/js/legacy-formato.js">`.

- [ ] **Step 1: Crear `assets/js/legacy-formato.js`**

```js
/*
 * legacy-formato.js
 * Utilitario de formato de fecha y moneda para las pantallas de quiniela.
 * Version: 0.9.2 (congelada desde 2026-04-10, sin actualizar desde entonces)
 * Nota de auditoria: version anterior a una correccion de seguridad conocida
 * en librerias de utilidades JS de la misma familia (equivalente a
 * CVE-2019-11358, prototype pollution en utilidades de merge de objetos).
 * Este archivo no hace merge de objetos externos, pero nunca se reviso
 * si la version seguia siendo necesaria o si convenia removerla.
 */
function formatearMoneda(valor) {
    return '$' + parseFloat(valor).toFixed(2);
}

function formatearFecha(fechaIso) {
    var partes = fechaIso.split('-');
    return partes[2] + '/' + partes[1] + '/' + partes[0];
}
```

- [ ] **Step 2: Verificación manual**

Run: `node -e "require('./assets/js/legacy-formato.js'); console.log(formatearMoneda('1234.5'), formatearFecha('2026-07-20'));"`
Expected: imprime `$1234.50 20/07/2026`

- [ ] **Step 3: Commit**

```bash
git add assets/js/legacy-formato.js
GIT_AUTHOR_DATE="2026-04-10T11:00:00-03:00" GIT_COMMITTER_DATE="2026-04-10T11:00:00-03:00" \
  git commit -m "feat: utilitario de formato de fecha y moneda para el front"
```

---

### Task 3: Módulo de cálculo de premio (con complejidad y defecto intencionales)

**Files:**
- Create: `lib/premio.php`

**Interfaces:**
- Consumes: array `$apuesta` con claves `numero_apostado` (string 2 chars), `modalidad` ('cabeza'|'numero'), `monto` (numeric); array `$extracciones` de filas `['posicion' => int, 'numero' => ?string]`
- Produces: función `calcularPremio(array $apuesta, array $extracciones): float`

- [ ] **Step 1: Crear `lib/premio.php`**

```php
<?php
// Calculo de premio de una apuesta contra las extracciones de su sorteo.
// NOTA: complejidad intencional, sin extraer funciones auxiliares - modulo
// candidato a intervencion preventiva (ver auditoria del curso, E7/E8).

function calcularPremio($apuesta, $extracciones) {
    $numero = $apuesta['numero_apostado'];
    $modalidad = $apuesta['modalidad'];
    $monto = (float)$apuesta['monto'];

    if ($modalidad === 'cabeza') {
        foreach ($extracciones as $ext) {
            if ((int)$ext['posicion'] === 1) {
                if ($ext['numero'] !== null) {
                    if ($numero === $ext['numero']) {
                        return $monto * 70;
                    } else {
                        return 0;
                    }
                } else {
                    return 0;
                }
            }
        }
        return 0;
    } else {
        if ($modalidad === 'numero') {
            foreach ($extracciones as $ext) {
                if ($ext['numero'] !== null) {
                    if ($numero === $ext['numero']) {
                        return $monto * 7;
                    }
                }
            }
            return 0;
        } else {
            return 0;
        }
    }
}
```

- [ ] **Step 2: Verificación manual — caso ganador normal**

Run:
```bash
php -r "
require 'lib/premio.php';
\$apuesta = ['numero_apostado' => '47', 'modalidad' => 'cabeza', 'monto' => 100];
\$extracciones = [['posicion' => 1, 'numero' => '47']];
echo calcularPremio(\$apuesta, \$extracciones);
"
```
Expected: `7000` (100 × 70)

- [ ] **Step 3: Verificación manual — reproducir el defecto correctivo**

Este caso reproduce el defecto que los cursantes van a diagnosticar en E2/E3: si `numero_apostado` llega sin el cero a la izquierda (`"5"` en vez de `"05"`), el premio da 0 aunque la extracción sea `"05"`.

Run:
```bash
php -r "
require 'lib/premio.php';
\$apuesta = ['numero_apostado' => '5', 'modalidad' => 'numero', 'monto' => 50];
\$extracciones = [['posicion' => 4, 'numero' => '05']];
echo calcularPremio(\$apuesta, \$extracciones);
"
```
Expected: `0` — confirma el defecto (comparación estricta `'5' !== '05'`). Esto es intencional: no corregir acá, es el defecto que resuelve el correctivo del curso.

- [ ] **Step 4: Commit**

```bash
git add lib/premio.php
GIT_AUTHOR_DATE="2026-04-12T15:30:00-03:00" GIT_COMMITTER_DATE="2026-04-12T15:30:00-03:00" \
  git commit -m "feat: calculo de premio por modalidad cabeza y numero"
```

---

### Task 4: Pantalla de carga de apuesta

**Files:**
- Create: `apostar.php`

**Interfaces:**
- Consumes: `$mysqli` de `db/conexion.php`; tabla `apuestas` (Task 1); `assets/js/legacy-formato.js` (Task 2)
- Produces: registro nuevo en `apuestas` vía POST. **Introduce el defecto correctivo**: castea `numero_apostado` a `(int)` antes de guardar, truncando ceros a la izquierda.

- [ ] **Step 1: Crear `apostar.php`**

```php
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
```

- [ ] **Step 2: Verificación manual**

Con Apache y MySQL corriendo (XAMPP) y `db/seed.sql` ya ejecutado:

Run: abrir `http://localhost/quiniela/apostar.php` en el navegador, completar el formulario con número `05`, modalidad `numero`, sorteo `2026-07-20`, y enviar.
Expected: mensaje "Apuesta registrada." Luego:

Run: `mysql -u root quiniela -e "SELECT numero_apostado FROM apuestas ORDER BY id DESC LIMIT 1;"`
Expected: devuelve `5` (un solo caracter, no `05`) — confirma que el defecto quedó wireado correctamente end-to-end.

- [ ] **Step 3: Commit**

```bash
git add apostar.php
GIT_AUTHOR_DATE="2026-05-05T09:15:00-03:00" GIT_COMMITTER_DATE="2026-05-05T09:15:00-03:00" \
  git commit -m "feat: pantalla de carga de apuesta"
```

---

### Task 5: Pantalla de carga de resultado (admin)

**Files:**
- Create: `admin_resultado.php`

**Interfaces:**
- Consumes: `$mysqli`; tabla `extracciones` (Task 1)
- Produces: actualiza `extracciones.numero` para las 20 posiciones de un sorteo. Conserva el formato de 2 cifras con `str_pad` (a diferencia de `apostar.php`).

- [ ] **Step 1: Crear `admin_resultado.php`**

```php
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
```

- [ ] **Step 2: Verificación manual**

Run: abrir `http://localhost/quiniela/admin_resultado.php`, elegir el sorteo `2026-07-21` (id 2, sin resultado), completar las 20 posiciones (ej. todas `01`..`20`) y enviar.
Expected: mensaje "Resultado cargado." Luego:

Run: `mysql -u root quiniela -e "SELECT numero FROM extracciones WHERE sorteo_id=2 AND posicion=1;"`
Expected: devuelve `01` (2 caracteres, con cero a la izquierda conservado).

- [ ] **Step 3: Commit**

```bash
git add admin_resultado.php
GIT_AUTHOR_DATE="2026-05-06T09:45:00-03:00" GIT_COMMITTER_DATE="2026-05-06T09:45:00-03:00" \
  git commit -m "feat: pantalla de carga de resultado de sorteo"
```

---

### Task 6: Listado de apuestas por sorteo

**Files:**
- Create: `listado_apuestas.php`

**Interfaces:**
- Consumes: `$mysqli`; `calcularPremio()` de `lib/premio.php` (Task 3); tablas `sorteos`, `extracciones`, `apuestas`
- Produces: página que lista apuestas de un sorteo con premio calculado, orden `id ASC` (contrato implícito, ver Global Constraints)

- [ ] **Step 1: Crear `listado_apuestas.php`**

```php
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
```

- [ ] **Step 2: Verificación manual**

Run: abrir `http://localhost/quiniela/listado_apuestas.php?sorteo_id=1`
Expected: tabla con 3 filas (Juan Perez, Maria Gomez, Carlos Ruiz) ordenadas por ID ascendente. La fila de Maria Gomez (número `05`, modalidad `numero`) debe mostrar premio `350.00` (50 × 7) porque esta apuesta fue insertada directo por seed (sin pasar por el defecto de `apostar.php`). Confirma que el módulo de cálculo funciona correctamente cuando el dato de entrada es correcto.

- [ ] **Step 3: Commit**

```bash
git add listado_apuestas.php
GIT_AUTHOR_DATE="2026-06-15T11:00:00-03:00" GIT_COMMITTER_DATE="2026-06-15T11:00:00-03:00" \
  git commit -m "feat: listado de apuestas por sorteo con premio calculado"
```

---

### Task 7: Historial de sorteos

**Files:**
- Create: `historial.php`

**Interfaces:**
- Consumes: `$mysqli`; tablas `sorteos`, `extracciones`
- Produces: página de listado de sorteos con estado de resultado (Cargado/Pendiente) y link a `listado_apuestas.php`

- [ ] **Step 1: Crear `historial.php`**

```php
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
```

- [ ] **Step 2: Verificación manual**

Run: abrir `http://localhost/quiniela/historial.php`
Expected: 2 filas — sorteo 1 (2026-07-20) con estado "Cargado", sorteo 2 (2026-07-21) con estado "Cargado" si ya se completó Task 5, o "Pendiente" si no.

- [ ] **Step 3: Commit**

```bash
git add historial.php
GIT_AUTHOR_DATE="2026-06-16T11:20:00-03:00" GIT_COMMITTER_DATE="2026-06-16T11:20:00-03:00" \
  git commit -m "feat: historial de sorteos"
```

---

### Task 8: Página de inicio y estilos

**Files:**
- Create: `index.php`
- Create: `assets/css/estilo.css`

**Interfaces:**
- Consumes: nada
- Produces: navegación a `apostar.php`, `admin_resultado.php`, `historial.php`; hoja de estilos compartida por todas las páginas

- [ ] **Step 1: Crear `assets/css/estilo.css`**

```css
body { font-family: Arial, sans-serif; margin: 2em; }
table { border-collapse: collapse; margin-top: 1em; }
th, td { padding: 4px 8px; }
.ok { color: green; font-weight: bold; }
```

- [ ] **Step 2: Crear `index.php`**

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Quiniela</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
    <h1>Quiniela</h1>
    <p>Proyecto de práctica para Mantenimiento de Software I.</p>
    <ul>
        <li><a href="apostar.php">Cargar apuesta</a></li>
        <li><a href="admin_resultado.php">Cargar resultado (admin)</a></li>
        <li><a href="historial.php">Historial de sorteos</a></li>
    </ul>
</body>
</html>
```

- [ ] **Step 3: Verificación manual**

Run: abrir `http://localhost/quiniela/`
Expected: página con título "Quiniela" y 3 links funcionando, estilos aplicados (tabla con bordes en las otras páginas).

- [ ] **Step 4: Commit**

```bash
git add index.php assets/css/estilo.css
GIT_AUTHOR_DATE="2026-06-16T11:40:00-03:00" GIT_COMMITTER_DATE="2026-06-16T11:40:00-03:00" \
  git commit -m "feat: pagina de inicio y estilos compartidos"
```

---

### Task 9: README de instalación

**Files:**
- Create: `README.md`

**Interfaces:**
- Consumes: nada
- Produces: instrucciones de instalación para los cursantes

- [ ] **Step 1: Crear `README.md`**

```markdown
# Quiniela — Proyecto de práctica

Proyecto de código ajeno para Mantenimiento de Software I (i2T). No es software en producción: está construido a propósito con un defecto, una funcionalidad evolutiva pendiente y deuda técnica documentada, para practicar el oficio de mantenimiento.

## Instalación local (XAMPP)

1. Instalá XAMPP (versión que trae PHP 8.x por defecto): https://www.apachefriends.org/
2. Copiá esta carpeta completa dentro de `htdocs` de tu instalación XAMPP (ej. `C:\xampp\htdocs\quiniela`)
3. Iniciá Apache y MySQL desde el panel de control de XAMPP
4. Abrí phpMyAdmin (`http://localhost/phpmyadmin`) y ejecutá el script `db/seed.sql` (pestaña SQL, pegar y ejecutar)
5. Abrí `http://localhost/quiniela/` en el navegador

## Estructura

- `index.php` — navegación principal
- `apostar.php` — carga de apuesta
- `admin_resultado.php` — carga de resultado de sorteo (20 extracciones)
- `listado_apuestas.php` — listado de apuestas de un sorteo con premio calculado
- `historial.php` — historial de sorteos
- `db/` — conexión y script de datos
- `lib/premio.php` — cálculo de premio
- `docs/` — documentación del curso y de cómo se generó este proyecto

## Uso durante la cursada

Este repositorio se trabaja en los encuentros E2 a E8 de la materia. No corrijas ni "mejores" nada antes de que el formador lo indique en el encuentro correspondiente — parte del ejercicio es diagnosticar antes de tocar.
```

- [ ] **Step 2: Verificación manual**

Run: `cat README.md | grep -c "##"`
Expected: `3` (tres encabezados de sección: Instalación, Estructura, Uso)

- [ ] **Step 3: Commit**

```bash
git add README.md
GIT_AUTHOR_DATE="2026-07-22T09:00:00-03:00" GIT_COMMITTER_DATE="2026-07-22T09:00:00-03:00" \
  git commit -m "docs: instrucciones de instalacion XAMPP"
```

---

### Task 10: Checklist de construcción

**Files:**
- Create: `CHECKLIST.md`

**Interfaces:**
- Consumes: lista de tareas de este plan (Tasks 1-9)
- Produces: checklist en la raíz del repo, visible para el formador durante la construcción

- [ ] **Step 1: Crear `CHECKLIST.md`**

```markdown
# Checklist de construcción — Quiniela

- [x] Esquema de base de datos y conexión (`db/seed.sql`, `db/conexion.php`)
- [x] Utilitario JS legacy con deuda de dependencia documentada (`assets/js/legacy-formato.js`)
- [x] Módulo de cálculo de premio, con complejidad y defecto correctivo intencionales (`lib/premio.php`)
- [x] Pantalla de carga de apuesta, con el punto de entrada del defecto (`apostar.php`)
- [x] Pantalla de carga de resultado admin (`admin_resultado.php`)
- [x] Listado de apuestas por sorteo, con contrato implícito de orden (`listado_apuestas.php`)
- [x] Historial de sorteos (`historial.php`)
- [x] Página de inicio y estilos (`index.php`, `assets/css/estilo.css`)
- [x] README de instalación XAMPP
- [x] Este checklist
- [ ] Meta-documentación de generación con IA (`docs/generacion-ia/`)
- [ ] Verificación manual final de los tres ejes pedagógicos (correctivo/evolutivo/preventivo)

## Ejes pedagógicos plantados (referencia rápida para el formador)

| Eje | Dónde | Encuentro |
|---|---|---|
| Correctivo | `apostar.php` trunca cero a la izquierda; `lib/premio.php` compara estricto | E2/E3 |
| Evolutivo | `listado_apuestas.php` sin filtro por modalidad (pendiente) | E4/E5 |
| Preventivo | `lib/premio.php` complejo, magic numbers duplicados con `apostar.php`, `legacy-formato.js` desactualizado, cero tests | E7/E8 |
```

- [ ] **Step 2: Commit**

```bash
git add CHECKLIST.md
GIT_AUTHOR_DATE="2026-07-22T09:15:00-03:00" GIT_COMMITTER_DATE="2026-07-22T09:15:00-03:00" \
  git commit -m "docs: checklist de construccion del proyecto"
```

---

### Task 11: Meta-documentación de generación con IA

**Files:**
- Create: `docs/generacion-ia/prompt-generacion.md`

**Interfaces:**
- Consumes: `docs/superpowers/specs/2026-07-22-quiniela-design.md`, este plan
- Produces: nota institucional sobre cómo se generó el proyecto, útil como referencia CAT-PROMPT

- [ ] **Step 1: Crear `docs/generacion-ia/prompt-generacion.md`**

```markdown
# Generación asistida por IA de este proyecto

Este repositorio fue generado con Claude Code, siguiendo el flujo `superpowers:brainstorming` → `superpowers:writing-plans` → ejecución tarea a tarea.

- Spec de diseño: `docs/superpowers/specs/2026-07-22-quiniela-design.md`
- Plan de implementación: `docs/superpowers/plans/2026-07-22-quiniela-implementation.md`

## Por qué importa para la materia

Este proyecto es en sí mismo un ejemplo de intervención asistida por IA: el
relevamiento (alcance, reglas de negocio, dónde plantar cada eje pedagógico)
se hizo en diálogo con el formador antes de escribir una línea de código,
siguiendo el mismo método de los cuatro momentos que se enseña en la
materia — diagnosticar el problema a resolver (dar a los cursantes código
ajeno realista), decidir el enfoque (PHP procedural simple, sin
frameworks), intervenir con un plan de tareas explícito, y asegurar con
documentación que sobrevive a quien lo generó.

## Uso sugerido en el catálogo institucional

Este documento y el spec asociado pueden servir de referencia para la
familia PM (Prompts de proyecto) o TPL (Templates) del catálogo
CAT-PROMPT-001, como ejemplo de relevamiento aplicado a la generación de un
proyecto completo en vez de una sola función.
```

- [ ] **Step 2: Verificación manual**

Run: `ls docs/generacion-ia/`
Expected: lista `prompt-generacion.md`

- [ ] **Step 3: Commit**

```bash
git add docs/generacion-ia/prompt-generacion.md
GIT_AUTHOR_DATE="2026-07-22T09:30:00-03:00" GIT_COMMITTER_DATE="2026-07-22T09:30:00-03:00" \
  git commit -m "docs: meta-documentacion de generacion asistida por IA"
```

---

### Task 12: Verificación manual final de los tres ejes pedagógicos

**Files:**
- Modify: `CHECKLIST.md` (tildar los dos últimos ítems)

**Interfaces:**
- Consumes: todo lo anterior (Tasks 1-11) corriendo en un XAMPP local
- Produces: confirmación de que los tres ejes funcionan como está documentado en el spec §6, antes de entregar el repo a los cursantes

- [ ] **Step 1: Verificar eje correctivo end-to-end**

Run: cargar una apuesta vía `apostar.php` con número `05` sobre el sorteo 1 (que ya tiene extracción `05` en posición 4, modalidad `numero`), luego abrir `listado_apuestas.php?sorteo_id=1`.
Expected: la fila de esa apuesta muestra premio `0.00` a pesar de que el número apostado coincide con una extracción real — confirma el defecto reproducible tal como lo describe el spec §6.1.

- [ ] **Step 2: Verificar eje evolutivo — contrato implícito presente y sin filtro**

Run: abrir `listado_apuestas.php?sorteo_id=1` y confirmar visualmente que las apuestas aparecen ordenadas por ID ascendente y que no hay ningún control de filtro por modalidad en la pantalla.
Expected: orden ascendente confirmado, sin filtro — el pedido evolutivo queda intacto para el taller E4/E5.

- [ ] **Step 3: Verificar eje preventivo — deuda técnica presente**

Run: `grep -rn "70\|x70" apostar.php lib/premio.php` y `grep -c "function test\|assert" -r .` (sin carpeta de tests)
Expected: el primer grep encuentra el multiplicador duplicado en ambos archivos; el segundo grep devuelve `0` — confirma ausencia de tests automatizados en todo el repo.

- [ ] **Step 4: Tildar los últimos dos ítems de `CHECKLIST.md`**

Editar `CHECKLIST.md`, cambiar:
```
- [ ] Meta-documentación de generación con IA (`docs/generacion-ia/`)
- [ ] Verificación manual final de los tres ejes pedagógicos (correctivo/evolutivo/preventivo)
```
por:
```
- [x] Meta-documentación de generación con IA (`docs/generacion-ia/`)
- [x] Verificación manual final de los tres ejes pedagógicos (correctivo/evolutivo/preventivo)
```

- [ ] **Step 5: Commit final**

```bash
git add CHECKLIST.md
GIT_AUTHOR_DATE="2026-07-22T10:00:00-03:00" GIT_COMMITTER_DATE="2026-07-22T10:00:00-03:00" \
  git commit -m "chore: cierre de checklist, proyecto listo para entregar a la cohorte"
```
