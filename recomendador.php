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
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">&larr; Volver al Catálogo</a>
    </div>
</nav>

<div class="container pb-5" style="max-width: 850px;">
    <div class="text-center mb-4">
        <h1 class="fw-bold">🧭 ¿Qué coche me compro?</h1>
        <p class="text-muted">Dinos tu presupuesto y uso habitual. Solo te mostraremos modelos con motores fiables (Semáforo Verde).</p>
    </div>

    <!-- Formulario de 3 preguntas -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                <input type="hidden" name="buscar" value="1">

                <div class="col-md-4">
                    <label class="form-label fw-bold small">1. Presupuesto máximo (€)</label>
                    <select name="presupuesto" class="form-select">
                        <?php foreach ([3000, 5000, 7000, 10000, 15000, 25000] as $p): ?>
                            <option value="<?= $p ?>" <?= $presupuesto === $p ? 'selected' : '' ?>>
                                Hasta <?= number_format($p, 0, ',', '.') ?> €
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">2. ¿Para qué lo vas a usar?</label>
                    <select name="uso" class="form-select">
                        <option value="">Cualquier uso</option>
                        <?php foreach (['Ciudad / Diario', 'Uso mixto', 'Viajes largos', 'Deportivo / Disfrute'] as $u): ?>
                            <option value="<?= $u ?>" <?= $uso === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small">3. Combustible preferido</label>
                    <select name="combustible" class="form-select">
                        <option value="">Indiferente</option>
                        <?php foreach (['Gasolina', 'Diésel', 'Híbrido'] as $c): ?>
                            <option value="<?= $c ?>" <?= $combustible === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-dark px-4">Encontrar mi coche ideal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <?php if ($busqueda_realizada): ?>
        <?php if (empty($resultados)): ?>
            <div class="alert alert-warning text-center">
                No tenemos ningún modelo en la base de datos que cumpla exactamente esos 3 filtros. Prueba a subir el presupuesto o dejar el uso en "Cualquier uso".
            </div>
        <?php else: ?>
            <h5 class="fw-bold mb-3">Opciones recomendadas para ti (<?= count($resultados) ?>):</h5>
            <div class="row g-3">
                <?php foreach ($resultados as $coche): ?>
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm border-0">
                            <img src="<?= htmlspecialchars($coche['imagen_url']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="<?= htmlspecialchars($coche['modelo']) ?>">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars("{$coche['marca']} {$coche['modelo']} ({$coche['generacion']})") ?></h5>
                                    <span class="badge bg-success">★ <?= $coche['puntuacion_general'] ?></span>
                                </div>
                                <p class="text-muted small mb-2">Mercado aprox: ~<?= number_format($coche['precio_orientativo'], 0, ',', '.') ?> € · <?= htmlspecialchars($coche['uso_ideal']) ?></p>
                                <div class="alert alert-success py-2 px-3 small mb-3">
                                    <strong>🟢 Motor(es) a buscar:</strong><br>
                                    <?= htmlspecialchars($coche['motores_verdes']) ?>
                                </div>
                                <?php
                                    $ficha_params = ['id' => $coche['id']];
                                    if ($presupuesto) $ficha_params['max_price'] = $presupuesto;
                                    if ($combustible !== '') $ficha_params['fuel'] = $combustible;
                                    $ficha_url = 'ficha.php?' . http_build_query($ficha_params);
                                ?>
                                <a href="<?= $ficha_url ?>" class="btn btn-outline-dark btn-sm mt-auto">
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