<?php
// Calculo de premio de una apuesta contra las extracciones de su sorteo.

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
