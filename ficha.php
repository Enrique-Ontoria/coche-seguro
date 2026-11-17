<?php

require_once 'db.php';

// Leemos el ID por URL (ej: ficha.php?id=2), si no viene o no es número, por defecto 1
$modelo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

// Procesar envío de reporte de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_reporte'])) {
    $tipo = $_POST['tipo'] ?? 'Nuevo fallo / punto';
    $mensaje_reporte = trim($_POST['mensaje'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');

    if ($mensaje_reporte !== '') {
        $stmt = $pdo->prepare("INSERT INTO reportes (modelo_id, tipo, mensaje, contacto) VALUES (?, ?, ?, ?)");
        $stmt->execute([$modelo_id, $tipo, $mensaje_reporte, $contacto]);
        
        header("Location: ficha.php?id=$modelo_id&reportado=1#seccion-reporte");
        exit;
    }
}

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
            <div id="panel-veredicto" 
            data-modelo-id="<?= $coche['id'] ?>" 
            data-modelo-nombre="<?= htmlspecialchars($coche['marca'] . ' ' . $coche['modelo'] . ' (' . $coche['generacion'] . ')') ?>"
            class="alert alert-success d-flex align-items-center gap-3 mb-3">

            <!-- Barra de acciones rápidas -->
            <div class="d-flex justify-content-between align-items-center gap-2 mb-4">
                <button type="button" id="btn-copiar-informe" class="btn btn-dark btn-sm">
                    📋 Copiar resumen para WhatsApp / Negociar
                </button>
                <button type="button" id="btn-reset-checklist" class="btn btn-outline-secondary btn-sm">
                    🔄 Limpiar marcas
                </button>
            </div>
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
    <!-- Buzón de colaboración / Reportar fallo -->
    <div id="seccion-reporte" class="card shadow-sm border-0 mt-4">
        <div class="card-body p-4">
            <?php if (isset($_GET['reportado'])): ?>
                <div class="alert alert-success mb-3">
                    ✅ <strong>¡Gracias por aportar!</strong> Hemos recibido tu reporte y lo revisaremos para mejorar esta guía.
                </div>
            <?php endif; ?>

            <details <?= isset($_GET['reportado']) ? '' : '' ?>>
                <summary class="fw-bold text-dark" style="cursor: pointer;">
                    💡 ¿Conoces otro fallo común de este coche o ves algún dato erróneo? Repórtalo aquí
                </summary>
                
                <form method="POST" class="mt-3 pt-3 border-top d-flex flex-column gap-3">
                    <input type="hidden" name="enviar_reporte" value="1">
                    
                    <div>
                        <label class="form-label small fw-bold">Motivo del reporte</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="Nuevo fallo / punto">Añadir un fallo común o punto a revisar</option>
                            <option value="Dato erróneo">Corregir un dato erróneo en la ficha</option>
                            <option value="Otro">Otra sugerencia</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label small fw-bold">Cuéntanos los detalles (motor afectado, síntomas, coste...)</label>
                        <textarea name="mensaje" rows="3" class="form-control form-control-sm" placeholder="Ej. En los motores 1.6 gasolina suele fallar el sensor de cigüeñal en caliente..." required></textarea>
                    </div>

                    <div>
                        <label class="form-label small fw-bold">Tu nombre o email (opcional, por si queremos preguntarte)</label>
                        <input type="text" name="contacto" class="form-control form-control-sm" placeholder="Opcional">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-dark btn-sm">Enviar reporte</button>
                    </div>
                </form>
            </details>
        </div>
    </div>
</div>
    <?php
    // ── Parámetros opcionales del recomendador ────────────────────────────────
    $max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_INT) ?: null;
    $fuel_raw  = isset($_GET['fuel']) ? trim(strip_tags($_GET['fuel'])) : '';
    $fuel      = in_array($fuel_raw, ['Gasolina', 'Diésel', 'Híbrido'], true) ? $fuel_raw : '';

    // ── Mapas de combustible por portal ──────────────────────────────────────
    $fuel_wallapop   = ['Gasolina' => 'gasoline', 'Diésel' => 'gasoil',   'Híbrido' => 'hybrid'];
    $fuel_autoscout  = ['Gasolina' => 'B',         'Diésel' => 'D',        'Híbrido' => '2'];
    $fuel_cochesnet  = ['Gasolina' => '1',          'Diésel' => '2',        'Híbrido' => '4'];
    $fuel_milanuncios= ['Gasolina' => 'gasolina',  'Diésel' => 'diesel',   'Híbrido' => 'hibrido'];

    // ── Construir parámetros limpios de búsqueda ──────────────────────────────
    $query_texto = urlencode($coche['marca'] . ' ' . $coche['modelo']);
    $slug_marca  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $coche['marca']));
    $slug_modelo = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $coche['modelo']));
    $anio_min    = (int)$coche['anio_inicio'];
    $anio_max    = (int)$coche['anio_fin'];

    // ── Wallapop ──────────────────────────────────────────────────────────────
    $url_wallapop = "https://es.wallapop.com/app/search?keywords={$query_texto}&min_year={$anio_min}&max_year={$anio_max}&category_ids=100";
    if ($max_price) $url_wallapop .= "&max_sale_price={$max_price}";
    if ($fuel !== '' && isset($fuel_wallapop[$fuel])) $url_wallapop .= "&engine={$fuel_wallapop[$fuel]}";

    // ── AutoScout24 ───────────────────────────────────────────────────────────
    $url_autoscout = "https://www.autoscout24.es/lst/{$slug_marca}/{$slug_modelo}?fregfrom={$anio_min}&fregto={$anio_max}&sort=price&desc=0";
    if ($max_price) $url_autoscout .= "&priceto={$max_price}";
    if ($fuel !== '' && isset($fuel_autoscout[$fuel])) $url_autoscout .= "&fuel={$fuel_autoscout[$fuel]}";

    // ── Coches.net ────────────────────────────────────────────────────────────
    $url_cochesnet = "https://www.coches.net/segunda-mano/?KeyWords={$query_texto}&MinYear={$anio_min}&MaxYear={$anio_max}";
    if ($max_price) $url_cochesnet .= "&MaxPrice={$max_price}";
    if ($fuel !== '' && isset($fuel_cochesnet[$fuel])) $url_cochesnet .= "&FuelType={$fuel_cochesnet[$fuel]}";

    // ── Milanuncios ───────────────────────────────────────────────────────────
    $url_milanuncios = "https://www.milanuncios.com/coches-de-segunda-mano/{$slug_marca}-{$slug_modelo}.htm?desde={$anio_min}&hasta={$anio_max}";
    if ($max_price) $url_milanuncios .= "&hasta_precio={$max_price}";
    if ($fuel !== '' && isset($fuel_milanuncios[$fuel])) $url_milanuncios .= "&combustible={$fuel_milanuncios[$fuel]}";
    ?>

    <!-- Bloque: Buscar unidades en venta -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1">🔎 Buscar unidades en venta (<?= $anio_min ?>–<?= $anio_max ?>)</h5>
                    <p class="text-muted small mb-0">
                        Enlaces directos filtrados por generación. Recuerda filtrar luego por los motores en <strong>Verde</strong>.
                    </p>
                    <?php if ($max_price || $fuel !== ''): ?>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <?php if ($max_price): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-normal">
                            💰 Presupuesto máx: <?= number_format($max_price, 0, ',', '.') ?> €
                        </span>
                        <?php endif; ?>
                        <?php if ($fuel !== ''): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle fw-normal">
                            ⛽ Combustible: <?= htmlspecialchars($fuel) ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-muted" style="font-size:.75rem;">· Filtros aplicados desde el recomendador</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= $url_wallapop ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm fw-semibold">
                        Wallapop &nearr;
                    </a>
                    <a href="<?= $url_cochesnet ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm fw-semibold">
                        Coches.net &nearr;
                    </a>
                    <a href="<?= $url_autoscout ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-warning text-dark btn-sm fw-semibold">
                        AutoScout24 &nearr;
                    </a>
                    <a href="<?= $url_milanuncios ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm fw-semibold">
                        Milanuncios &nearr;
                    </a>
                </div>
            </div>
        </div>
</div>
<script src="assets/js/checklist.js"></script>
</body>
</html>