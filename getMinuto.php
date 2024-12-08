<?php
// Establecer la zona horaria a Colombia
date_default_timezone_set('America/Bogota');

// Obtener los minutos actuales
$minuto = date("i");

// Devolver solo los minutos
echo $minuto;
?>
