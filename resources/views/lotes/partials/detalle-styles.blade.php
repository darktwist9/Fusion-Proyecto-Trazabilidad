<style>
    :root { --primary-color: #2c5530; --secondary-color: #4a7c59; }
    .lote-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white; border-radius: 15px; padding: 25px; margin-bottom: 20px;
    }
    .lote-header h2 { margin: 0; font-weight: 700; }
    .estado-badge { padding: 8px 20px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; }
    .small-box {
        border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05); transition: transform 0.3s ease; margin-bottom: 20px;
    }
    .small-box:hover { transform: translateY(-2px); }
    .small-box .icon { font-size: 70px !important; }
    .small-box-green { background: linear-gradient(135deg, #28a745, #34ce57) !important; }
    .small-box-blue { background: linear-gradient(135deg, #17a2b8, #20c997) !important; }
    .small-box-yellow { background: linear-gradient(135deg, #ffc107, #ffca2c) !important; }
    .small-box-red { background: linear-gradient(135deg, #dc3545, #e74a3b) !important; }
    .lote-section-card {
        border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        border: none; margin-bottom: 20px;
    }
    .lote-section-nav .nav-link {
        border-radius: 8px; font-weight: 600; color: #6c757d; padding: 10px 18px; margin-right: 6px;
    }
    .lote-section-nav .nav-link.active {
        background: var(--primary-color); color: #fff;
    }
    .lote-section-nav .nav-link:not(.active):hover { background: #e8f5e9; color: var(--primary-color); }
    .info-table td { padding: 10px 0; border-bottom: 1px solid #f1f3f4; }
    .info-table td:first-child { font-weight: 600; color: #495057; width: 40%; }

    /* Panel «Datos del lote» */
    .lote-datos-panel {
        background: linear-gradient(160deg, #f8fbf8 0%, #fff 55%);
        border: 1px solid #e2ebe3;
        border-radius: 14px;
        padding: 1.25rem 1.35rem 1.1rem;
        box-shadow: 0 4px 18px rgba(44, 85, 48, 0.06);
    }
    .lote-datos-panel__header {
        display: flex;
        align-items: center;
        gap: .85rem;
        margin-bottom: 1.1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e8efe9;
    }
    .lote-datos-panel__header-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(44, 85, 48, 0.25);
    }
    .lote-datos-panel__title {
        margin: 0;
        font-weight: 700;
        font-size: 1.05rem;
        color: #1a2e1c;
    }
    .lote-datos-panel__subtitle {
        margin: .15rem 0 0;
        font-size: .78rem;
        color: #6b7c6e;
    }
    .lote-datos-panel__badges {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1rem;
    }
    .lote-datos-panel__badge {
        display: inline-flex;
        align-items: center;
        padding: .4rem .85rem;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 600;
        letter-spacing: .01em;
    }
    .lote-datos-panel__badge--cultivo {
        background: linear-gradient(135deg, #1e4620, #2c5530);
        color: #fff;
        box-shadow: 0 2px 8px rgba(44, 85, 48, 0.2);
    }
    .lote-datos-panel__badge--muted {
        background: #eef2ee;
        color: #6c757d;
    }
    .lote-datos-panel__badges .estado-badge { font-size: .82rem; }
    .lote-datos-panel__metrics {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .65rem;
        margin-bottom: 1rem;
    }
    .lote-datos-panel__metric {
        background: #fff;
        border: 1px solid #e8efe9;
        border-radius: 10px;
        padding: .65rem .75rem;
        text-align: center;
        transition: border-color .2s, box-shadow .2s;
    }
    .lote-datos-panel__metric:hover {
        border-color: #c5d9c8;
        box-shadow: 0 2px 8px rgba(44, 85, 48, 0.08);
    }
    .lote-datos-panel__metric-label {
        display: block;
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #7a8b7c;
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .lote-datos-panel__metric-value {
        display: block;
        font-size: 1.05rem;
        font-weight: 700;
        color: #1a2e1c;
        line-height: 1.2;
    }
    .lote-datos-panel__metric-value--sm { font-size: .88rem; }
    .lote-datos-panel__metric-value small { font-size: .75rem; font-weight: 600; color: #6b7c6e; }
    .lote-datos-panel__metric-hint {
        display: block;
        font-size: .68rem;
        font-weight: 500;
        color: #8a9a8c;
        margin-top: .15rem;
    }
    .lote-datos-panel__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .6rem;
        margin-bottom: .75rem;
    }
    .lote-datos-panel__item {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        background: #fff;
        border: 1px solid #edf2ed;
        border-radius: 10px;
        padding: .7rem .8rem;
    }
    .lote-datos-panel__item--wide { grid-column: 1 / -1; }
    .lote-datos-panel__item-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eef5ef;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .lote-datos-panel__item-body { min-width: 0; flex: 1; }
    .lote-datos-panel__item-label {
        display: block;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #8a9a8c;
        font-weight: 600;
        margin-bottom: .2rem;
    }
    .lote-datos-panel__item-value {
        display: block;
        font-size: .9rem;
        font-weight: 600;
        color: #2d3b2f;
        word-break: break-word;
    }
    .lote-datos-panel__traz-code {
        display: inline-block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .78rem;
        font-weight: 600;
        color: #9b2c5e;
        background: #fdf2f8;
        border: 1px dashed #f0c4d8;
        border-radius: 6px;
        padding: .25rem .55rem;
        letter-spacing: .02em;
    }
    .lote-datos-panel__geo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        background: linear-gradient(135deg, #f0f7f1, #fff);
        border: 1px solid #d4e5d6;
        border-radius: 10px;
        padding: .75rem 1rem;
    }
    .lote-datos-panel__geo-info {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }
    .lote-datos-panel__geo-info > i {
        font-size: 1.25rem;
        color: var(--primary-color);
        opacity: .85;
    }
    .lote-datos-panel__coords {
        display: block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .8rem;
        font-weight: 600;
        color: #9b2c5e;
    }
    .lote-datos-panel__map-btn {
        display: inline-flex;
        align-items: center;
        padding: .45rem 1rem;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        color: var(--primary-color);
        background: #fff;
        border: 2px solid var(--primary-color);
        text-decoration: none;
        transition: background .2s, color .2s, transform .15s;
        white-space: nowrap;
    }
    .lote-datos-panel__map-btn:hover {
        background: var(--primary-color);
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
    }
    @media (max-width: 575px) {
        .lote-datos-panel__metrics { grid-template-columns: 1fr; }
        .lote-datos-panel__grid { grid-template-columns: 1fr; }
    }
    .btn-action { border-radius: 8px; padding: 10px 20px; font-weight: 500; }
    #map { height: 420px; width: 100%; border-radius: 10px; border: 2px solid #e9ecef; }
    .timeline { position: relative; padding: 20px 0; }
    .timeline::before {
        content: ''; position: absolute; left: 20px; top: 0; bottom: 0; width: 3px;
        background: linear-gradient(to bottom, var(--primary-color), #e9ecef);
    }
    .timeline-item { position: relative; padding-left: 60px; padding-bottom: 25px; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-icon {
        position: absolute; left: 5px; width: 34px; height: 34px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; z-index: 1;
    }
    .timeline-icon.success { background: #28a745; }
    .timeline-icon.info { background: #17a2b8; }
    .timeline-icon.warning { background: #ffc107; color: #1a252f; }
    .timeline-icon.primary { background: var(--primary-color); }
    .timeline-content { background: #f8f9fc; border-radius: 10px; padding: 15px; border-left: 3px solid #dee2e6; }
    .timeline-content.success { border-left-color: #28a745; }
    .timeline-content.info { border-left-color: #17a2b8; }
    .timeline-content.warning { border-left-color: #ffc107; }
    .timeline-content.primary { border-left-color: var(--primary-color); }
    .timeline-date { font-size: 0.8rem; color: #6c757d; margin-bottom: 5px; }
    .timeline-title { font-weight: 600; color: #1a252f; margin-bottom: 5px; }
    .timeline-desc { font-size: 0.9rem; color: #495057; margin: 0; }
    .timeline-user { font-size: 0.8rem; color: #6c757d; margin-top: 5px; }
    .empty-timeline { text-align: center; padding: 40px; color: #6c757d; }
    .empty-timeline i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
</style>
