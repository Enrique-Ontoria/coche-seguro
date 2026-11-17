<?php
session_start();
require_once 'db.php';

// Si no es admin, fuera al login
if (empty($_SESSION['es_admin'])) {
    header("Location: admin.php");
    exit;
}

$modelo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$modelo_id) {
    header("Location: admin.php");
    exit;
}

$mensaje = '';

// Procesar actualizaciones y borrados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // 1. Actualizar datos principales del modelo
    if ($accion === 'actualizar_modelo') {
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $generacion = trim($_POST['generacion']);
        $anio_inicio = (int)$_POST['anio_inicio'];
        $anio_fin = (int)$_POST['anio_fin'];
        $puntuacion = (float)$_POST['puntuacion_general'];
        $uso_ideal = $_POST['uso_ideal'];
        $veredicto = trim($_POST['resumen_veredicto']);
        $ruta_imagen = $_POST['imagen_actual'];

        // Si sube una foto nueva, reemplazamos la ruta
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $nombre_limpio = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', "$marca-$modelo-$generacion")) . '-' . time() . '.' . $ext;
                $destino = 'assets/img/' . $nombre_limpio;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $ruta_imagen = $destino;
                }
            }
        }

        $precio_orientativo = (int)($_POST['precio_orientativo'] ?? 5000);
        $ranking_ventas = (isset($_POST['ranking_ventas']) && $_POST['ranking_ventas'] !== '')
            ? (int)$_POST['ranking_ventas']
            : null;

        $stmt = $pdo->prepare("UPDATE modelos SET marca = ?, modelo = ?, generacion = ?, anio_inicio = ?, anio_fin = ?, puntuacion_general = ?, resumen_veredicto = ?, imagen_url = ?, uso_ideal = ?, precio_orientativo = ?, ranking_ventas = ? WHERE id = ?");
        $stmt->execute([$marca, $modelo, $generacion, $anio_inicio, $anio_fin, $puntuacion, $veredicto, $ruta_imagen, $uso_ideal, $precio_orientativo, $ranking_ventas, $modelo_id]);
        $mensaje = "Datos del vehículo actualizados.";
    }

    // 2. Actualizar un motor
    if ($accion === 'actualizar_motor') {
        $stmt = $pdo->prepare("UPDATE motores SET nombre = ?, combustible = ?, semaforo = ?, opinion = ? WHERE id = ? AND modelo_id = ?");
        $stmt->execute([
            trim($_POST['nombre']),
            $_POST['combustible'],
            $_POST['semaforo'],
            trim($_POST['opinion']),
            (int)$_POST['motor_id'],
            $modelo_id
        ]);
        $mensaje = "Motor actualizado.";
    }

    // 3. Eliminar un motor
    if ($accion === 'eliminar_motor') {
        $stmt = $pdo->prepare("DELETE FROM motores WHERE id = ? AND modelo_id = ?");
        $stmt->execute([(int)$_POST['motor_id'], $modelo_id]);
        $mensaje = "Motor eliminado.";
    }

    // 4. Actualizar un punto de checklist
    if ($accion === 'actualizar_punto') {
        $stmt = $pdo->prepare("UPDATE checklist_puntos SET seccion = ?, descripcion = ?, que_significa_fallo = ?, gravedad = ? WHERE id = ? AND modelo_id = ?");
        $stmt->execute([
            $_POST['seccion'],
            trim($_POST['descripcion']),
            trim($_POST['que_significa_fallo']),
            $_POST['gravedad'],
            (int)$_POST['punto_id'],
            $modelo_id
        ]);
        $mensaje = "Punto de revisión actualizado.";
    }

    // 5. Eliminar un punto de checklist
    if ($accion === 'eliminar_punto') {
        $stmt = $pdo->prepare("DELETE FROM checklist_puntos WHERE id = ? AND modelo_id = ?");
        $stmt->execute([(int)$_POST['punto_id'], $modelo_id]);
        $mensaje = "Punto de revisión eliminado.";
    }

    // 6. Añadir un nuevo motor directamente a este modelo
    if ($accion === 'crear_motor') {
        $stmt = $pdo->prepare("INSERT INTO motores (modelo_id, nombre, combustible, semaforo, opinion) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $modelo_id,
            trim($_POST['nombre']),
            $_POST['combustible'],
            $_POST['semaforo'],
            trim($_POST['opinion'])
        ]);
        $mensaje = "Nuevo motor añadido a este vehículo.";
    }

    // 7. Añadir un nuevo punto de revisión directamente a este modelo
    if ($accion === 'crear_punto') {
        $stmt = $pdo->prepare("INSERT INTO checklist_puntos (modelo_id, seccion, descripcion, que_significa_fallo, gravedad) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $modelo_id,
            $_POST['seccion'],
            trim($_POST['descripcion']),
            trim($_POST['que_significa_fallo']),
            $_POST['gravedad']
        ]);
        $mensaje = "Nuevo punto de revisión añadido a la checklist.";
    }
}

// Cargar datos frescos del modelo, sus motores y sus puntos
$stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = ?");
$stmt->execute([$modelo_id]);
$coche = $stmt->fetch();

if (!$coche) {
    header("Location: admin.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM motores WHERE modelo_id = ?");
$stmt->execute([$modelo_id]);
$motores = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM checklist_puntos WHERE modelo_id = ?");
$stmt->execute([$modelo_id]);
$puntos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar <?= htmlspecialchars($coche['marca'] . ' ' . $coche['modelo']) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin.php">&larr; Volver al Panel Admin</a>
        <a href="ficha.php?id=<?= $coche['id'] ?>" target="_blank" class="btn btn-outline-light btn-sm">Ver Ficha Pública &nearr;</a>
    </div>
</nav>

<div class="container pb-5" style="max-width: 900px;">

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <!-- 1. Editar Ficha Principal -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Editar Datos Generales</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="accion" value="actualizar_modelo">
                <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($coche['imagen_url']) ?>">

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Marca</label>
                    <input type="text" name="marca" class="form-control" value="<?= htmlspecialchars($coche['marca']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Modelo</label>
                    <input type="text" name="modelo" class="form-control" value="<?= htmlspecialchars($coche['modelo']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Generación</label>
                    <input type="text" name="generacion" class="form-control" value="<?= htmlspecialchars($coche['generacion']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Año Inicio</label>
                    <input type="number" name="anio_inicio" class="form-control" value="<?= $coche['anio_inicio'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Año Fin</label>
                    <input type="number" name="anio_fin" class="form-control" value="<?= $coche['anio_fin'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Nota</label>
                    <input type="number" step="0.1" min="1" max="5" name="puntuacion_general" class="form-control" value="<?= $coche['puntuacion_general'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Uso Ideal</label>
                    <select name="uso_ideal" class="form-select">
                        <?php 
                        $usos = ['Ciudad / Diario', 'Uso mixto', 'Viajes largos', 'Deportivo / Disfrute'];
                        foreach ($usos as $u): ?>
                            <option value="<?= $u ?>" <?= $coche['uso_ideal'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars($coche['imagen_url']) ?>" alt="Actual" style="height: 60px; width: 90px; object-fit: cover;" class="rounded border">
                    <div class="flex-grow-1">
                        <label class="form-label small fw-bold">Cambiar Foto (opcional)</label>
                        <input type="file" name="foto" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold">Veredicto general</label>
                    <textarea name="resumen_veredicto" rows="3" class="form-control" required><?= htmlspecialchars($coche['resumen_veredicto']) ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Posición Top Ventas <span class="fw-normal text-muted">(opcional, dejar vacío si no aplica)</span></label>
                    <input type="number" name="ranking_ventas" min="1" class="form-control"
                           value="<?= $coche['ranking_ventas'] !== null ? (int)$coche['ranking_ventas'] : '' ?>"
                           placeholder="Ej. 1, 2, 3…">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar Cambios del Modelo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Editar Motores Existentes -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Motores Asociados (<?= count($motores) ?>)</div>
        <div class="card-body d-flex flex-column gap-3">
            <?php if (empty($motores)): ?>
                <p class="text-muted small mb-0">No hay motores registrados para este coche.</p>
            <?php else: ?>
                <?php foreach ($motores as $m): ?>
                    <form method="POST" class="border rounded p-3 bg-white">
                        <input type="hidden" name="motor_id" value="<?= $m['id'] ?>">
                        <div class="row g-2 mb-2">
                            <div class="col-md-5">
                                <input type="text" name="nombre" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($m['nombre']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <select name="combustible" class="form-select form-select-sm">
                                    <?php foreach (['Diésel', 'Gasolina', 'Híbrido'] as $comb): ?>
                                        <option value="<?= $comb ?>" <?= $m['combustible'] === $comb ? 'selected' : '' ?>><?= $comb ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="semaforo" class="form-select form-select-sm">
                                    <option value="Verde" <?= $m['semaforo'] === 'Verde' ? 'selected' : '' ?>>🟢 Verde</option>
                                    <option value="Amarillo" <?= $m['semaforo'] === 'Amarillo' ? 'selected' : '' ?>>🟡 Amarillo</option>
                                    <option value="Rojo" <?= $m['semaforo'] === 'Rojo' ? 'selected' : '' ?>>🔴 Rojo</option>
                                </select>
                            </div>
                        </div>
                        <textarea name="opinion" rows="2" class="form-control form-control-sm mb-2" required><?= htmlspecialchars($m['opinion']) ?></textarea>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" name="accion" value="eliminar_motor" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Seguro que quieres borrar este motor?')">Eliminar</button>
                            <button type="submit" name="accion" value="actualizar_motor" class="btn btn-dark btn-sm">Guardar Motor</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Formulario rápido: Añadir nuevo motor aquí mismo -->
            <form method="POST" class="border border-primary-subtle rounded p-3 bg-light mt-2">
                <input type="hidden" name="accion" value="crear_motor">
                <div class="small fw-bold text-primary mb-2">+ Añadir nuevo motor a este coche</div>
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej. 2.0 TDI 140cv (BKD)" required>
                    </div>
                    <div class="col-md-3">
                        <select name="combustible" class="form-select form-select-sm">
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diésel">Diésel</option>
                            <option value="Híbrido">Híbrido</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="semaforo" class="form-select form-select-sm">
                            <option value="Verde">🟢 Verde</option>
                            <option value="Amarillo">🟡 Amarillo</option>
                            <option value="Rojo">🔴 Rojo</option>
                        </select>
                    </div>
                </div>
                <textarea name="opinion" rows="2" class="form-control form-control-sm mb-2" placeholder="Opinión y fallos endémicos..." required></textarea>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm">+ Añadir Motor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Editar Puntos de Checklist Existentes -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Puntos de Revisión (<?= count($puntos) ?>)</div>
        <div class="card-body d-flex flex-column gap-3">
            <?php if (empty($puntos)): ?>
                <p class="text-muted small mb-0">No hay puntos registrados para este coche.</p>
            <?php else: ?>
                <?php foreach ($puntos as $p): ?>
                    <form method="POST" class="border rounded p-3 bg-white">
                        <input type="hidden" name="punto_id" value="<?= $p['id'] ?>">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <select name="seccion" class="form-select form-select-sm">
                                    <?php foreach (['Bajo el capó', 'Prueba dinámica', 'Interior', 'Exterior / Bajos'] as $sec): ?>
                                        <option value="<?= $sec ?>" <?= $p['seccion'] === $sec ? 'selected' : '' ?>><?= $sec ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select name="gravedad" class="form-select form-select-sm">
                                    <option value="Leve" <?= $p['gravedad'] === 'Leve' ? 'selected' : '' ?>>Leve</option>
                                    <option value="Grave" <?= $p['gravedad'] === 'Grave' ? 'selected' : '' ?>>Grave</option>
                                    <option value="Critico" <?= $p['gravedad'] === 'Critico' ? 'selected' : '' ?>>Crítico</option>
                                </select>
                            </div>
                        </div>
                        <input type="text" name="descripcion" class="form-control form-control-sm mb-2 fw-semibold" value="<?= htmlspecialchars($p['descripcion']) ?>" required>
                        <textarea name="que_significa_fallo" rows="2" class="form-control form-control-sm mb-2" required><?= htmlspecialchars($p['que_significa_fallo']) ?></textarea>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" name="accion" value="eliminar_punto" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Seguro que quieres borrar este punto?')">Eliminar</button>
                            <button type="submit" name="accion" value="actualizar_punto" class="btn btn-dark btn-sm">Guardar Punto</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
            <!-- Formulario rápido: Añadir nuevo punto aquí mismo -->
            <form method="POST" class="border border-primary-subtle rounded p-3 bg-light mt-2">
                <input type="hidden" name="accion" value="crear_punto">
                <div class="small fw-bold text-primary mb-2">+ Añadir nuevo punto de revisión</div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <select name="seccion" class="form-select form-select-sm">
                            <option value="Bajo el capó">Bajo el capó</option>
                            <option value="Prueba dinámica">Prueba dinámica</option>
                            <option value="Interior">Interior</option>
                            <option value="Exterior / Bajos">Exterior / Bajos</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="gravedad" class="form-select form-select-sm">
                            <option value="Leve">Leve</option>
                            <option value="Grave" selected>Grave</option>
                            <option value="Critico">Crítico</option>
                        </select>
                    </div>
                </div>
                <input type="text" name="descripcion" class="form-control form-control-sm mb-2" placeholder="Qué revisar..." required>
                <textarea name="que_significa_fallo" rows="2" class="form-control form-control-sm mb-2" placeholder="Qué significa si falla y coste estimado..." required></textarea>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm">+ Añadir Punto</button>
                </div>
            </form>
        </div>
    </div>

</div>
</body>
</html>