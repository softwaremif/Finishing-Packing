@props([
    'title',
    'backUrl'
])

@once
    <style>
        .btn-back-custom {
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .btn-back-custom:hover {
            color: var(--text-main);
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px); /* Efek UX: bergerak sedikit ke kiri saat di-hover */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
@endonce

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
    <div class="d-flex align-items-center gap-3">

        <a href="{{ $backUrl }}"
           class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
           style="width:38px;height:38px"
           title="Kembali">

            <i class="fas fa-arrow-left"></i>

        </a>

        <div>
            <h4 class="fw-bold text-dark mb-0">
                {{ $title }}
            </h4>
        </div>
    </div>
</div>