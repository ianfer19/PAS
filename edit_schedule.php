<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "relay_control";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del horario
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM horarios WHERE id = $id");

    if ($result->num_rows > 0) {
        $horario = $result->fetch_assoc();
    } else {
        die("Horario no encontrado.");
    }
} else {
    die("ID de horario no proporcionado.");
}

// Guardar los cambios en el horario
if (isset($_POST['update_schedule'])) {
    $hora_inicio = $_POST['hora_inicio'];
    $minuto_inicio = $_POST['minuto_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $minuto_fin = $_POST['minuto_fin'];
    $dia = $_POST['dia'];

    // Actualizar el horario en la base de datos
    $stmt = $conn->prepare("UPDATE horarios SET hora_inicio = ?, minuto_inicio = ?, hora_fin = ?, minuto_fin = ?, dia = ? WHERE id = ?");
    $stmt->bind_param("iiiiii", $hora_inicio, $minuto_inicio, $hora_fin, $minuto_fin, $dia, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php"); // Redirigir a la página principal después de actualizar
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Horario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Editar Horario</h1>
        
        <form method="POST">
            <div class="mb-3">
                <label for="hora_inicio" class="form-label">Hora de Inicio</label>
                <input type="number" class="form-control" id="hora_inicio" name="hora_inicio" value="<?php echo $horario['hora_inicio']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="minuto_inicio" class="form-label">Minuto de Inicio</label>
                <input type="number" class="form-control" id="minuto_inicio" name="minuto_inicio" value="<?php echo $horario['minuto_inicio']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="hora_fin" class="form-label">Hora de Fin</label>
                <input type="number" class="form-control" id="hora_fin" name="hora_fin" value="<?php echo $horario['hora_fin']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="minuto_fin" class="form-label">Minuto de Fin</label>
                <input type="number" class="form-control" id="minuto_fin" name="minuto_fin" value="<?php echo $horario['minuto_fin']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="dia" class="form-label">Día</label>
                <select class="form-control" id="dia" name="dia" required>
                    <option value="lunes" <?php echo $horario['dia'] == 'lunes' ? 'selected' : ''; ?>>Lunes</option>
                    <option value="martes" <?php echo $horario['dia'] == 'martes' ? 'selected' : ''; ?>>Martes</option>
                    <option value="miércoles" <?php echo $horario['dia'] == 'miércoles' ? 'selected' : ''; ?>>Miércoles</option>
                    <option value="jueves" <?php echo $horario['dia'] == 'jueves' ? 'selected' : ''; ?>>Jueves</option>
                    <option value="viernes" <?php echo $horario['dia'] == 'viernes' ? 'selected' : ''; ?>>Viernes</option>
                    <option value="sábado" <?php echo $horario['dia'] == 'sábado' ? 'selected' : ''; ?>>Sábado</option>
                    <option value="domingo" <?php echo $horario['dia'] == 'domingo' ? 'selected' : ''; ?>>Domingo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" name="update_schedule">Actualizar Horario</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Cerrar la conexión
$conn->close();
?>
