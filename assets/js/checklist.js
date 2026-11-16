document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.chk-inspeccion');
    const panel = document.getElementById('panel-veredicto');
    const icono = document.getElementById('icono-veredicto');
    const titulo = document.getElementById('titulo-veredicto');
    const desc = document.getElementById('desc-veredicto');

    // Si la página no tiene el panel de inspección, salimos limpiamente
    if (!panel) return;

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

        // Evaluación de estado
        if (criticos > 0) {
            panel.className = 'alert alert-danger d-flex align-items-center gap-3 mb-4';
            icono.textContent = '⛔';
            titulo.textContent = 'COMPRA DESACONSEJADA (Riesgo Crítico)';
            desc.textContent = `Has detectado ${criticos} fallo(s) crítico(s). Salvo descuento de miles de euros y presupuesto para taller, busca otra unidad.`;
        } else if (graves >= 2) {
            panel.className = 'alert alert-warning d-flex align-items-center gap-3 mb-4';
            icono.textContent = '⚠️';
            titulo.textContent = 'Precaución: Gastos inminentes';
            desc.textContent = `Has marcado ${graves} fallos graves. Utilízalos como argumento para rebajar el precio entre 300€ y 800€.`;
        } else if (graves === 1 || leves > 0) {
            panel.className = 'alert alert-info d-flex align-items-center gap-3 mb-4';
            icono.textContent = '🟡';
            titulo.textContent = 'Desgaste moderado / Negociable';
            desc.textContent = 'Detalles habituales por edad y uso. No comprometen la vida del motor a corto plazo.';
        } else {
            panel.className = 'alert alert-success d-flex align-items-center gap-3 mb-4';
            icono.textContent = '🟢';
            titulo.textContent = 'Unidad aparentemente sana';
            desc.textContent = 'No has marcado ningún fallo crítico todavía. Sigue revisando los puntos.';
        }
    }

    checkboxes.forEach(chk => chk.addEventListener('change', calcularRiesgo));
});