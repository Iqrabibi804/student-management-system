/**
 * dashboard.js v2 — Charts using CHART_DATA object (no PHP-in-heredoc bug)
 document
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    if (typeof CHART_DATA === 'undefined') return;
    initCourseChart();
    initMonthlyChart();
    initStatusChart();
});

function chartColors() {
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    return {
        grid:  dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)',
        ticks: dark ? '#475569' : '#94A3B8',
        tooltipBg:    dark ? '#1E293B' : '#FFFFFF',
        tooltipTitle: dark ? '#F1F5F9' : '#0F172A',
        tooltipBody:  dark ? '#94A3B8' : '#64748B',
        tooltipBorder:dark ? '#334155' : '#E2E8F0',
    };
}

Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

const PALETTE = ['#6366F1','#06B6D4','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#F97316'];

/* ── Bar Chart ──────────────────────────────────────── */
function initCourseChart() {
    const ctx = document.getElementById('courseChart');
    if (!ctx) return;
    const c = chartColors();

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: CHART_DATA.courseLabels,
            datasets: [{
                label: 'Students',
                data: CHART_DATA.courseValues,
                backgroundColor: CHART_DATA.courseLabels.map((_, i) => PALETTE[i % PALETTE.length] + 'CC'),
                borderColor:     CHART_DATA.courseLabels.map((_, i) => PALETTE[i % PALETTE.length]),
                borderWidth: 1.5,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: tooltipStyle(c, v => ` ${v} student${v !== 1 ? 's' : ''}`)
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: c.ticks, font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: c.grid }, ticks: { color: c.ticks, font: { size: 11 }, stepSize: 1 } }
            }
        }
    });
}

/* ── Line Chart ─────────────────────────────────────── */
function initMonthlyChart() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    const c = chartColors();
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Registrations',
                data: CHART_DATA.monthlyValues,
                borderColor: '#6366F1',
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    g.addColorStop(0, 'rgba(99,102,241,0.18)');
                    g.addColorStop(1, 'rgba(99,102,241,0)');
                    return g;
                },
                borderWidth: 2.5, fill: true, tension: 0.42,
                pointBackgroundColor: '#6366F1',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2.5,
                pointRadius: 5, pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: tooltipStyle(c, v => ` ${v} registration${v !== 1 ? 's' : ''}`)
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: c.ticks, font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: c.grid }, ticks: { color: c.ticks, font: { size: 11 }, stepSize: 1 } }
            }
        }
    });
}

/* ── Donut Chart ────────────────────────────────────── */
function initStatusChart() {
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive'],
            datasets: [{
                data: CHART_DATA.statusValues,
                backgroundColor: ['#10B981', '#94A3B8'],
                borderColor: ['#059669', '#64748B'],
                borderWidth: 1.5,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: false, maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` }
                }
            }
        }
    });
}

/* ── Tooltip helper ─────────────────────────────────── */
function tooltipStyle(c, labelFn) {
    return {
        backgroundColor: c.tooltipBg,
        titleColor: c.tooltipTitle,
        bodyColor: c.tooltipBody,
        borderColor: c.tooltipBorder,
        borderWidth: 1,
        padding: 12,
        cornerRadius: 10,
        callbacks: { label: ctx => labelFn(ctx.parsed.y ?? ctx.parsed) }
    };
}
