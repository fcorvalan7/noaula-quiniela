# Diseño — Proyecto Quiniela para Mantenimiento de Software I

**Fecha**: 2026-07-22
**Formador**: Federico Corvalán
**Materia**: Mantenimiento de Software I (NOAULA-C41-MTO-I, i2T Software Factory)

## 1. Propósito y contexto

Este proyecto no es un producto a completar: es **código ajeno construido a propósito** para que los cursantes de Mantenimiento de Software I practiquen los tres regímenes de mantenimiento (correctivo, evolutivo, preventivo) sobre un sistema real, chico y entendible, en vez de sobre su propio código.

Se distribuye vía repositorio git que cada cursante clona y levanta localmente con XAMPP. No tiene backend en producción ni usuarios reales — es una maqueta pedagógica con datos estáticos.

Referencia de programa: los encuentros E2/E3 (correctivo), E4/E5 (evolutivo) y E7/E8 (preventivo) del cronograma de la materia (ver `Mantenimiento de Software I/NOAULA-C41-MTO-I_Plan_de_Estudio_v1 (1).pdf`) son los consumidores directos de este repositorio.

## 2. Alcance

**Incluye (v1):**
- Carga de apuestas sobre un sorteo (modalidad "a la cabeza" o "al número")
- Carga de resultado (20 extracciones) de un sorteo por parte del administrador
- Listado de apuestas de un sorteo con premio calculado
- Historial simple de sorteos
- Un defecto correctivo, un pedido evolutivo pendiente y deuda técnica preventiva, plantados deliberadamente (sección 6)

**No incluye (fuera de alcance v1):**
- Login, roles o autenticación de ningún tipo
- Pagos reales o integración con lotería oficial
- Multi-modalidad más allá de "a la cabeza" / "al número" (sin redoblona ni combinadas)
- Frontend con framework (sin Angular, sin build tools)
- Tests automatizados (ausencia deliberada — es parte de la deuda técnica a diagnosticar en el eje preventivo)

**Supuestos:**
- Cada cursante corre el proyecto en su propia instalación local de XAMPP
- El repositorio se entrega con datos de ejemplo ya cargados (seed SQL), no arranca vacío

## 3. Arquitectura

PHP 8.x (versión que trae XAMPP por defecto hoy) procedural, sin framework ni Composer. Front en JavaScript vanilla con `fetch` contra endpoints PHP que devuelven JSON donde haga falta interactividad; el resto son formularios PHP tradicionales (post/redirect/get). Base de datos MySQL/MariaDB vía mysqli, con script `seed.sql` que crea el esquema y carga datos de ejemplo.

Se descartó Angular como front (exige Node/npm/Angular CLI, rompe el "solo XAMPP" que necesita el curso) y PHP 7.x explícito (exige instaladores XAMPP archivados y EOL sin ganancia pedagógica) — ver decisiones en sección 8.

Estructura de carpetas prevista:

```
quiniela/
  index.php
  apostar.php
  admin_resultado.php
  listado_apuestas.php
  historial.php
  /db/
    conexion.php
    seed.sql
  /lib/
    premio.php          (cálculo de premio — foco preventivo)
  /assets/
    js/
    css/
  /docs/
    superpowers/specs/   (este documento + plan de implementación)
    generacion-ia/       (prompts usados para generar el proyecto)
  CHECKLIST.md
  README.md             (instrucciones de instalación XAMPP)
```

## 4. Modelo de datos

Sin tabla de usuarios (no hay login).

| Tabla | Campos | Notas |
|---|---|---|
| `sorteos` | id, fecha, turno | turno: previa / matutina / vespertina / nocturna |
| `extracciones` | sorteo_id, posicion (1-20), numero | numero: 2 cifras "00" a "99", nulo hasta que el admin carga el resultado |
| `apuestas` | id, sorteo_id, nombre_apostador, numero_apostado, modalidad, monto, fecha | modalidad: 'cabeza' / 'numero'. nombre_apostador es texto libre, no referencia a usuario. numero_apostado: 2 cifras, "00" a "99" |

El premio **no se persiste**: se calcula al vuelo en `lib/premio.php` cada vez que se consulta un listado. Esto mantiene ese módulo como pieza central que hay que leer y mantener, en vez de un valor cacheado que nadie vuelve a tocar.

## 5. Reglas de negocio

- **A la cabeza**: el número apostado coincide con la extracción de posición 1 → premio = monto × multiplicador alto (ej. x70)
- **Al número**: el número apostado coincide con cualquiera de las 20 extracciones → premio = monto × multiplicador bajo (ej. x7)
- Simplificación deliberada frente a una quiniela real (donde el pago varía según la posición exacta de acierto): se documenta acá como decisión de diseño, no como omisión.

## 6. Inyección pedagógica

Estas tres decisiones son el corazón del proyecto — sin ellas, el repositorio es solo "una app de quiniela" y no cumple su función docente.

### 6.1 Correctivo (consumido en E2/E3)

`lib/premio.php` compara el número apostado contra las extracciones con una comparación de tipos que falla en un caso borde: números con cero a la izquierda ("05") guardados o comparados de forma inconsistente entre string e int, según el punto de entrada (formulario de apuesta vs carga de resultado). El defecto es silencioso — no lanza error, simplemente el premio da $0 para esos casos — y solo se reproduce con números del 00 al 09. Es un defecto de **datos/tipo**, no de lógica obvia: obliga a aplicar el protocolo de 5 pasos en vez de "arreglar lo primero que se ve".

### 6.2 Evolutivo (consumido en E4/E5)

`listado_apuestas.php` ya está en uso y tiene un **contrato implícito de orden**: las apuestas se listan ascendente por id, porque en el flujo real ese orden se cotejaría contra un talonario físico correlativo (documentado en el código como comentario de contexto, no como regla visible). El pedido evolutivo a resolver en el taller: agregar un filtro por modalidad (cabeza / número / todas) — mismo patrón que el ejemplo de "filtro por estado" del material de teoría del E4, para que a los cursantes les resuene el caso visto en clase. La trampa a evitar: que el filtro rompa el orden ascendente o el conteo total del encabezado.

### 6.3 Preventivo (consumido en E7/E8)

- `lib/premio.php` con complejidad ciclomática alta a propósito (ifs anidados sin extraer, sin tests)
- La misma lógica de cálculo de premio duplicada entre `apostar.php` (preview de premio potencial al cargar la apuesta) y `listado_apuestas.php` — candidato clásico de "módulo peligroso"
- Cero cobertura de tests en todo el repositorio
- Una librería JS incluida en `/assets/js/` desactualizada con CVE conocido documentado, para ejercitar deuda de dependencias
- Historial de commits con fechas escalonadas (no todo en un commit inicial) para que la métrica de "antigüedad de módulo" tenga sentido al auditar

## 7. Meta-documentación en el repositorio

- `docs/superpowers/specs/`: este documento de diseño + el plan de implementación derivado (writing-plans)
- `docs/generacion-ia/`: prompt(s) usados para generar el código base del proyecto, con nota de qué se generó con cada uno — funciona además como artefacto de referencia institucional en la lógica de CAT-PROMPT
- `CHECKLIST.md` en la raíz: flujo completo de construcción, que se actualiza a medida que avanza la implementación

## 8. Decisiones clave y por qué

| Decisión | Por qué |
|---|---|
| Sin login/roles | Es maqueta local de un solo desarrollador por vez; auth real sumaría una superficie de código ajena al objetivo del curso y podría convertirse en la fuente de bugs, distrayendo del oficio de mantenimiento |
| PHP procedural sin framework | Es el código legacy real que el equipo AMS mantiene en la práctica — acoplado, sin capas. Un MVC prolijo entrenaría menos el músculo de leer caos real |
| JS vanilla, no Angular | Angular exige Node/npm/Angular CLI; rompe el "se levanta solo con XAMPP" |
| PHP 8.x, no 7.x explícito | XAMPP actual trae 8.x por defecto; forzar 7.x exige instaladores archivados y EOL sin ganancia pedagógica real para código procedural simple |
| Premio calculado al vuelo, no persistido | Mantiene `lib/premio.php` como módulo vivo que hay que leer, en vez de un valor que se cachea y nadie vuelve a tocar |
| Modalidad como campo elegido al apostar (no por posición de acierto) | Simplificación deliberada del dominio real de quiniela, para no competir con el tiempo que los cursantes deben dedicar al oficio de mantenimiento, no al aprendizaje del negocio de quinielas |

## 9. Lo que no sabemos / queda abierto

- Multiplicadores exactos de premio (x70 / x7) son placeholder razonable — ajustables sin impacto en el diseño
- Nombre y CVE exacto de la librería JS desactualizada a incluir se define en la etapa de implementación
- Mensajes de commit con fechas escalonadas requieren decidir un rango de fechas ficticio consistente (a definir en el plan de implementación)

## 10. Próximo paso

Este spec pasa a plan de implementación (superpowers:writing-plans), con checklist ejecutable en `CHECKLIST.md` y reparto de tareas independientes entre agentes en paralelo donde no haya dependencia de orden (ej. seed SQL, README de instalación, JS vanilla base pueden ir en paralelo; los módulos que dependen del modelo de datos van después).
