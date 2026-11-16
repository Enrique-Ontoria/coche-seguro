<?php
require_once 'db.php';

// Leemos el ID por URL (ej: ficha.php?id=2), si no viene o no es número, por defecto 1
$modelo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

// 1. Obtener datos del modelo
$stmt = $pdo->prepare("SELECT * FROM modelos WHERE id = ?");
$stmt->execute([$modelo_id]);
$coche = $stmt->fetch();

// Si ponen un ID que no existe en la BD, redirigir al inicio o frenar
if (!$coche) {
    header("Location: index.php");
    exit;
}

// 2. Obtener motores
$stmt = $pdo->prepare("SELECT * FROM motores WHERE modelo_id = ?");
$stmt->execute([$modelo_id]);
$motores = $stmt->fetchAll();

// 3. Obtener checklist
$stmt = $pdo->prepare("SELECT * FROM checklist_puntos WHERE modelo_id = ?");
$stmt->execute([$modelo_id]);
$puntos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($coche['marca'] . ' ' . $coche['modelo']) ?> - Guía de Compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5" style="max-width: 800px;">
    <!-- Botón Volver -->
    <a href="index.php" class="btn btn-outline-secondary mb-3">&larr; Volver al buscador</a>

    <!-- Cabecera -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <img src="<?= htmlspecialchars($coche['imagen_url']) ?>" alt="<?= htmlspecialchars($coche['modelo']) ?>" style="max-height: 280px; object-fit: cover; width: 100%;">
        <div class="card-body p-4">
        <div class="card-body p-4">
            <span class="badge bg-secondary mb-2"><?= $coche['generacion'] ?> (<?= $coche['anio_inicio'] ?>-<?= $coche['anio_fin'] ?>)</span>
            <h1 class="h2 fw-bold text-dark"><?= htmlspecialchars($coche['marca'] . ' ' . $coche['modelo']) ?></h1>
            <p class="text-muted mt-2 mb-0"><?= htmlspecialchars($coche['resumen_veredicto']) ?></p>
        </div>
    </div>

    <!-- Motores: Semáforo -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h2 class="h4 fw-bold mb-3">Semáforo de Motorizaciones</h2>
            <div class="list-group list-group-flush">
                <?php foreach ($motores as $m): ?>
                    <?php 
                        $badgeColor = match($m['semaforo']) {
                            'Verde' => 'success',
                            'Amarillo' => 'warning text-dark',
                            'Rojo' => 'danger',
                            default => 'secondary'
                        };
                    ?>
                    <div class="list-group-item px-0 py-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold"><?= htmlspecialchars($m['nombre']) ?></span>
                            <span class="badge bg-<?= $badgeColor ?>"><?= $m['semaforo'] ?></span>
                        </div>
                        <small class="text-muted"><?= htmlspecialchars($m['opinion']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Checklist interactiva -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold mb-0">Qué revisar antes de soltar un euro</h2>
                <span class="badge bg-light text-muted border">Marca los fallos que detectes</span>
            </div>

            <!-- Panel de Veredicto Dinámico que busca checklist.js -->
            <div id="panel-veredicto" class="alert alert-success d-flex align-items-center gap-3 mb-4">
                <span class="fs-2" id="icono-veredicto">🟢</span>
                <div>
                    <h5 class="fw-bold mb-0" id="titulo-veredicto">Unidad aparentemente sana</h5>
                    <small id="desc-veredicto">No has marcado ningún fallo crítico todavía. Sigue revisando los puntos.</small>
                </div>
            </div>

            <!-- Listado con clases y data-attributes para JS -->
            <div class="d-flex flex-column gap-3">
                <?php foreach ($puntos as $p): ?>
                    <?php 
                        $gravedad = $p['gravedad'] ?? 'Grave';
                        $badgeGravedad = match($gravedad) {
                            'Critico' => 'bg-danger',
                            'Grave' => 'bg-warning text-dark',
                            'Leve' => 'bg-secondary',
                            default => 'bg-secondary'
                        };
                    ?>
                    <div class="form-check p-3 bg-white border rounded checklist-item">
                        <input 
                            class="form-check-input ms-0 me-3 chk-inspeccion" 
                            type="checkbox" 
                            id="check_<?= $p['id'] ?>"
                            data-gravedad="<?= htmlspecialchars($gravedad) ?>"
                        >
                        <label class="form-check-label w-100" for="check_<?= $p['id'] ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['seccion']) ?></span>
                                <span class="badge <?= $badgeGravedad ?>"><?= htmlspecialchars($gravedad) ?></span>
                            </div>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($p['descripcion']) ?></div>
                            <small class="text-danger d-block mt-1">⚠️ Si falla: <?= htmlspecialchars($p['que_significa_fallo']) ?></small>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/checklist.js"></script>
</body>
</html>