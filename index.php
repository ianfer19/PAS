<?php
// Dirección IP del ESP8266
$esp8266_ip = "192.168.101.7"; // Reemplaza con la IP de tu ESP8266

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

// Variable para almacenar la respuesta del ESP8266
$response = "";

// Obtener el último estado guardado
$result = $conn->query("SELECT action, timestamp FROM relay_actions ORDER BY id DESC LIMIT 1");
$last_action = ($result->num_rows > 0) ? $result->fetch_assoc() : null;
$last_action_type = $last_action['action'] ?? "No disponible";
$last_action_time = $last_action['timestamp'] ?? "No disponible";

// Verificar si hay una acción solicitada
if (isset($_GET['action'])) {
    $action = $_GET['action']; // Puede ser 'ON' o 'OFF'

    // Comprobar si el último estado es diferente al nuevo
    if ($action != $last_action_type) {
        // Enviar la solicitud al ESP8266
        $url = "http://$esp8266_ip/$action";
        $response = file_get_contents($url);

        // Guardar la acción en la base de datos
        $stmt = $conn->prepare("INSERT INTO relay_actions (action) VALUES (?)");
        $stmt->bind_param("s", $action);
        $stmt->execute();
        $stmt->close();
    }
}

// Agregar horario
if (isset($_POST['add_schedule'])) {
    $hora_inicio = $_POST['hora_inicio'];
    $minuto_inicio = $_POST['minuto_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $minuto_fin = $_POST['minuto_fin'];
    $dia = $_POST['dia']; // Obtener el nombre del día

// Guardar el horario en la base de datos
$stmt = $conn->prepare("INSERT INTO horarios (hora_inicio, minuto_inicio, hora_fin, minuto_fin, dia) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiis", $hora_inicio, $minuto_inicio, $hora_fin, $minuto_fin, $dia);

    $stmt->execute();
    $stmt->close();
}

// Eliminar horario
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            justify-content: space-between;
        }
        footer {
            margin-top: auto;
        }
        .button-container {
            display: flex;
            flex-direction: column; /* Coloca los botones uno debajo del otro */
            justify-content: center;
            align-items: center;
            height: 60vh; /* Centra los botones verticalmente */
            gap: 20px; /* Añade espacio entre los botones */
        }
        .btn-lg {
            font-size: 1.5rem; /* Ajusta el tamaño de la fuente */
            padding: 20px 40px; /* Ajusta el tamaño de los botones */
            width: 200px; /* Establece un ancho fijo para los botones */
            height: 80px; /* Establece una altura fija para los botones */
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center; /* Asegura que el texto esté centrado dentro del botón */
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
                        <a class="nav-link active" aria-current="page" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="estadisticas.php">Estadísticas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="predicciones.php">Predicciones</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container mt-5">
        <h1 class="text-center">Control</h1>
        
        <!-- Contenedor de los botones -->
        <div class="button-container">
            <div>
                <button class="btn btn-success btn-lg" onclick="location.href='?action=ON'">Encender</button>
            </div>
            <div class="mt-3">
                <button class="btn btn-danger btn-lg" onclick="location.href='?action=OFF'">Apagar</button>
            </div>
        </div>

        <!-- Mostrar la respuesta del ESP8266 -->
        <?php if (!empty($response)): ?>
            <div class="alert alert-info mt-3" role="alert">
                <?php echo htmlspecialchars($response); ?>
            </div>
        <?php endif; ?>

        <!-- Mostrar el último estado y horario -->
        <div class="mt-4">
            <h4>Último estado:</h4>
            <p>Estado: <strong><?php echo $last_action_type; ?></strong></p>
            <p>Hora: <strong><?php echo $last_action_time; ?></strong></p>
        </div>

        <!-- Formulario para agregar un nuevo horario -->
        <div class="mt-5">
            <h4>Agregar Horario</h4>
            <form method="POST">
                <div class="mb-3">
                    <label for="hora_inicio" class="form-label">Hora de Inicio</label>
                    <input type="number" class="form-control" id="hora_inicio" name="hora_inicio" required>
                </div>
                <div class="mb-3">
                    <label for="minuto_inicio" class="form-label">Minuto de Inicio</label>
                    <input type="number" class="form-control" id="minuto_inicio" name="minuto_inicio" required>
                </div>
                <div class="mb-3">
                    <label for="hora_fin" class="form-label">Hora de Fin</label>
                    <input type="number" class="form-control" id="hora_fin" name="hora_fin" required>
                </div>
                <div class="mb-3">
                    <label for="minuto_fin" class="form-label">Minuto de Fin</label>
                    <input type="number" class="form-control" id="minuto_fin" name="minuto_fin" required>
                </div>
                <div class="mb-3">
                    <label for="dia" class="form-label">Día de la Semana</label>
                    <select class="form-control" id="dia" name="dia" required>
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miércoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                        <option value="sábado">Sábado</option>
                        <option value="domingo">Domingo</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="add_schedule">Agregar Horario</button>
            </form>
        </div>

        <!-- Mostrar los horarios programados -->
        <div class="mt-5">
            <h4>Horarios Programados</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Hora de Inicio</th>
                        <th>Hora de Fin</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM horarios";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['dia'] . "</td>";
                            echo "<td>" . $row['hora_inicio'] . ":" . $row['minuto_inicio'] . "</td>";
                            echo "<td>" . $row['hora_fin'] . ":" . $row['minuto_fin'] . "</td>";
                            echo "<td><a href='?delete_id=" . $row['id'] . "' class='btn btn-danger'>Eliminar</a></td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <p>© 2024 MiApp</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Cerrar la conexión después de todo
$conn->close();
?>
