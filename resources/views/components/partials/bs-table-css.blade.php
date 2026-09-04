@once
<style>
    .bs-dg-wrap .order-title { font-weight:700; font-size:18px; margin-bottom:12px; }
    .bs-dg-wrap .filter-bar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px; align-items:center; }

    .bs-dg-wrap .active-filters-bar {
        display:none; align-items:center; gap:10px; margin-bottom:14px;
        padding:10px 16px; border:1px solid #e5e7eb; border-radius:12px; background:#fff;
    }
    .bs-dg-wrap .active-filters-label { font-size:13px; color:#374151; white-space:nowrap; flex-shrink:0; }
    .bs-dg-wrap .active-filters-chips { display:flex; flex-wrap:wrap; gap:8px; flex:1; }
    .bs-dg-wrap .active-filter-chip {
        display:inline-flex; align-items:center; gap:8px; padding:6px 8px 6px 14px;
        border-radius:20px; background:#f1f5f9; color:#0f172a; font-size:13px; font-weight:600;
    }
    .bs-dg-wrap .active-filter-chip .chip-close {
        display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px;
        border:none; background:transparent; color:#64748b; font-size:14px; line-height:1; cursor:pointer; padding:0;
    }
    .bs-dg-wrap .active-filter-chip .chip-close:hover { color:#0f172a; }
    .bs-dg-wrap .chip-clear-all {
        font-size:13px; font-weight:600; color:#2563eb; text-decoration:none; cursor:pointer;
        background:none; border:none; white-space:nowrap; flex-shrink:0;
    }
    .bs-dg-wrap .chip-clear-all:hover { color:#1d4ed8; text-decoration:underline; }

    /* Tabel */
    .bs-dg-wrap .bs-dg-table-wrap {
        border-radius:14px; overflow:hidden; border:1px solid #e5e7eb; background:#fff;
    }
    .bs-dg-wrap .bs-dg-scroll { max-height:600px; overflow-y:auto; }
    .bs-dg-wrap table.bs-dg-table { margin-bottom:0; }
    .bs-dg-wrap table.bs-dg-table thead th {
        font-weight:600; font-size:12px; text-align:center; color:#374151;
        background:#fff; border-bottom:1px solid #e5e7eb !important;
    }
    .bs-dg-wrap table.bs-dg-table tbody td { font-size:12px; border-bottom:1px solid #f3f4f6; }
    .bs-dg-wrap table.bs-dg-table tbody tr:hover { background:#f9fafb; }

    .bs-dg-wrap .action-btn {
        display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px;
        border-radius:8px; background:#e0f2fe; color:#0369a1; font-size:13px; text-decoration:none;
    }
    .bs-dg-wrap .action-btn:hover { background:#bae6fd; color:#0c4a6e; }

    .bs-dg-wrap .sort-wrap { margin-left:auto; }
    .bs-dg-wrap .sort-btn {
        display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px;
        border:1px solid #e5e7eb; background:#fff; color:#374151; font-size:12px; font-weight:600;
        cursor:pointer; user-select:none;
    }
    .bs-dg-wrap .sort-btn:hover { background:#f9fafb; border-color:#cbd5e1; color:#0f172a; }
    .bs-dg-wrap .sort-card-wrap { position:relative; }
    .bs-dg-wrap .sort-btn .fa-chevron-down { transition:transform .15s ease; }
    .bs-dg-wrap .sort-card-wrap.open .sort-btn .fa-chevron-down { transform:rotate(180deg); }
    .bs-dg-wrap .sort-card-panel {
        display:none; position:absolute; top:calc(100% + 8px); right:0; min-width:230px;
        background:#fff; border:1px solid #e5e7eb; border-radius:12px;
        box-shadow:0 10px 28px rgba(15,23,42,.14); padding:8px; z-index:60;
    }
    .bs-dg-wrap .sort-card-panel.show { display:block; }
    .bs-dg-wrap .sort-card-option {
        display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px;
        cursor:pointer; font-size:13px; color:#374151;
    }
    .bs-dg-wrap .sort-card-option:hover { background:#f8fafc; }
    .bs-dg-wrap .sort-card-option.active { background:#eff6ff; color:#1d4ed8; font-weight:600; }
    .bs-dg-wrap .sort-card-option .opt-icon {
        width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center;
        background:#f1f5f9; color:#64748b; font-size:12px; flex-shrink:0;
    }
    .bs-dg-wrap .sort-card-option.active .opt-icon { background:#dbeafe; color:#1d4ed8; }
    .bs-dg-wrap .sort-card-option .opt-check { margin-left:auto; color:#1d4ed8; font-size:12px; opacity:0; }
    .bs-dg-wrap .sort-card-option.active .opt-check { opacity:1; }

    .bs-dg-wrap .bs-empty-state { text-align:center; padding:60px 20px; }
    .bs-dg-wrap .bs-empty-state img { opacity:.9; }

    .bs-dg-wrap .bs-dg-footer {
        display:flex; justify-content:space-between; align-items:center;
        padding:8px 12px; border-top:1px solid #f1f5f9; background:#fff;
    }
</style>
@endonce