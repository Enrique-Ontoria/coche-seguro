<?php
require_once 'db.php';

$presupuesto = filter_input(INPUT_GET, 'presupuesto', FILTER_VALIDATE_INT) ?: 8000;
$uso         = $_GET['uso'] ?? '';
$combustible = $_GET['combustible'] ?? '';

$resultados = [];
$busqueda_realizada = isset($_GET['buscar']);

if ($busqueda_realizada) {
    $sql = "
        SELECT DISTINCT m.*, 
               GROUP_CONCAT(mot.nombre SEPARATOR ' / ') AS motores_verdes
        FROM modelos m
        INNER JOIN motores mot ON m.id = mot.modelo_id
        WHERE mot.semaforo = 'Verde'
          AND m.precio_orientativo <= :presupuesto
    ";
    $params = [':presupuesto' => $presupuesto];

    if ($uso !== '') {
        $sql .= " AND m.uso_ideal = :uso";
        $params[':uso'] = $uso;
    }

    if ($combustible !== '') {
        $sql .= " AND mot.combustible = :combustible";
        $params[':combustible'] = $combustible;
    }

    $sql .= " GROUP BY m.id ORDER BY m.puntuacion_general DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Qué coche me compro? - Recomendador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-editorial mb-0">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="index.php">&larr; Volver al Catálogo</a>
        <a href="recomendador.php" class="btn btn-editorial-amber btn-sm ms-auto">🧭 Recomendador</a>
    </div>
</nav>

<!-- Hero del recomendador -->
<div class="hero-editorial py-4 mb-4">
    <div class="container text-center" style="max-width: 700px;">
        <h1 class="fw-bold mb-1">🧭 ¿Qué coche me compro?</h1>
        <p class="mb-0" style="color: rgba(255,255,255,.7);">Dinos tu presupuesto y uso habitual. Solo te mostraremos modelos con motores fiables (Semáforo Verde).</p>
    </div>
</div>

<div class="container pb-5" style="max-width: 860px;">

    <!-- Formulario de 3 preguntas -->
    <div class="card-editorial p-4 mb-4">
        <form method="GET" class="row g-3">
            <input type="hidden" name="buscar" value="1">

            <div class="col-md-4">
                <label class="form-label fw-bold small" style="color: var(--text-secondary);">1. Presupuesto máximo (€)</label>
                <select name="presupuesto" class="form-select">
                    <?php foreach ([3000, 5000, 7000, 10000, 15000, 25000] as $p): ?>
                        <option value="<?= $p ?>" <?= $presupuesto === $p ? 'selected' : '' ?>>
                            Hasta <?= number_format($p, 0, ',', '.') ?> €
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small" style="color: var(--text-secondary);">2. ¿Para qué lo vas a usar?</label>
                <select name="uso" class="form-select">
                    <option value="">Cualquier uso</option>
                    <?php foreach (['Ciudad / Diario', 'Uso mixto', 'Viajes largos', 'Deportivo / Disfrute'] as $u): ?>
                        <option value="<?= $u ?>" <?= $uso === $u ? 'selected' : '' ?>><?= $u ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small" style="color: var(--text-secondary);">3. Combustible preferido</label>
                <select name="combustible" class="form-select">
                    <option value="">Indiferente</option>
                    <?php foreach (['Gasolina', 'Diésel', 'Híbrido'] as $c): ?>
                        <option value="<?= $c ?>" <?= $combustible === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 text-end mt-3">
                <button type="submit" class="btn btn-editorial-primary px-4">Encontrar mi coche ideal</button>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <?php if ($busqueda_realizada): ?>
        <?php if (empty($resultados)): ?>
            <div class="alert alert-warning text-center">
                No tenemos ningún modelo en la base de datos que cumpla exactamente esos 3 filtros. Prueba a subir el presupuesto o dejar el uso en "Cualquier uso".
            </div>
        <?php else: ?>
            <h5 class="fw-bold mb-3" style="color: var(--text-primary);">Opciones recomendadas para ti (<?= count($resultados) ?>):</h5>
            <div class="row g-3">
                <?php foreach ($resultados as $coche): ?>
                    <div class="col-md-6">
                        <div class="card-editorial card-editorial-hover h-100 overflow-hidden d-flex flex-column">
                            <img src="<?= htmlspecialchars($coche['imagen_url']) ?>" class="card-img-editorial" alt="<?= htmlspecialchars($coche['modelo']) ?>">
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                                    <h5 class="fw-bold mb-0 fs-6" style="color: var(--text-primary);">
                                        <?= htmlspecialchars("{$coche['marca']} {$coche['modelo']} ({$coche['generacion']})") ?>
                                    </h5>
                                    <span class="badge-pill-tech font-mono flex-shrink-0">★ <?= $coche['puntuacion_general'] ?></span>
                                </div>
                                <p class="small mb-2" style="color: var(--text-muted);">
                                    Mercado aprox: ~<span class="font-mono"><?= number_format($coche['precio_orientativo'], 0, ',', '.') ?></span> € · <?= htmlspecialchars($coche['uso_ideal']) ?>
                                </p>
                                <div class="p-2 rounded small mb-3 semaforo-verde">
                                    <strong>🟢 Motor(es) a buscar:</strong><br>
                                    <?= htmlspecialchars($coche['motores_verdes']) ?>
                                </div>
                                <?php
                                    $ficha_params = ['id' => $coche['id']];
                                    if ($presupuesto) $ficha_params['max_price'] = $presupuesto;
                                    if ($combustible !== '') $ficha_params['fuel'] = $combustible;
                                    $ficha_url = 'ficha.php?' . http_build_query($ficha_params);
                                ?>
                                <a href="<?= $ficha_url ?>" class="btn btn-editorial-primary btn-sm mt-auto">
                                    Ver guía de compra y checklist &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
