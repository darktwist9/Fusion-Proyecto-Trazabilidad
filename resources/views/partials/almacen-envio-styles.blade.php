<style>
    .almacen-section {
        background: #f8f9fc;
        border-radius: 12px;
        padding: 20px;
        border: 2px dashed #6c757d;
        margin-top: 0;
        transition: all 0.3s ease;
    }
    .almacen-section.active {
        border-color: #28a745;
        border-style: solid;
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
    }
    .almacen-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        border: 2px solid #dee2e6;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .almacen-card:hover {
        border-color: #28a745;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
    }
    .almacen-card.selected {
        border-color: #28a745;
        background: #d4edda;
    }
    .almacen-card .almacen-icon {
        font-size: 1.8rem;
        color: #6c757d;
        width: 45px;
    }
    .almacen-card.selected .almacen-icon {
        color: #28a745;
    }
    .almacen-card .almacen-nombre {
        font-weight: 600;
        color: #1a252f;
    }
    .almacen-card .almacen-tipo {
        font-size: 0.8rem;
        color: #6c757d;
    }
    .capacidad-bar {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 8px;
    }
    .capacidad-bar .fill {
        height: 100%;
        border-radius: 3px;
    }
    .capacidad-bar .fill.low { background: #28a745; }
    .capacidad-bar .fill.medium { background: #ffc107; }
    .capacidad-bar .fill.high { background: #dc3545; }
    .guia-campo {
        background: #f8fbf8;
        border-left: 3px solid #2c5530;
        border-radius: 0 8px 8px 0;
        padding: 0.65rem 0.85rem;
        margin-bottom: 0.75rem;
        font-size: 0.85rem;
        color: #495057;
    }
    .guia-campo strong { color: #2c5530; }
    .almacen-section-extra {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(40, 167, 69, 0.35);
        border-radius: 10px;
        padding: 1rem 1.15rem;
        margin-top: 1rem;
    }
    .almacen-section-extra .form-control {
        background: #fff;
        border: 2px solid #dee2e6;
    }
    .almacen-section-extra .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.15rem rgba(40, 167, 69, 0.2);
    }
    .almacen-section-extra label {
        color: #1a252f;
    }
    .almacen-section-actions {
        margin-top: 1rem;
        padding-top: 0.25rem;
    }
    .almacen-modal-tabs .nav-link {
        font-weight: 600;
        color: #495057;
        border-radius: 8px 8px 0 0;
    }
    .almacen-modal-tabs .nav-link.active {
        color: #2c5530;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    .almacen-modal-lista {
        max-height: 420px;
        overflow-y: auto;
    }
    .almacen-modal-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        margin-bottom: .5rem;
        cursor: pointer;
        transition: all .2s ease;
        background: #fff;
    }
    .almacen-modal-item:hover {
        border-color: #28a745;
        box-shadow: 0 2px 8px rgba(40, 167, 69, .15);
    }
    .almacen-modal-item.is-selected {
        border-color: #28a745;
        background: #d4edda;
    }
    .almacen-modal-item .almacen-modal-icon {
        width: 40px;
        text-align: center;
        font-size: 1.4rem;
        color: #6c757d;
    }
    .almacen-modal-item.is-selected .almacen-modal-icon { color: #28a745; }
    .almacen-modal-item .almacen-modal-body { flex: 1; min-width: 0; }
    .almacen-modal-item .almacen-modal-nombre {
        font-weight: 600;
        color: #1a252f;
    }
    .almacen-modal-item .almacen-modal-meta {
        font-size: .8rem;
        color: #6c757d;
    }
    .almacen-mapa-modal {
        height: 420px;
        min-height: 420px;
        border-radius: 10px;
        border: 1px solid #dee2e6;
    }
    .almacen-mapa-marker { background: transparent !important; border: none !important; }
    .almacen-mapa-pin {
        width: 32px; height: 32px; border-radius: 50%; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.35); cursor: pointer;
        transition: transform .12s ease;
        background: #28a745;
    }
    .almacen-mapa-pin:hover { transform: scale(1.12); }
    .almacen-mapa-pin.is-selected {
        box-shadow: 0 0 0 3px #fbbf24, 0 2px 8px rgba(0,0,0,.35);
    }
    .leaflet-tooltip.almacen-mapa-tooltip {
        background: #1e293b; color: #fff; border: 0; border-radius: 8px;
        font-size: .8rem; font-weight: 600; padding: .35rem .65rem;
        box-shadow: 0 4px 12px rgba(0,0,0,.2);
    }
    .leaflet-tooltip.almacen-mapa-tooltip::before { border-top-color: #1e293b; }
    .almacen-mapa-flash {
        position: absolute; z-index: 1000; top: 10px; left: 50%; transform: translateX(-50%);
        background: #1e293b; color: #fff; padding: .4rem .85rem; border-radius: 8px;
        font-size: .8rem; display: none; pointer-events: none;
    }
</style>
