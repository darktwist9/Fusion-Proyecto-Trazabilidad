<style>
/* Ruta en tiempo real — colores por trayecto, tono ejecutivo (no neón) */
:root {
    --rt-border: #e2e8f0;
    --rt-muted: #64748b;
    --rt-text: #1e293b;
    --rt-logistica: #2c5530;
    --rt-logistica-soft: #f0f7f1;
}

[data-sim-tipo="agricola"],
.rt-wrap--agricola {
    --rt-tipo-color: #2c5530;
    --rt-tipo-color-mid: #3d6b42;
    --rt-tipo-soft: #f0f7f1;
    --rt-tipo-border: #d1e7d4;
}
[data-sim-tipo="planta_mayorista"],
.rt-wrap--planta_mayorista {
    --rt-tipo-color: #6d28d9;
    --rt-tipo-color-mid: #7c3aed;
    --rt-tipo-soft: #f5f3ff;
    --rt-tipo-border: #ddd6fe;
}
[data-sim-tipo="distribucion"],
.rt-wrap--distribucion {
    --rt-tipo-color: #c2410c;
    --rt-tipo-color-mid: #ea580c;
    --rt-tipo-soft: #fff7ed;
    --rt-tipo-border: #fed7aa;
}
.rt-wrap--default {
    --rt-tipo-color: #1d4ed8;
    --rt-tipo-color-mid: #2563eb;
    --rt-tipo-soft: #eff6ff;
    --rt-tipo-border: #bfdbfe;
}

.sim-vivo-head {
    background: #fff;
    border: 1px solid var(--rt-border);
    border-left: 4px solid var(--rt-tipo-color, var(--rt-logistica));
    border-radius: 8px;
    padding: .9rem 1.1rem;
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem 1rem;
    box-shadow: 0 1px 4px rgba(15, 23, 42, .04);
}
.sim-vivo-head__main { min-width: 0; flex: 1; }
.sim-vivo-head__code {
    display: block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 1rem;
    font-weight: 700;
    color: var(--rt-text);
    letter-spacing: .01em;
}
.sim-vivo-head__label {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--rt-muted);
    margin-bottom: .15rem;
}
.sim-vivo-head__tipo {
    display: inline-block;
    margin-top: .35rem;
    font-size: .78rem;
    font-weight: 600;
    color: var(--rt-muted);
}
.sim-vivo-head__tipo i { color: var(--rt-tipo-color, var(--rt-logistica)); margin-right: .25rem; }
.sim-vivo-head__ruta {
    margin-top: .55rem;
    padding-top: .55rem;
    border-top: 1px solid var(--rt-border);
}
.sim-vivo-ruta-flow {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    gap: .5rem;
    align-items: start;
}
@media (max-width: 767px) {
    .sim-vivo-ruta-flow { grid-template-columns: 1fr; }
    .sim-vivo-ruta-flecha { display: none; }
}
.sim-vivo-ruta-punto {
    font-size: .82rem;
    font-weight: 600;
    line-height: 1.4;
    color: var(--rt-text);
}
.sim-vivo-ruta-punto__lbl {
    display: block;
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--rt-muted);
    margin-bottom: .15rem;
}
.sim-vivo-ruta-punto i { display: none; }
.sim-vivo-ruta-punto--origen .sim-vivo-ruta-punto__lbl { color: #2c5530; }
.sim-vivo-ruta-punto--destino .sim-vivo-ruta-punto__lbl { color: #9f1239; }
.sim-vivo-ruta-flecha {
    color: var(--rt-muted);
    font-size: .85rem;
    padding-top: 1.1rem;
    text-align: center;
}
.sim-vivo-head__actions { flex-shrink: 0; }
.sim-live-badge.sim-live--espera {
    background: #fffbeb !important;
    color: #92400e !important;
    border: 1px solid #fde68a;
}
.sim-live-pct.sim-live--espera { color: #b45309; }
.sim-live-bar.sim-live--espera { background: #d97706 !important; }

.rt-page-head {
    background: #fff;
    border: 1px solid var(--rt-border);
    border-top: 3px solid var(--rt-logistica);
    border-radius: 10px;
    padding: 1rem 1.15rem;
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: .75rem 1rem;
}
.rt-page-head__title { font-size: .95rem; font-weight: 700; color: var(--rt-text); margin: 0; }
.rt-page-head__subtitle { font-size: .82rem; color: var(--rt-muted); margin: .15rem 0 0; }
.rt-page-head__actions { display: flex; flex-wrap: wrap; gap: .5rem .75rem; }

.rt-info-banner {
    background: var(--rt-logistica-soft);
    border: 1px solid var(--rt-tipo-border, #d1e7d4);
    border-left: 4px solid var(--rt-logistica);
    border-radius: 8px;
    padding: .75rem 1rem;
    color: var(--rt-text);
    font-size: .86rem;
    margin-bottom: 1rem;
}
.rt-info-banner i { color: var(--rt-logistica); }

.rt-leyenda-trayectos {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem .75rem;
    margin-bottom: 1rem;
}
.rt-leyenda-item {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .78rem;
    color: var(--rt-muted);
    padding: .35rem .65rem;
    background: #fff;
    border: 1px solid var(--rt-border);
    border-radius: 999px;
}
.rt-leyenda-item__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.sim-live-panel {
    border: 1px solid var(--rt-border);
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
    background: #fff;
}
.sim-live-panel .card-header {
    background: var(--rt-tipo-soft, #f8fafc);
    border-bottom: 1px solid var(--rt-tipo-border, var(--rt-border));
    padding: .85rem 1rem;
}
.sim-live-panel .card-title { font-size: .92rem; color: var(--rt-text); }
.sim-live-panel .card-title i { color: var(--rt-tipo-color, var(--rt-logistica)); }

.sim-live-badge {
    font-weight: 600;
    font-size: .72rem;
    letter-spacing: .02em;
    padding: .35rem .65rem;
    border-radius: 999px;
}
.sim-live-badge.sim-live--activo {
    background: var(--rt-tipo-soft, #eff6ff) !important;
    color: var(--rt-tipo-color, #1d4ed8) !important;
    border: 1px solid var(--rt-tipo-border, #bfdbfe);
}
.sim-live-badge.sim-live--completado {
    background: #f0fdf4 !important;
    color: #15803d !important;
    border: 1px solid #bbf7d0;
}
.sim-live-pct {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--rt-tipo-color, var(--rt-logistica));
}
.sim-live-pct.sim-live--completado { color: #15803d; }
.sim-live-bar {
    background: var(--rt-tipo-color-mid, var(--rt-tipo-color, var(--rt-logistica))) !important;
    border-radius: 999px;
}
.sim-live-bar.sim-live--completado { background: #16a34a !important; }
.sim-live-panel .progress { background: #e2e8f0; border-radius: 999px; }
.sim-live-eta { color: var(--rt-muted) !important; font-size: .84rem; }

.rt-live-card {
    border: 1px solid var(--rt-border);
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
    overflow: hidden;
}
.rt-live-card .card-header {
    background: linear-gradient(180deg, var(--rt-logistica-soft) 0%, #fff 100%);
    border-bottom: 1px solid var(--rt-border);
}
.rt-live-card .card-title { color: var(--rt-text); font-size: .95rem; }
.rt-live-card .card-title i { color: var(--rt-logistica); }
.rt-live-card .sim-lista-total {
    background: var(--rt-logistica) !important;
    color: #fff !important;
    font-weight: 700;
    border-radius: 999px;
    min-width: 1.5rem;
}

.rt-filtros-card {
    border: 1px solid var(--rt-border);
    border-radius: 12px;
    box-shadow: 0 1px 6px rgba(15, 23, 42, .04);
}

.rt-tipo-pill,
.rt-estado-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .7rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .02em;
    white-space: nowrap;
    line-height: 1.2;
    color: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
}
.rt-estado-pill {
    color: #0369a1 !important;
    background: #e0f2fe !important;
    border: 1px solid #bae6fd;
    box-shadow: none;
}
.rt-estado-pill--espera {
    color: #92400e !important;
    background: #fffbeb !important;
    border-color: #fde68a;
}
.rt-live-pct { font-weight: 800; }

.rt-btn-mapa {
    background: var(--rt-logistica);
    border-color: var(--rt-logistica);
    color: #fff;
}
.rt-btn-mapa:hover {
    background: #234829;
    border-color: #234829;
    color: #fff;
}

#mapaSimulacionVivo {
    height: 480px;
    width: 100%;
    min-height: 420px;
    border-radius: 10px;
    border: 1px solid var(--rt-border);
    z-index: 1;
}
[data-sim-tipo="agricola"] .sim-camion-pin { background: #2c5530 !important; }
[data-sim-tipo="planta_mayorista"] .sim-camion-pin { background: #7c3aed !important; }
[data-sim-tipo="distribucion"] .sim-camion-pin { background: #ea580c !important; }
[data-sim-tipo="agricola"] .sim-ruta-pendiente { stroke: #3d6b42 !important; }
[data-sim-tipo="agricola"] .sim-ruta-recorrida { stroke: #2c5530 !important; }
[data-sim-tipo="planta_mayorista"] .sim-ruta-pendiente { stroke: #8b5cf6 !important; }
[data-sim-tipo="planta_mayorista"] .sim-ruta-recorrida { stroke: #6d28d9 !important; }
[data-sim-tipo="distribucion"] .sim-ruta-pendiente { stroke: #fb923c !important; }
[data-sim-tipo="distribucion"] .sim-ruta-recorrida { stroke: #ea580c !important; }
</style>
