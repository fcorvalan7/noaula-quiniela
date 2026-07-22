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
