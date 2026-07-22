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
- [x] Meta-documentación de generación con IA (`docs/generacion-ia/`)
- [x] Verificación manual final de los tres ejes pedagógicos (correctivo/evolutivo/preventivo)

## Ejes pedagógicos plantados (referencia rápida para el formador)

| Eje | Dónde | Encuentro |
|---|---|---|
| Correctivo | `apostar.php` trunca cero a la izquierda; `lib/premio.php` compara estricto | E2/E3 |
| Evolutivo | `listado_apuestas.php` sin filtro por modalidad (pendiente) | E4/E5 |
| Preventivo | `lib/premio.php` complejo, magic numbers duplicados con `apostar.php`, `legacy-formato.js` desactualizado, cero tests | E7/E8 |
