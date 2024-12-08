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

// Obtener el mes seleccionado o el actual
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('m');

// Consulta para obtener el consumo diario
$sql_consumo = "SELECT DAY(timestamp) AS dia, COUNT(*) AS total_uso
                FROM relay_actions
                WHERE action = 'ON' AND MONTH(timestamp) = '$mes_seleccionado'
                GROUP BY DAY(timestamp)";

$result_consumo = $conn->query($sql_consumo);

$consumo_diario = [];
$dias = [];

if ($result_consumo->num_rows > 0) {
    while($row = $result_consumo->fetch_assoc()) {
        $consumo_diario[] = $row['total_uso'] * 45; // 45W por cada vez que el relé estuvo encendido
        $dias[] = $row['dia'];
    }
}

// Consulta para obtener el consumo de horas por día
$sql_horas = "SELECT DAY(timestamp) AS dia, SUM(TIMESTAMPDIFF(HOUR, timestamp, NOW())) AS horas_uso
              FROM relay_actions
              WHERE action = 'ON' AND MONTH(timestamp) = '$mes_seleccionado'
              GROUP BY DAY(timestamp)";

$result_horas = $conn->query($sql_horas);

$horas_uso = [];

if ($result_horas->num_rows > 0) {
    while($row = $result_horas->fetch_assoc()) {
        $horas_uso[] = $row['horas_uso'];
    }
}

// Nombre del mes
$meses_nombres = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];

$mes_nombre = $meses_nombres[$mes_seleccionado];

// Consulta para obtener los registros
$sql_registros = "SELECT action, DATE_FORMAT(timestamp, '%d-%m-%Y %H:%i:%s') AS fecha
                  FROM relay_actions
                  WHERE MONTH(timestamp) = '$mes_seleccionado'
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
    <title>Estadísticas - Control Relé</title>
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
                        <a class="nav-link" aria-current="page" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="estadisticas.php">Estadísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="predicciones.php">Predicciones</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center">Estadísticas del Consumo - <?php echo ucfirst($mes_nombre); ?></h1>

        <!-- Selector de mes -->
        <form method="get" class="mb-3">
            <select name="mes" class="form-select" onchange="this.form.submit()">
                <?php foreach ($meses_nombres as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($key == $mes_seleccionado) ? 'selected' : ''; ?>><?php echo ucfirst($value); ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Gráficas -->
        <div class="row">
            <div class="col-md-6">
                <h4>Consumo Diario (Watts)</h4>
                <div class="chart-container">
                    <canvas id="consumoChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <h4>Consumo de Horas por Día</h4>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Gráficas con Chart.js -->
    <script>
        // Gráfico de Consumo Diario
        var ctx1 = document.getElementById('consumoChart').getContext('2d');
        var consumoChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dias); ?>,
                datasets: [{
                    label: 'Consumo Diario (W)',
                    data: <?php echo json_encode($consumo_diario); ?>,
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
        var ctx2 = document.getElementById('horasChart').getContext('2d');
        var horasChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dias); ?>,
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
</body>
</html>
