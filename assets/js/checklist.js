document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.chk-inspeccion');
    const panel = document.getElementById('panel-veredicto');
    const icono = document.getElementById('icono-veredicto');
    const titulo = document.getElementById('titulo-veredicto');
    const desc = document.getElementById('desc-veredicto');
    const btnCopiar = document.getElementById('btn-copiar-informe');
    const btnReset = document.getElementById('btn-reset-checklist');

    if (!panel) return;

    // Clave única en localStorage para este modelo concreto
    const modeloId = panel.dataset.modeloId;
    const modeloNombre = panel.dataset.modeloNombre;
    const storageKey = `cocheseguro_check_${modeloId}`;

    // 1. Cargar estado previo guardado (si existe)
    function cargarProgreso() {
        const guardado = localStorage.getItem(storageKey);
        if (!guardado) return;

        const idsMarcados = JSON.parse(guardado);
        checkboxes.forEach(chk => {
            if (idsMarcados.includes(chk.id)) {
                chk.checked = true;
            }
        });
    }

    // 2. Guardar estado actual en localStorage
    function guardarProgreso() {
        const marcados = [];
        checkboxes.forEach(chk => {
            if (chk.checked) marcados.push(chk.id);
        });
        localStorage.setItem(storageKey, JSON.stringify(marcados));
    }

    // 3. Evaluar riesgo y actualizar interfaz
    function calcularRiesgo() {
        let criticos = 0;
        let graves = 0;
        let leves = 0;

        checkboxes.forEach(chk => {
            const itemBox = chk.closest('.checklist-item');
            if (chk.checked) {
                const gravedad = chk.dataset.gravedad;
                if (gravedad === 'Critico') criticos++;
                else if (gravedad === 'Grave') graves++;
                else leves++;

                itemBox.classList.add('border-danger', 'bg-light');
            } else {
                itemBox.classList.remove('border-danger', 'bg-light');
            }
        });

        if (criticos > 0) {
            panel.className = 'alert alert-danger d-flex align-items-center gap-3 mb-3';
            icono.textContent = '⛔';
            titulo.textContent = 'COMPRA DESACONSEJADA (Riesgo Crítico)';
            desc.textContent = `Has detectado ${criticos} fallo(s) crítico(s). Salvo descuento de miles de euros y presupuesto para taller, busca otra unidad.`;
        } else if (graves >= 2) {
            panel.className = 'alert alert-warning d-flex align-items-center gap-3 mb-3';
            icono.textContent = '⚠️';
            titulo.textContent = 'Precaución: Gastos inminentes';
            desc.textContent = `Has marcado ${graves} fallos graves. Utilízalos como argumento para rebajar el precio entre 300€ y 800€.`;
        } else if (graves === 1 || leves > 0) {
            panel.className = 'alert alert-info d-flex align-items-center gap-3 mb-3';
            icono.textContent = '🟡';
            titulo.textContent = 'Desgaste moderado / Negociable';
            desc.textContent = 'Detalles habituales por edad y uso. No comprometen la vida del motor a corto plazo.';
        } else {
            panel.className = 'alert alert-success d-flex align-items-center gap-3 mb-3';
            icono.textContent = '🟢';
            titulo.textContent = 'Unidad aparentemente sana';
            desc.textContent = 'No has marcado ningún fallo crítico todavía. Sigue revisando los puntos.';
        }

        guardarProgreso();
    }

    // 4. Generar informe y copiar al portapapeles
    if (btnCopiar) {
        btnCopiar.addEventListener('click', async () => {
            const fallosDetectados = [];

            checkboxes.forEach(chk => {
                if (chk.checked) {
                    const label = document.querySelector(`label[for="${chk.id}"]`);
                    const textoPunto = label.querySelector('.fw-semibold').textContent.trim();
                    const gravedad = chk.dataset.gravedad;
                    fallosDetectados.push(`- [${gravedad.toUpperCase()}] ${textoPunto}`);
                }
            });

            let informe = `🚗 *Informe de inspección: ${modeloNombre}*\n`;
            informe += `Estado: ${titulo.textContent}\n\n`;

            if (fallosDetectados.length > 0) {
                informe += `*Puntos detectados a revisar/negociar:*\n${fallosDetectados.join('\n')}`;
            } else {
                informe += `✅ Sin fallos detectados en los puntos críticos revisados.`;
            }

            try {
                await navigator.clipboard.writeText(informe);
                const textoOriginal = btnCopiar.textContent;
                btnCopiar.textContent = '✅ ¡Copiado al portapapeles!';
                btnCopiar.classList.replace('btn-dark', 'btn-success');

                setTimeout(() => {
                    btnCopiar.textContent = textoOriginal;
                    btnCopiar.classList.replace('btn-success', 'btn-dark');
                }, 2000);
            } catch (err) {
                alert('No se pudo copiar automáticamente. Revisa los permisos del navegador.');
            }
        });
    }

    // 5. Botón para limpiar marcas
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            checkboxes.forEach(chk => chk.checked = false);
            localStorage.removeItem(storageKey);
            calcularRiesgo();
        });
    }

    // Eventos en cada checkbox
    checkboxes.forEach(chk => chk.addEventListener('change', calcularRiesgo));

    // Inicialización al abrir la página
    cargarProgreso();
    calcularRiesgo();
});