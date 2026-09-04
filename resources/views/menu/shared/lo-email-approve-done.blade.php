<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Approval</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layout.css_global')
    <style>
        body { background: #f8fafc; }

        .result-card {
            border-radius: 14px;
            overflow: hidden;
        }
        .result-accent {
            height: 5px;
            background: {{ $success ? '#16a34a' : '#d97706' }};
        }
        .result-icon-wrap {
            width: 88px; height: 88px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px auto;
            background: {{ $success ? '#dcfce7' : '#fef3c7' }};
        }
        .result-icon-wrap i {
            font-size: 40px;
            color: {{ $success ? '#16a34a' : '#d97706' }};
        }
        .result-title {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
        }
        .result-message {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            max-width: 360px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container py-5" style="max-width:480px;">
        <div class="card border-0 shadow-sm result-card">
            {{-- <div class="result-accent"></div> --}}
            <div class="card-body p-5 text-center">
                <div class="result-icon-wrap">
                    <i class="fas fa-{{ $success ? 'check' : 'triangle-exclamation' }}"></i>
                </div>
                <div class="result-title mb-2">{{ $success ? 'Berhasil Diproses' : 'Tidak Bisa Diproses' }}</div>
                <p class="result-message mb-0">{{ $message }}</p>
            </div>
        </div>
    </div>
    @include('layout.js_global')
</body>
</html>