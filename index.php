<?php
require_once 'db.php';

$busqueda = trim($_GET['q'] ?? '');
$uso = trim($_GET['uso'] ?? '');

// ── Consulta catálogo general ─────────────────────────────────────────────
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

// ── Consulta Top Ventas (siempre, sin filtros de sesión) ──────────────────
$top_ventas = $pdo->query("
    SELECT m.*,
           (SELECT mot.nombre FROM motores mot
            WHERE mot.modelo_id = m.id AND mot.semaforo = 'Rojo'
            ORDER BY mot.id ASC LIMIT 1) AS motor_rojo,
           (SELECT mot.nombre FROM motores mot
            WHERE mot.modelo_id = m.id AND mot.semaforo = 'Verde'
            ORDER BY mot.id ASC LIMIT 1) AS motor_verde
    FROM modelos m
    WHERE m.ranking_ventas IS NOT NULL
    ORDER BY m.ranking_ventas ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CocheSeguro - El templo del comprador de segunda mano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-editorial">
    <div class="container">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 17H3a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h1l3-5h8l3 5h1a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/>
                <circle cx="7.5" cy="17.5" r="2.5"/>
                <circle cx="16.5" cy="17.5" r="2.5"/>
            </svg>
            CocheSeguro
        </a>
        <span class="navbar-text small d-none d-sm-inline" style="color: rgba(255,255,255,.55);">
            Guías de compra, semáforos de fiabilidad y checklist sin engaños
        </span>
    </div>
</nav>

<!-- Hero con buscador y filtros rápidos -->
<div class="hero-editorial py-5 mb-4">
    <div class="container text-center" style="max-width: 700px;">
        <h1 class="fw-bold mb-2">No compres a ciegas.</h1>
        <p class="mb-4" style="color: rgba(255,255,255,.7);">Identifica la generación exacta, descubre qué motores rompen y revisa el coche como un mecánico.</p>

        <!-- Búsqueda en tiempo real (cliente) -->
        <div class="d-flex gap-2 mb-3">
            <input
                type="text"
                id="buscador-live"
                class="form-control form-control-lg"
                placeholder="Busca por marca, modelo o uso ideal…"
                value="<?= htmlspecialchars($busqueda) ?>"
                autocomplete="off"
            >
        </div>

        <!-- Filter dock estilo Awwwards -->
        <div class="filter-dock-wrapper mt-3">
            <div class="filter-dock" id="filterDock">
                <div class="filter-glider" id="filterGlider"></div>
                <button class="filter-pill-btn <?= $uso === '' ? 'active' : '' ?>"
                        data-uso="">Todos</button>
                <button class="filter-pill-btn <?= $uso === 'Ciudad / Diario'     ? 'active' : '' ?>"
                        data-uso="Ciudad / Diario">Ciudad / Diario</button>
                <button class="filter-pill-btn <?= $uso === 'Uso mixto'           ? 'active' : '' ?>"
                        data-uso="Uso mixto">Uso mixto</button>
                <button class="filter-pill-btn <?= $uso === 'Viajes largos'       ? 'active' : '' ?>"
                        data-uso="Viajes largos">Viajes largos</button>
                <button class="filter-pill-btn <?= $uso === 'Deportivo / Disfrute'? 'active' : '' ?>"
                        data-uso="Deportivo / Disfrute">Deportivo</button>
                <button class="filter-pill-btn <?= $uso === '__top__' ? 'active' : '' ?>"
                        data-uso="__top__">Top Ventas</button>
            </div>
        </div>

        <?php if ($busqueda !== '' || $uso !== ''): ?>
            <div class="mt-3">
                <a href="index.php" class="small text-warning text-decoration-none">Limpiar filtros</a>
            </div>
        <?php endif; ?>

        <!-- CTA Recomendador destacado -->
        <div class="hero-cta-box p-3 mt-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 text-start mx-auto" style="max-width: 680px;">
            <div>
                <p class="fw-bold mb-0 text-white lh-sm">¿No tienes claro qué coche buscar?</p>
                <p class="mb-0 small" style="color: rgba(255,255,255,.65);">Filtra por presupuesto, uso y combustible y descubre qué motores no fallan.</p>
            </div>
            <a href="recomendador.php" class="btn btn-recomendador-glow text-nowrap d-inline-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>
                </svg>
                Ayúdame a elegir &rarr;
            </a>
        </div>
    </div>
</div>

<!-- Top Ventas de Ocasión -->
<?php if (!empty($top_ventas)): ?>
<div class="container mb-2" style="max-width: 1060px;">
    <div class="d-flex align-items-baseline gap-3 mb-1">
        <h2 class="h5 fw-bold mb-0" style="color: var(--text-primary);">Top Ventas de Ocasión</h2>
        <span class="badge-pill-tech">Los <?= count($top_ventas) ?> más buscados</span>
    </div>
    <p class="small mb-4" style="color: var(--text-muted);">Los modelos más buscados en el mercado de segunda mano, analizados bajo lupa mecánica.</p>
    <div class="row g-4 mb-5" id="top-ventas-grid">
        <?php foreach ($top_ventas as $tv): ?>
            <?php
                $tiene_rojo  = !empty($tv['motor_rojo']);
                $tiene_verde = !empty($tv['motor_verde']);
            ?>
            <div class="col-md-6 col-lg-4 top-ventas-item"
                 data-marca="<?= htmlspecialchars(strtolower($tv['marca'])) ?>"
                 data-modelo="<?= htmlspecialchars(strtolower($tv['modelo'])) ?>"
                 data-uso="<?= htmlspecialchars(strtolower($tv['uso_ideal'])) ?>">
                <div class="card card-editorial card-editorial-hover h-100 overflow-hidden">
                    <div class="position-relative">
                        <img
                            src="<?= htmlspecialchars($tv['imagen_url']) ?>"
                            class="card-img-editorial"
                            alt="<?= htmlspecialchars($tv['marca'] . ' ' . $tv['modelo']) ?>"
                        >
                        <span class="badge-ranking">#<?= (int)$tv['ranking_ventas'] ?> Top Ventas</span>
                    </div>
                    <div class="card-body d-flex flex-column p-3">
                        <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                            <span class="badge-pill-tech font-mono"><?= htmlspecialchars($tv['generacion']) ?></span>
                            <span class="badge-pill-tech"><?= htmlspecialchars($tv['uso_ideal']) ?></span>
                            <span class="badge-pill-tech ms-auto font-mono">★ <?= number_format($tv['puntuacion_general'], 1) ?></span>
                        </div>
                        <h3 class="h5 fw-bold mb-1" style="color: var(--text-primary);">
                            <?= htmlspecialchars($tv['marca'] . ' ' . $tv['modelo']) ?>
                        </h3>
                        <small class="font-mono mb-3 d-block" style="color: var(--text-muted);">
                            <?= (int)$tv['anio_inicio'] ?>–<?= (int)$tv['anio_fin'] ?>
                        </small>
                        <?php if ($tiene_rojo): ?>
                            <div class="p-2 rounded small mb-3 semaforo-rojo">
                                <strong>Muy vendido, pero evita:</strong> <?= htmlspecialchars($tv['motor_rojo']) ?>
                            </div>
                        <?php elseif ($tiene_verde): ?>
                            <div class="p-2 rounded small mb-3 semaforo-verde">
                                <strong>Apuesta segura en:</strong> <?= htmlspecialchars($tv['motor_verde']) ?>
                            </div>
                        <?php endif; ?>
                        <a href="ficha.php?id=<?= (int)$tv['id'] ?>" class="btn btn-editorial-primary btn-sm mt-auto">
                            Ver guía y checklist &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Listado de Modelos -->
<div class="container pb-5" style="max-width: 1060px;">
    <div id="catalogo-grid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($modelos)): ?>
            <div class="col-12 text-center py-5">
                <p class="fs-5" style="color: var(--text-muted);">No hay coches catalogados con ese filtro todavía.</p>
                <a href="index.php" class="btn btn-editorial-primary">Ver catálogo completo</a>
            </div>
        <?php else: ?>
            <?php foreach ($modelos as $m): ?>
                <div class="col catalogo-item"
                     data-marca="<?= htmlspecialchars(strtolower($m['marca'])) ?>"
                     data-modelo="<?= htmlspecialchars(strtolower($m['modelo'])) ?>"
                     data-uso="<?= htmlspecialchars(strtolower($m['uso_ideal'])) ?>">
                    <div class="card card-editorial card-editorial-hover h-100 overflow-hidden">
                        <!-- Imagen del vehículo -->
                        <img
                            src="<?= htmlspecialchars($m['imagen_url']) ?>"
                            class="card-img-editorial"
                            alt="<?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?>"
                        >

                        <div class="card-body d-flex flex-column p-3">
                            <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                                <span class="badge-pill-tech font-mono"><?= htmlspecialchars($m['generacion']) ?></span>
                                <span class="badge-pill-tech"><?= htmlspecialchars($m['uso_ideal']) ?></span>
                                <span class="badge-pill-tech ms-auto font-mono">★ <?= number_format($m['puntuacion_general'], 1) ?></span>
                            </div>

                            <h2 class="h5 fw-bold mb-1" style="color: var(--text-primary);">
                                <?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?>
                            </h2>
                            <small class="font-mono mb-3 d-block" style="color: var(--text-muted);">
                                <?= $m['anio_inicio'] ?>–<?= $m['anio_fin'] ?>
                            </small>

                            <p class="card-text small flex-grow-1" style="color: var(--text-secondary);">
                                <?= htmlspecialchars(mb_strimwidth($m['resumen_veredicto'], 0, 105, '…')) ?>
                            </p>

                            <a href="ficha.php?id=<?= $m['id'] ?>" class="btn btn-editorial-primary w-100 mt-3 btn-sm">
                                Ver guía y checklist &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Mensaje de "sin resultados" para el filtro live -->
    <div id="sin-resultados-live" class="text-center py-5 d-none">
        <p class="fs-5" style="color: var(--text-muted);">Ningún modelo coincide con tu búsqueda.</p>
    </div>
</div>

<!-- Filtrado instantáneo en cliente + filter dock glider -->
<script>
(function () {
    /* ── 1. Buscador live ─────────────────────────────────── */
    const input         = document.getElementById('buscador-live');
    const items         = document.querySelectorAll('.catalogo-item');
    const sinResultados = document.getElementById('sin-resultados-live');

    function filtrarPorTexto(q) {
        let visibles = 0;
        items.forEach(function (card) {
            const marca  = card.dataset.marca  || '';
            const modelo = card.dataset.modelo || '';
            const uso    = card.dataset.uso    || '';
            const coincide = !q || marca.includes(q) || modelo.includes(q) || uso.includes(q);
            card.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });
        sinResultados.classList.toggle('d-none', visibles > 0);
    }

    input.addEventListener('input', function () {
        filtrarPorTexto(this.value.toLowerCase().trim());
    });

    /* ── 2. Filter dock: glider deslizante ───────────────── */
    const dock       = document.getElementById('filterDock');
    const glider     = document.getElementById('filterGlider');
    const pills      = dock.querySelectorAll('.filter-pill-btn');
    const topGrid    = document.getElementById('top-ventas-grid');
    const topItems   = document.querySelectorAll('.top-ventas-item');

    function moveGlider(activeBtn) {
        glider.style.width     = activeBtn.offsetWidth + 'px';
        glider.style.transform = 'translateX(' + activeBtn.offsetLeft + 'px)';
    }

    function getActiveBtn() {
        return dock.querySelector('.filter-pill-btn.active') || pills[0];
    }

    document.addEventListener('DOMContentLoaded', function () {
        moveGlider(getActiveBtn());
    });

    window.addEventListener('resize', function () {
        moveGlider(getActiveBtn());
    });

    pills.forEach(function (btn) {
        btn.addEventListener('click', function () {
            pills.forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            moveGlider(btn);
            btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });

            const usoFiltro = btn.dataset.uso.toLowerCase();
            const esTopVentas = usoFiltro === '__top__';

            /* Mostrar/ocultar sección Top Ventas completa */
            if (topGrid) {
                const seccion = topGrid.closest('.container');
                if (seccion) seccion.style.display = esTopVentas ? '' : 'none';
            }

            /* Filtrar catálogo general */
            let visibles = 0;
            items.forEach(function (card) {
                let coincide;
                if (esTopVentas) {
                    /* En Top Ventas, mostrar solo los que están en el top */
                    const topIds = Array.from(topItems).map(function(ti) {
                        return ti.querySelector('a[href]') ? ti.querySelector('a[href]').getAttribute('href') : '';
                    });
                    const href = card.querySelector('a[href]') ? card.querySelector('a[href]').getAttribute('href') : '';
                    coincide = topIds.includes(href);
                } else if (!usoFiltro) {
                    coincide = true;
                } else {
                    coincide = card.dataset.uso === usoFiltro;
                }
                card.style.display = coincide ? '' : 'none';
                if (coincide) visibles++;
            });
            sinResultados.classList.toggle('d-none', visibles > 0);

            input.value = '';
        });
    });

    requestAnimationFrame(function () {
        moveGlider(getActiveBtn());
        /* Ocultar sección Top Ventas si no es el filtro activo al cargar */
        const activeUso = getActiveBtn().dataset.uso;
        if (activeUso !== '__top__' && topGrid) {
            const seccion = topGrid.closest('.container');
            if (seccion) seccion.style.display = '';
        }
    });
})();
</script>

</body>
</html>
