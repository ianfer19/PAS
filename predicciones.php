<?php
// Dirección IP del ESP8266
$esp8266_ip = "192.168.101.10"; // Reemplaza con la IP de tu ESP8266

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

// Consulta para obtener el consumo mensual
$sql_consumo = "SELECT MONTH(timestamp) AS mes, COUNT(*) AS total_uso
                FROM relay_actions
                WHERE action = 'ON'
                GROUP BY MONTH(timestamp)";

$result_consumo = $conn->query($sql_consumo);

$consumo_mensual = [];
$meses = [];

$meses_nombres = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];

if ($result_consumo->num_rows > 0) {
    while($row = $result_consumo->fetch_assoc()) {
        $consumo_mensual[] = $row['total_uso'] * 45; // 45W por cada vez que el relé estuvo encendido
        $meses[] = $meses_nombres[str_pad($row['mes'], 2, "0", STR_PAD_LEFT)];
    }
}

// Consulta para obtener el consumo de horas por mes
$sql_horas = "SELECT MONTH(timestamp) AS mes, SUM(TIMESTAMPDIFF(HOUR, timestamp, NOW())) AS horas_uso
              FROM relay_actions
              WHERE action = 'ON'
              GROUP BY MONTH(timestamp)";

$result_horas = $conn->query($sql_horas);

$horas_uso = [];

if ($result_horas->num_rows > 0) {
    while($row = $result_horas->fetch_assoc()) {
        $horas_uso[] = $row['horas_uso'];
    }
}

// Predicciones
$predicciones_consumo = [];
$predicciones_horas = [];

// Utilizamos una tendencia lineal simple para hacer la predicción
// Suponemos que la tendencia lineal será el promedio del consumo y horas de los meses anteriores.
$promedio_consumo = array_sum($consumo_mensual) / count($consumo_mensual);
$promedio_horas = array_sum($horas_uso) / count($horas_uso);

// Realizamos predicciones para los próximos 3 meses
for ($i = 1; $i <= 3; $i++) {
    $predicciones_consumo[] = round($promedio_consumo);
    $predicciones_horas[] = round($promedio_horas);
}

// Consulta para obtener el consumo mensual
$sql_consumo = "SELECT MONTH(timestamp) AS mes, COUNT(*) AS total_uso
                FROM relay_actions
                WHERE action = 'ON'
                GROUP BY MONTH(timestamp)";

$result_consumo = $conn->query($sql_consumo);

$consumo_mensual = [];
$meses = [];

$meses_nombres = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];

if ($result_consumo->num_rows > 0) {
    while($row = $result_consumo->fetch_assoc()) {
        $consumo_mensual[] = $row['total_uso'] * 45; // 45W por cada vez que el relé estuvo encendido
        $meses[] = $meses_nombres[str_pad($row['mes'], 2, "0", STR_PAD_LEFT)]; // Se asegura de obtener el nombre del mes
    }
}

// Consulta para obtener el consumo de horas por mes
$sql_horas = "SELECT MONTH(timestamp) AS mes, SUM(TIMESTAMPDIFF(HOUR, timestamp, NOW())) AS horas_uso
              FROM relay_actions
              WHERE action = 'ON'
              GROUP BY MONTH(timestamp)";

$result_horas = $conn->query($sql_horas);

$horas_uso = [];

if ($result_horas->num_rows > 0) {
    while($row = $result_horas->fetch_assoc()) {
        $horas_uso[] = $row['horas_uso'];
    }
}

// Consulta para obtener los registros
$sql_registros = "SELECT action, DATE_FORMAT(timestamp, '%d-%m-%Y %H:%i:%s') AS fecha
                  FROM relay_actions
                  ORDER BY timestamp DESC";

$result_registros = $conn->query($sql_registros);

// Función para formatear la fecha
function formatoFecha($fecha) {
    $meses = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
        '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
        '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
    ];
    $fecha_format = DateTime::createFromFormat('d-m-Y H:i:s', $fecha);
    $dia = $fecha_format->format('d');
    $mes = $meses[$fecha_format->format('m')];
    $año = $fecha_format->format('Y');
    $hora = $fecha_format->format('H:i:s');
    
    return "$dia de $mes de $año a las $hora";
}

// Cerrar la conexión a la base de datos
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predicciones - Control</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            max-width: 600px;
            margin: auto;
        }
        .table-container {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">MiApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="estadisticas.php">Estadísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="predicciones.php">Predicciones</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center">Predicciones del Consumo</h1>

        <!-- Gráficas -->
        <div class="row">
            <div class="col-md-6">
                <h4>Predicción de Consumo Mensual (Watts)</h4>
                <div class="chart-container">
                    <canvas id="prediccionesConsumoChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <h4>Predicción de Consumo de Horas</h4>
                <div class="chart-container">
                    <canvas id="prediccionesHorasChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas debajo de Predicciones -->
    <div class="container">


        <!-- Gráficas -->
        <div class="row">
            <div class="col-md-6">
                <h4>Consumo Mensual (Watts)</h4>
                <div class="chart-container">
                    <canvas id="consumoChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <h4>Consumo de Horas por Mes</h4>
                <div class="chart-container">
                    <canvas id="horasChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de Registros -->
        <div class="table-container">
            <h4>Registros de Encendido y Apagado</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Acción</th>
                        <th>Fecha y Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_registros->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo ($row['action'] == 'ON' ? 'Se encendió' : 'Se apagó'); ?></td>
                        <td><?php echo formatoFecha($row['fecha']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Gráficas con Chart.js -->
    <script>
        // Gráfico de Predicciones de Consumo Mensual
        var ctx1 = document.getElementById('prediccionesConsumoChart').getContext('2d');
        var prediccionesConsumoChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ['Mes Actual', 'Próximo Mes', 'Segundo Mes', 'Tercer Mes'],
                datasets: [{
                    label: 'Predicción de Consumo (W)',
                    data: <?php echo json_encode($predicciones_consumo); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Predicciones de Consumo de Horas
        var ctx2 = document.getElementById('prediccionesHorasChart').getContext('2d');
        var prediccionesHorasChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Mes Actual', 'Próximo Mes', 'Segundo Mes', 'Tercer Mes'],
                datasets: [{
                    label: 'Predicción de Horas de Consumo',
                    data: <?php echo json_encode($predicciones_horas); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Consumo Mensual
        var ctx3 = document.getElementById('consumoChart').getContext('2d');
        var consumoChart = new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [{
                    label: 'Consumo Mensual (W)',
                    data: <?php echo json_encode($consumo_mensual); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Consumo de Horas
        var ctx4 = document.getElementById('horasChart').getContext('2d');
        var horasChart = new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [{
                    label: 'Consumo de Horas',
                    data: <?php echo json_encode($horas_uso); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center text-lg-start">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Control del Relé</h5>
                    <p>
                        Este es un sistema de control para relés utilizando ESP8266 y PHP.
                    </p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Enlaces</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Inicio</a></li>
                        <li><a href="estadisticas.php" class="text-white">Estadísticas</a></li>
                        <li><a href="predicciones.php" class="text-white">Predicciones</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
            © 2024 Control Relé - Todos los derechos reservados
        </div>
    </footer>
</body>
</html>
