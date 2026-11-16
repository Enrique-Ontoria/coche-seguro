<?php
session_start();
require_once 'db.php';
require_once 'config.php';

$mensaje = '';
$tipo_alerta = 'success';

// 1. Control de Login y Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['es_admin'] = true;
    } else {
        $mensaje = "Contraseña incorrecta.";
        $tipo_alerta = "danger";
    }
}

// 2. Procesar formularios si estamos autenticados
if (!empty($_SESSION['es_admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A) Crear nuevo modelo + subida de imagen
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear_modelo') {
        $marca = trim($_POST['marca']);
        $modelo = trim($_POST['modelo']);
        $generacion = trim($_POST['generacion']);
        $anio_inicio = (int)$_POST['anio_inicio'];
        $anio_fin = (int)$_POST['anio_fin'];
        $puntuacion = (float)$_POST['puntuacion_general'];
        $uso_ideal = $_POST['uso_ideal'];
        $veredicto = trim($_POST['resumen_veredicto']);
        $ruta_imagen = 'assets/img/default-car.jpg';

        // Procesar foto si se subió una
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $permitidas)) {
                // Generamos un nombre limpio único: ej. seat-leon-1p-17318000.jpg
                $nombre_limpio = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', "$marca-$modelo-$generacion")) . '-' . time() . '.' . $ext;
                $destino = 'assets/img/' . $nombre_limpio;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $ruta_imagen = $destino;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO modelos (marca, modelo, generacion, anio_inicio, anio_fin, puntuacion_general, resumen_veredicto, imagen_url, uso_ideal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$marca, $modelo, $generacion, $anio_inicio, $anio_fin, $puntuacion, $veredicto, $ruta_imagen, $uso_ideal]);
        
        $mensaje = "Modelo <strong>$marca $modelo</strong> añadido correctamente.";
    }

    // B) Añadir motor a un modelo existente
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear_motor') {
        $stmt = $pdo->prepare("INSERT INTO motores (modelo_id, nombre, combustible, semaforo, opinion) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$_POST['modelo_id'],
            trim($_POST['nombre']),
            $_POST['combustible'],
            $_POST['semaforo'],
            trim($_POST['opinion'])
        ]);
        $mensaje = "Motor añadido al catálogo.";
    }

    // C) Añadir punto de checklist a un modelo existente
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear_punto') {
        $stmt = $pdo->prepare("INSERT INTO checklist_puntos (modelo_id, seccion, descripcion, que_significa_fallo, gravedad) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$_POST['modelo_id'],
            $_POST['seccion'],
            trim($_POST['descripcion']),
            trim($_POST['que_significa_fallo']),
            $_POST['gravedad']
        ]);
        $mensaje = "Punto de revisión añadido a la checklist.";
    }
}

// Obtener lista de modelos para los desplegables
$lista_modelos = $pdo->query("SELECT id, marca, modelo, generacion FROM modelos ORDER BY marca, modelo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - CocheSeguro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🚗 CocheSeguro <span class="badge bg-warning text-dark ms-2">Admin</span></a>
        <?php if (!empty($_SESSION['es_admin'])): ?>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-light btn-sm">Ver Web</a>
                <a href="admin.php?logout=1" class="btn btn-danger btn-sm">Cerrar sesión</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container pb-5" style="max-width: 900px;">

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_alerta ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <?php if (empty($_SESSION['es_admin'])): ?>
        <!-- Formulario de Login -->
        <div class="card shadow-sm border-0 mx-auto mt-5" style="max-width: 400px;">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3 text-center">Acceso Administrador</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Entrar</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Panel de Gestión -->
        <div class="row g-4">
            
            <!-- 1. Nuevo Coche -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">1. Dar de alta nuevo coche</div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="row g-3">
                            <input type="hidden" name="accion" value="crear_modelo">
                            
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Marca</label>
                                <input type="text" name="marca" class="form-control" placeholder="Ej. Seat" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Modelo</label>
                                <input type="text" name="modelo" class="form-control" placeholder="Ej. León" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Generación / Código</label>
                                <input type="text" name="generacion" class="form-control" placeholder="Ej. Mk2 (1P)" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Año Inicio</label>
                                <input type="number" name="anio_inicio" class="form-control" placeholder="2005" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Año Fin</label>
                                <input type="number" name="anio_fin" class="form-control" placeholder="2012" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Nota (1.0 a 5.0)</label>
                                <input type="number" step="0.1" min="1" max="5" name="puntuacion_general" class="form-control" value="4.0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Uso Ideal</label>
                                <select name="uso_ideal" class="form-select">
                                    <option value="Ciudad / Diario">Ciudad / Diario</option>
                                    <option value="Uso mixto">Uso mixto</option>
                                    <option value="Viajes largos">Viajes largos</option>
                                    <option value="Deportivo / Disfrute">Deportivo / Disfrute</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Foto del coche (JPG, PNG, WEBP)</label>
                                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Veredicto general</label>
                                <textarea name="resumen_veredicto" rows="2" class="form-control" placeholder="Resumen rápido de virtudes y defectos..." required></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Guardar Modelo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2. Añadir Motor -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">2. Añadir Motor a un Coche</div>
                    <div class="card-body">
                        <form method="POST" class="d-flex flex-column gap-3">
                            <input type="hidden" name="accion" value="crear_motor">
                            
                            <div>
                                <label class="form-label small fw-bold">Coche</label>
                                <select name="modelo_id" class="form-select" required>
                                    <?php foreach ($lista_modelos as $mod): ?>
                                        <option value="<?= $mod['id'] ?>"><?= htmlspecialchars("{$mod['marca']} {$mod['modelo']} ({$mod['generacion']})") ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="form-label small fw-bold">Nombre / Código Motor</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej. 1.9 TDI 105cv (BLS)" required>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Combustible</label>
                                    <select name="combustible" class="form-select">
                                        <option value="Diésel">Diésel</option>
                                        <option value="Gasolina">Gasolina</option>
                                        <option value="Híbrido">Híbrido</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Semáforo</label>
                                    <select name="semaforo" class="form-select">
                                        <option value="Verde">🟢 Verde</option>
                                        <option value="Amarillo">🟡 Amarillo</option>
                                        <option value="Rojo">🔴 Rojo</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="form-label small fw-bold">Opinión / Fallos endémicos</label>
                                <textarea name="opinion" rows="2" class="form-control" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-dark mt-auto">Añadir Motor</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. Añadir Punto Checklist -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">3. Añadir Punto de Revisión</div>
                    <div class="card-body">
                        <form method="POST" class="d-flex flex-column gap-3">
                            <input type="hidden" name="accion" value="crear_punto">
                            
                            <div>
                                <label class="form-label small fw-bold">Coche</label>
                                <select name="modelo_id" class="form-select" required>
                                    <?php foreach ($lista_modelos as $mod): ?>
                                        <option value="<?= $mod['id'] ?>"><?= htmlspecialchars("{$mod['marca']} {$mod['modelo']} ({$mod['generacion']})") ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Sección</label>
                                    <select name="seccion" class="form-select">
                                        <option value="Bajo el capó">Bajo el capó</option>
                                        <option value="Prueba dinámica">Prueba dinámica</option>
                                        <option value="Interior">Interior</option>
                                        <option value="Exterior / Bajos">Exterior / Bajos</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Gravedad si falla</label>
                                    <select name="gravedad" class="form-select">
                                        <option value="Leve">Leve</option>
                                        <option value="Grave" selected>Grave</option>
                                        <option value="Critico">Crítico</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="form-label small fw-bold">Qué revisar</label>
                                <input type="text" name="descripcion" class="form-control" placeholder="Ej. Comprobar holgura en turbo..." required>
                            </div>

                            <div>
                                <label class="form-label small fw-bold">Qué significa si falla</label>
                                <textarea name="que_significa_fallo" rows="2" class="form-control" placeholder="Ej. Desgaste de eje, reparación de 600€..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-dark mt-auto">Añadir Punto</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 4. Listado de coches existentes para editar -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">4. Catálogo Actual (Editar o Borrar detalles)</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vehículo</th>
                                    <th>Generación</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_modelos as $mod): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars("{$mod['marca']} {$mod['modelo']}") ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($mod['generacion']) ?></span></td>
                                        <td class="text-end">
                                            <a href="editar_modelo.php?id=<?= $mod['id'] ?>" class="btn btn-outline-dark btn-sm">
                                                ✏️ Editar Ficha y Motores
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

</body>
</html>