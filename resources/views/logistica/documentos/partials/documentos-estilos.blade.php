<style>
/* Paleta ejecutiva AgroFusion */
.log-doc-wrap { padding: 0 .15rem 1.5rem; max-width: 1100px; margin: 0 auto; }

.log-doc-hero {
    display: flex; align-items: flex-start; gap: 1rem;
    background: linear-gradient(135deg, #1e3a22 0%, #2c5530 55%, #3d6b45 100%);
    border-radius: 14px; padding: 1.25rem 1.4rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 8px 28px rgba(30, 58, 34, .22);
    color: #fff; overflow: hidden; position: relative;
}
.log-doc-hero::after {
    content: ''; position: absolute; right: -30px; top: -30px;
    width: 140px; height: 140px; border-radius: 50%;
    background: rgba(255,255,255,.06); pointer-events: none;
}
.log-doc-hero__icon {
    width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #d4e8d6;
}
.log-doc-hero__body { flex: 1; min-width: 0; position: relative; z-index: 1; }
.log-doc-hero__eyebrow {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; color: rgba(255,255,255,.72); margin: 0 0 .3rem;
}
.log-doc-hero__title {
    font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0 0 .4rem;
}
.log-doc-hero__text {
    font-size: .86rem; color: rgba(255,255,255,.88); margin: 0;
    line-height: 1.55; max-width: 680px;
}

.log-doc-metrics { margin-bottom: 1.15rem; }
.log-doc-metric {
    background: #fff; border-radius: 12px; padding: .95rem 1.05rem;
    height: 100%; display: flex; align-items: center; gap: .8rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 3px 14px rgba(15, 23, 42, .06);
    border-left: 4px solid var(--doc-accent, #2c5530);
}
.log-doc-metric--forest { --doc-accent: #2c5530; }
.log-doc-metric--navy   { --doc-accent: #1e3a5f; }
.log-doc-metric--bronze { --doc-accent: #9a7b4f; }
.log-doc-metric__icon {
    width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1rem;
}
.log-doc-metric--forest .log-doc-metric__icon { background: #e8f3ea; color: #1e4620; }
.log-doc-metric--navy   .log-doc-metric__icon { background: #e8eef5; color: #1e3a5f; }
.log-doc-metric--bronze .log-doc-metric__icon { background: #f5f0e8; color: #7a6240; }
.log-doc-metric__val { font-size: 1.45rem; font-weight: 800; color: #0f172a; line-height: 1; }
.log-doc-metric__lbl { font-size: .72rem; color: #64748b; margin-top: .2rem; font-weight: 600; }

.log-doc-card {
    background: #fff; border: 1px solid #dde3ea; border-radius: 14px;
    overflow: hidden; box-shadow: 0 4px 20px rgba(15, 23, 42, .07);
    margin-bottom: 1rem;
}
.log-doc-card__head {
    padding: .95rem 1.25rem;
    background: linear-gradient(90deg, #f4f8f5 0%, #fff 100%);
    border-bottom: 1px solid #dce8df;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
}
.log-doc-card__title {
    font-size: .98rem; font-weight: 800; color: #1e3a22; margin: 0;
    display: flex; align-items: center; gap: .55rem; flex: 1;
}
.log-doc-card__title::before {
    content: ''; display: block; width: 4px; height: 22px;
    background: #2c5530; border-radius: 2px; flex-shrink: 0;
}
.log-doc-card__count {
    font-size: .72rem; font-weight: 700; color: #2c5530;
    background: #e8f3ea; border: 1px solid #b8d4bc; border-radius: 999px;
    padding: .28rem .7rem;
}

.log-doc-filtros {
    padding: 1rem 1.25rem;
    background: linear-gradient(180deg, #f8faf9 0%, #f4f7f5 100%);
    border-bottom: 1px solid #e2ebe4;
}
.log-doc-filtros label {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: #475569; margin-bottom: .35rem;
}
.log-doc-filtros .form-control-sm {
    border-radius: 8px; border-color: #cbd5e1;
    background: #fff;
}
.log-doc-filtros .form-control-sm:focus {
    border-color: #2c5530;
    box-shadow: 0 0 0 .15rem rgba(44, 85, 48, .12);
}
.log-doc-btn-filter {
    background: linear-gradient(135deg, #1e3a22, #2c5530);
    border: 0; color: #fff; font-weight: 700;
    border-radius: 8px; padding: .4rem 1.1rem; font-size: .84rem;
    box-shadow: 0 3px 10px rgba(44, 85, 48, .25);
}
.log-doc-btn-filter:hover { background: linear-gradient(135deg, #162d1a, #234429); color: #fff; }

.log-doc-table thead th {
    background: linear-gradient(180deg, #2c5530 0%, #234429 100%);
    border-bottom: none;
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: rgba(255,255,255,.92); padding: .85rem 1rem;
}
.log-doc-table tbody td {
    padding: .9rem 1rem; vertical-align: middle;
    border-top: 1px solid #eef2f6; font-size: .86rem; color: #334155;
}
.log-doc-table tbody tr:hover { background: #f4f9f5; }
.log-doc-table .td-ref { font-weight: 700; color: #1e3a22; font-size: .84rem; }
.log-doc-table .td-muted { color: #64748b; font-size: .8rem; }
.log-doc-table .td-envio {
    font-weight: 700; color: #1e3a5f; font-size: .84rem;
}

.log-doc-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 700;
    padding: .25rem .6rem; border-radius: 6px;
}
.log-doc-chip--guia {
    background: #e8f3ea; color: #1e4620; border: 1px solid #a8cdb0;
}
.log-doc-chip--pod {
    background: #e8eef5; color: #1e3a5f; border: 1px solid #a8bdd4;
}
.log-doc-chip--nota {
    background: #f5f0e8; color: #6b5435; border: 1px solid #d4c4a8;
}
.log-doc-chip--default {
    background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;
}

.log-doc-actions { display: inline-flex; gap: .4rem; align-items: center; }
.log-doc-btn-icon {
    width: 34px; height: 34px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #cbd5e1; background: #fff; color: #475569;
    font-size: .8rem; transition: all .15s ease;
}
.log-doc-btn-icon:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; text-decoration: none; }
.log-doc-btn-icon--view {
    background: linear-gradient(135deg, #1e4620, #2c5530);
    border-color: #1e4620; color: #fff;
    box-shadow: 0 2px 8px rgba(44, 85, 48, .28);
}
.log-doc-btn-icon--view:hover { background: linear-gradient(135deg, #162d1a, #234429); color: #fff; }
.log-doc-btn-icon--down {
    background: #e8eef5; border-color: #a8bdd4; color: #1e3a5f;
}
.log-doc-btn-icon--down:hover { background: #d4e0ed; color: #152a45; }

.log-doc-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
.log-doc-footer { padding: .75rem 1.25rem; border-top: 1px solid #eef2f6; background: #fafbfc; }
.log-doc-filtros__link { color: #2c5530; font-weight: 700; }

/* Detalle + preview */
.log-doc-det { max-width: 960px; margin: 0 auto; padding: 0 .15rem 1.5rem; }
.log-doc-det__toolbar { margin-bottom: 1rem; }
.log-doc-det__back {
    display: inline-flex; align-items: center; gap: .45rem;
    font-size: .84rem; font-weight: 600; color: #1e3a5f; text-decoration: none;
    padding: .5rem .85rem; border: 1px solid #a8bdd4; border-radius: 8px;
    background: #e8eef5; transition: all .15s ease;
}
.log-doc-det__back:hover {
    background: #d4e0ed; color: #152a45; text-decoration: none;
}

.log-doc-det-card {
    background: #fff; border: 1px solid #dde3ea; border-radius: 14px;
    overflow: hidden; box-shadow: 0 6px 28px rgba(15, 23, 42, .09);
}
.log-doc-det-card__head {
    padding: 1.25rem 1.4rem 1.1rem;
    background: linear-gradient(135deg, #1e3a22 0%, #2c5530 60%, #3a6b42 100%);
    color: #fff; position: relative; overflow: hidden;
}
.log-doc-det-card__head::before {
    content: ''; position: absolute; right: -40px; bottom: -40px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.07);
}
.log-doc-det-card__head-inner { position: relative; z-index: 1; }
.log-doc-det-card__tipo {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #d4e8d6;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22);
    border-radius: 6px; padding: .22rem .55rem; margin-bottom: .55rem;
}
.log-doc-det-card__title {
    font-size: 1.12rem; font-weight: 800; color: #fff; margin: 0; line-height: 1.35;
}
.log-doc-auto-tag {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: #7a6240;
    background: #f5f0e8; border: 1px solid #d4c4a8; border-radius: 999px;
    padding: .22rem .6rem; margin-top: .55rem;
}

.log-doc-meta {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 0; background: #fafbfc;
}
.log-doc-meta__item {
    padding: 1rem 1.35rem;
    border-right: 1px solid #eef2f6;
    border-bottom: 1px solid #eef2f6;
}
.log-doc-meta__item:nth-child(4n) { border-right: none; }
.log-doc-meta__lbl {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: #64748b; margin-bottom: .25rem;
}
.log-doc-meta__val { font-size: .92rem; font-weight: 700; color: #1e293b; }
.log-doc-meta__val--accent { color: #1e4620; }
.log-doc-meta__val--navy { color: #1e3a5f; }

.log-doc-det-actions {
    display: flex; flex-wrap: wrap; gap: .55rem;
    padding: 1.1rem 1.35rem;
    background: linear-gradient(180deg, #fff 0%, #f8faf9 100%);
    border-bottom: 1px solid #e2ebe4;
}
.log-doc-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .58rem 1.15rem; border-radius: 9px; font-size: .86rem; font-weight: 700;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
    transition: all .15s ease;
}
.log-doc-btn--primary {
    background: linear-gradient(135deg, #1e4620, #2c5530);
    color: #fff; border-color: #1e4620;
    box-shadow: 0 3px 12px rgba(44, 85, 48, .3);
}
.log-doc-btn--primary:hover { background: linear-gradient(135deg, #162d1a, #234429); color: #fff; }
.log-doc-btn--primary.is-active {
    background: linear-gradient(135deg, #1e3a5f, #2a5080);
    border-color: #1e3a5f;
    box-shadow: 0 3px 12px rgba(30, 58, 95, .28);
}
.log-doc-btn--secondary {
    background: #fff; color: #1e3a5f; border-color: #a8bdd4;
}
.log-doc-btn--secondary:hover { background: #e8eef5; color: #152a45; text-decoration: none; }

.log-doc-preview { padding: 0; background: #3d4449; border-top: 3px solid #2c5530; }
.log-doc-preview--hidden { display: none; }
.log-doc-preview__frame {
    width: 100%; height: min(78vh, 920px); border: 0; display: block;
    background: #525659;
}
.log-doc-preview__empty {
    padding: 3rem 1.5rem; text-align: center; color: #64748b;
    background: linear-gradient(180deg, #f8faf9, #f1f5f3);
}
</style>
