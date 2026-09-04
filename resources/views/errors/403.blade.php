@extends('layout.main')

@section('css_custom')
    <style>
        .error-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .error-card {
            max-width: 600px;
            width: 100%;
            text-align: center;
            background: #FFFFFF;
            border: 1px solid #BDBDBD;
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }

        .error-code {
            font-size: 96px;
            font-weight: 700;
            line-height: 1;
            color: #0B89D2;
            margin-bottom: 16px;
        }

        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 12px;
        }

        .error-message {
            font-size: 16px;
            color: #BDBDBD;
            margin-bottom: 32px;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background: #0B89D2;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: .2s;
        }

        .btn-home:hover {
            background: #0778b9;
            color: #FFFFFF;
        }

        .lock-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: #EAF5FC;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            color: #0B89D2;
        }
    </style>
@endsection

@section('content')
    <div class="error-wrapper">

        <div class="error-card">

            <div class="lock-icon">
                <i class="fa-solid fa-lock"></i>
            </div>

            <div class="error-code">
                403
            </div>

            <div class="error-title">
                Access Denied
            </div>

            <div class="error-message">
                Anda tidak memiliki hak akses untuk membuka halaman ini.
                Silakan hubungi Tim IT apabila merasa ini adalah kesalahan.
            </div>

            <a href="{{ url()->previous() }}" class="btn-home">
                Kembali
            </a>

        </div>

    </div>
@endsection
