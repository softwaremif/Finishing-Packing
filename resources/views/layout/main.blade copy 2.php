<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="{!! asset('public/css/images/morich.gif') !!}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Morich Indo Fashion</title>
    @include('layout.css_global')
    @yield('css_custom')
</head>

<body>

    <div class="wrapper">

        <!-- Navbar -->
        @include('layout.navbar')
        <!-- End Navbar -->

        <!-- Content -->
        @include('modal.password')

        <div>
            @yield('content')
        </div>

        @include('alert.success')
        @include('alert.error')

    </div>
        {{-- @if (session('toast'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    Swal.fire({
                        toast: true,
                        position: 'bottom-start',
                        icon: "{{ session('toast')['icon'] }}",
                        title: `{!! session('toast')['title'] !!}`,
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true,

                        didOpen: (toast) => {

                            toast.addEventListener('mouseenter', Swal.stopTimer);

                            toast.addEventListener('mouseleave', Swal.resumeTimer);

                        }

                    });

                });
            </script>
        @endif --}}
        <script>
        /**
         * Toast global — bisa dipanggil dari halaman mana pun,
         * baik dari respons AJAX maupun dari session flash (redirect biasa).
         */
        function showToast(icon, title) {
            Swal.fire({
                toast: true,
                position: 'bottom-start',
                icon: icon,
                title: title,
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        }
    </script>

    {{-- Tetap dipertahankan untuk kasus redirect biasa (misal: dari controller lain yang belum AJAX) --}}
    @if (session('toast'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showToast(
                    "{{ session('toast')['icon'] }}",
                    `{!! session('toast')['title'] !!}`
                );
            });
        </script>
    @endif
    @include('layout.js_global')
    @yield('js_custom')
    @yield('scripts')

</body>
</html>