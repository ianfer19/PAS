<?php
// Conexión a la base de datos
$servername = "localhost"; // Cambia esto si tu servidor no está en localhost
$username = "root";        // Reemplaza con tu usuario de base de datos
$password = "";            // Reemplaza con tu contraseña de base de datos
$dbname = "relay_control"; // Reemplaza con el nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consultar los horarios programados
$sql = "SELECT * FROM horarios";
$result = $conn->query($sql);

$horarios = [];

if ($result->num_rows > 0) {
    // Recorremos los resultados de la consulta
    while($row = $result->fetch_assoc()) {
        $horarios[] = [
            'dia' => $row['dia'],
            'hora_inicio' => $row['hora_inicio'],
            'minuto_inicio' => $row['minuto_inicio'],
            'hora_fin' => $row['hora_fin'],
            'minuto_fin' => $row['minuto_fin']
        ];
    }
}

// Enviar los horarios como JSON
header('Content-Type: application/json');
echo json_encode($horarios);

// Cerrar la conexión
$conn->close();
?>
