<?php
require_once 'db.php';

$busqueda = trim($_GET['q'] ?? '');
$uso = trim($_GET['uso'] ?? '');

// Construimos la consulta dinámicamente según los filtros activos
$sql = "SELECT * FROM modelos WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (marca LIKE ? OR modelo LIKE ? OR generacion LIKE ?)";
    $termino = "%$busqueda%";
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
}

if ($uso !== '') {
    $sql .= " AND uso_ideal = ?";
    $params[] = $uso;
}

$sql .= " ORDER BY marca, modelo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$modelos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CocheSeguro - El templo del comprador de segunda mano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Recorte uniforme para que todas las fotos de las tarjetas midan exactamente lo mismo */
        .car-card-img {
            height: 190px;
            object-fit: cover;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🚗 CocheSeguro</a>
        <span class="navbar-text text-light small d-none d-sm-inline">
            Guías de compra, semáforos de fiabilidad y checklist sin engaños
        </span>
    </div>
</nav>

<!-- Hero con Buscador y Filtros Rápidos -->
<div class="bg-white border-bottom py-5 mb-4">
    <div class="container text-center" style="max-width: 700px;">
        <h1 class="fw-bold mb-2">No compres a ciegas.</h1>
        <p class="text-muted mb-4">Identifica la generación exacta, descubre qué motores rompen y revisa el coche como un mecánico.</p>
        
        <form method="GET" action="index.php" class="d-flex gap-2 mb-3">
            <input 
                type="text" 
                name="q" 
                class="form-control form-control-lg" 
                placeholder="Busca por marca, modelo o código (ej. E90, Golf, Focus)..." 
                value="<?= htmlspecialchars($busqueda) ?>"
            >
            <?php if ($uso !== ''): ?>
                <input type="hidden" name="uso" value="<?= htmlspecialchars($uso) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-lg px-4">Buscar</button>
        </form>

        <!-- Píldoras de filtro por necesidad -->
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <a href="index.php" class="btn btn-sm <?= $uso === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">Todos</a>
            <a href="index.php?uso=Ciudad / Diario" class="btn btn-sm <?= $uso === 'Ciudad / Diario' ? 'btn-dark' : 'btn-outline-secondary' ?>">🏙️ Ciudad / Diario</a>
            <a href="index.php?uso=Uso mixto" class="btn btn-sm <?= $uso === 'Uso mixto' ? 'btn-dark' : 'btn-outline-secondary' ?>">⚖️ Uso mixto</a>
            <a href="index.php?uso=Viajes largos" class="btn btn-sm <?= $uso === 'Viajes largos' ? 'btn-dark' : 'btn-outline-secondary' ?>">🛣️ Viajes largos</a>
            <a href="index.php?uso=Deportivo / Disfrute" class="btn btn-sm <?= $uso === 'Deportivo / Disfrute' ? 'btn-dark' : 'btn-outline-secondary' ?>">🏁 Deportivo</a>
        </div>

        <?php if ($busqueda !== '' || $uso !== ''): ?>
            <div class="mt-3">
                <a href="index.php" class="small text-danger text-decoration-none">✕ Limpiar todos los filtros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Listado de Modelos -->
<div class="container pb-5" style="max-width: 1000px;">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($modelos)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted fs-5">No hay coches catalogados con ese filtro todavía.</p>
                <a href="index.php" class="btn btn-outline-primary">Ver catálogo completo</a>
            </div>
        <?php else: ?>
            <?php foreach ($modelos as $m): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 overflow-hidden">
                        <!-- Imagen del vehículo -->
                        <img 
                            src="<?= htmlspecialchars($m['imagen_url']) ?>" 
                            class="car-card-img bg-secondary-subtle" 
                            alt="<?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?>"
                        >
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-secondary"><?= htmlspecialchars($m['generacion']) ?></span>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['uso_ideal']) ?></span>
                                <span class="badge bg-warning text-dark">★ <?= number_format($m['puntuacion_general'], 1) ?></span>
                            </div>
                            
                            <h2 class="h5 fw-bold mb-1">
                                <?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?>
                            </h2>
                            <small class="text-muted mb-3 d-block">Años <?= $m['anio_inicio'] ?> - <?= $m['anio_fin'] ?></small>
                            
                            <p class="card-text text-muted small flex-grow-1">
                                <?= htmlspecialchars(mb_strimwidth($m['resumen_veredicto'], 0, 105, '...')) ?>
                            </p>
                            
                            <a href="ficha.php?id=<?= $m['id'] ?>" class="btn btn-dark w-100 mt-3">
                                Ver guía y checklist &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>