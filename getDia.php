<?php
// Establecer la zona horaria a Colombia
date_default_timezone_set('America/Bogota');

// Obtener el día de la semana en español
$dias = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
$diaActual = $dias[date("w")];

echo $diaActual; // Devuelve el día de la semana en español
?>
