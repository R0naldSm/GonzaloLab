/**
 * GonzaloLabs — charts.js
 * Fábrica de gráficos con Chart.js 4.x para dashboard y reportes.
 * Requiere: Chart.js cargado antes de este script.
 *
 * Uso en vistas PHP:
 *   <script src="/public/assets/js/charts.js"></script>
 *   <script>
 *     GL.Charts.barLine('chartDia', labels, ordenes, ingresos);
 *   </script>
 */

'use strict';

// Colores del design system GonzaloLabs
const GL_COLORS = {
    primary:   '#06b6d4',
    secondary: '#3b82f6',
    success:   '#10b981',
    warning:   '#f59e0b',
    danger:    '#ef4444',
    purple:    '#8b5cf6',
    gray:      '#94a3b8',
    primaryBg: 'rgba(6,182,212,.12)',
    secondaryBg:'rgba(59,130,246,.12)',
    successBg: 'rgba(16,185,129,.12)',
    purpleBg:  'rgba(139,92,246,.12)',
};

// Opciones base reutilizables
const BASE_FONT = { family: "'Plus Jakarta Sans', Inter, sans-serif", size: 11 };

const BASE_GRID = {
    color: '#f1f5f9',
    borderColor: '#e2e8f0',
};

const BASE_LEGEND = {
    position: 'top',
    labels: { font: BASE_FONT, padding: 16, usePointStyle: true, pointStyleWidth: 8 },
};

// Destruir chart previo en el mismo canvas si existe
function destroyIfExists(id) {
    const existing = Chart.getChart(id);
    if (existing) existing.destroy();
}

// ─────────────────────────────────────────────────────────────
// CHART 1 — Barras + Línea (actividad diaria: órdenes/ingresos)
// Usado en: reportes/dashboard.php, dashboard/index.php
// ─────────────────────────────────────────────────────────────

/**
 * @param {string}   canvasId
 * @param {string[]} labels    - Fechas o etiquetas eje X
 * @param {number[]} ordenes   - Datos barras (eje izquierdo)
 * @param {number[]} ingresos  - Datos línea (eje derecho, $)
 */
function chartBarLine(canvasId, labels, ordenes, ingresos) {
    destroyIfExists(canvasId);
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Órdenes',
                    data: ordenes,
                    backgroundColor: 'rgba(6,182,212,.35)',
                    borderColor: GL_COLORS.primary,
                    borderWidth: 1.5,
                    borderRadius: 5,
                    yAxisID: 'y',
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Ingresos ($)',
                    data: ingresos,
                    borderColor: GL_COLORS.purple,
                    backgroundColor: GL_COLORS.purpleBg,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: GL_COLORS.purple,
                    yAxisID: 'y1',
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: BASE_LEGEND,
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const val = ctx.raw;
                            return ctx.datasetIndex === 1
                                ? ` $${parseFloat(val).toFixed(2)}`
                                : ` ${val} orden(es)`;
                        },
                    },
                },
            },
            scales: {
                x: { grid: BASE_GRID, ticks: { font: BASE_FONT } },
                y: {
                    beginAtZero: true,
                    grid: BASE_GRID,
                    ticks: { font: BASE_FONT, precision: 0 },
                    title: { display: true, text: 'Órdenes', font: BASE_FONT },
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { display: false },
                    ticks: {
                        font: BASE_FONT,
                        callback: (v) => '$' + v.toLocaleString('es-EC', { minimumFractionDigits: 0 }),
                    },
                    title: { display: true, text: 'Ingresos ($)', font: BASE_FONT },
                },
            },
        },
    });
}

// ─────────────────────────────────────────────────────────────
// CHART 2 — Dona (distribución por estado de órdenes)
// Usado en: reportes/dashboard.php
// ─────────────────────────────────────────────────────────────

/**
 * @param {string}   canvasId
 * @param {string[]} labels
 * @param {number[]} valores
 * @param {string[]} [colores]
 */
function chartDona(canvasId, labels, valores, colores) {
    destroyIfExists(canvasId);
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const defaultColors = [
        '#a5f3fc','#fde68a','#c4b5fd','#93c5fd','#86efac','#fca5a5',
        '#d9f99d','#fbcfe8','#fed7aa',
    ];

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: valores,
                backgroundColor: colores || defaultColors,
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: BASE_FONT, padding: 10, usePointStyle: true },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct   = total ? Math.round(ctx.raw / total * 100) : 0;
                            return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
}

// ─────────────────────────────────────────────────────────────
// CHART 3 — Barras horizontales (top exámenes)
// Usado en: reportes/estadisticas.php tipo=examenes
// ─────────────────────────────────────────────────────────────

/**
 * @param {string}   canvasId
 * @param {string[]} nombres    - Nombres de exámenes
 * @param {number[]} totales    - Veces solicitados
 */
function chartBarrasHorizontal(canvasId, nombres, totales) {
    destroyIfExists(canvasId);
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: nombres,
            datasets: [{
                label: 'Veces solicitado',
                data: totales,
                backgroundColor: GL_COLORS.primaryBg,
                borderColor: GL_COLORS.primary,
                borderWidth: 1.5,
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.raw} solicitudes`,
                    },
                },
            },
            scales: {
                x: { beginAtZero: true, grid: BASE_GRID, ticks: { font: BASE_FONT, precision: 0 } },
                y: { grid: { display: false }, ticks: { font: BASE_FONT } },
            },
        },
    });
}

// ─────────────────────────────────────────────────────────────
// CHART 4 — Línea de ingresos acumulados
// Usado en: reportes/estadisticas.php tipo=ingresos
// ─────────────────────────────────────────────────────────────

/**
 * @param {string}   canvasId
 * @param {string[]} dias
 * @param {number[]} totales    - Total facturado por día
 * @param {number[]} cobrados   - Total cobrado (pagado) por día
 */
function chartIngresosLinea(canvasId, dias, totales, cobrados) {
    destroyIfExists(canvasId);
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: dias,
            datasets: [
                {
                    label: 'Total facturado ($)',
                    data: totales,
                    borderColor: GL_COLORS.secondary,
                    backgroundColor: GL_COLORS.secondaryBg,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                },
                {
                    label: 'Cobrado ($)',
                    data: cobrados,
                    borderColor: GL_COLORS.success,
                    backgroundColor: GL_COLORS.successBg,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: BASE_LEGEND,
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: $${parseFloat(ctx.raw).toFixed(2)}`,
                    },
                },
            },
            scales: {
                x: { grid: BASE_GRID, ticks: { font: BASE_FONT } },
                y: {
                    beginAtZero: true,
                    grid: BASE_GRID,
                    ticks: {
                        font: BASE_FONT,
                        callback: (v) => '$' + v.toLocaleString('es-EC'),
                    },
                },
            },
        },
    });
}

// ─────────────────────────────────────────────────────────────
// CHART 5 — Sparkline mini (para stat-cards del dashboard)
// ─────────────────────────────────────────────────────────────

/**
 * @param {string}   canvasId
 * @param {number[]} data
 * @param {string}   [color]
 */
function chartSparkline(canvasId, data, color = GL_COLORS.primary) {
    destroyIfExists(canvasId);
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map((_, i) => i),
            datasets: [{
                data,
                borderColor: color,
                backgroundColor: color + '22',
                borderWidth: 1.5,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
            }],
        },
        options: {
            responsive: false,
            animation: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false } },
        },
    });
}

// Exportar
window.GL = window.GL || {};
window.GL.Charts = {
    barLine:             chartBarLine,
    dona:                chartDona,
    barrasHorizontal:    chartBarrasHorizontal,
    ingresosLinea:       chartIngresosLinea,
    sparkline:           chartSparkline,
};