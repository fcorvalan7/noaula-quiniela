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
