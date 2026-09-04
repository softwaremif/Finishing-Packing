<script type="text/javascript" src="{!! asset('public/datepicker/jquery.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/datepicker/moment.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/datepicker/daterangepicker.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/media/jquery-easyui-1.9.15/jquery.easyui.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/media/jquery-easyui-1.9.15/jquery.edatagrid.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/media/jquery-easyui-1.9.15/datagrid-detailview.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/bootstrap-5.3.2-dist/js/bootstrap.bundle.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/css/ckeditor_fullpage/ckeditor.js') !!}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $.ajaxSetup({
        cache: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>

<script>
    /**
     * ==========================================================
     * NOTIFIKASI GLOBAL — panduan pemakaian
     * ==========================================================
     *
     * showToast(icon, title)
     * → Notifikasi ringan, non-blocking, auto-hilang (toast, pojok bawah kiri).
     * → Cocok untuk: hasil AJAX yang mengembalikan objek {icon, title},
     *   misalnya update status packing, segel packing, dll.
     *
     * showAlert(index, message)
     * → Modal di tengah, harus diklik OK (khusus error).
     * → Cocok untuk: hasil AJAX lama yang mengembalikan HTTP status code
     *   (200/201/202/204 dianggap sukses, selain itu dianggap gagal).
     * ==========================================================
     */

    // function showToast(icon, title) {
    //     Swal.fire({
    //         toast: true,
    //         position: 'bottom-start',
    //         icon: icon,
    //         title: title,
    //         showConfirmButton: false,
    //         timer: 5000,
    //         timerProgressBar: true,
    //         didOpen: (toast) => {
    //             toast.addEventListener('mouseenter', Swal.stopTimer);
    //             toast.addEventListener('mouseleave', Swal.resumeTimer);
    //         }
    //     });
    // }

    // function showAlert(index, message) {
    //     if ([200, 201, 202, 204].includes(index)) {
    //         Swal.fire({
    //             icon: 'success',
    //             title: 'Berhasil',
    //             text: message,
    //             timer: 2500,
    //             showConfirmButton: false
    //         });
    //     } else {
    //         Swal.fire({
    //             icon: 'error',
    //             title: 'Gagal',
    //             text: message,
    //             confirmButtonText: 'OK'
    //         });
    //     }
    // }

    let __appToastTimer = null;

    function showToast(icon, title) {
        let cls = 'app-toast-success';
        let symbol = '✓';

        if (icon === 'error') {
            cls = 'app-toast-error';
            symbol = '✕';
        }
        if (icon === 'warning') {
            cls = 'app-toast-error';
            symbol = '!';
        }

        // Buat container sekali saja, lalu reuse untuk panggilan berikutnya
        let $toast = $('#appToast');
        if ($toast.length === 0) {
            $toast = $('<div id="appToast"></div>').appendTo('body');
        }

        // Hentikan animasi/timer sebelumnya kalau ada toast baru yang menimpa
        $toast.stop(true, true);
        clearTimeout(__appToastTimer);

        $toast
            .html(`
                <div class="app-toast-box ${cls}">
                    <span>${title}</span>
                    <span class="app-toast-symbol">${symbol}</span>
                </div>
            `)
            .css({
                display: 'flex',
                opacity: 0
            })
            .animate({
                opacity: 1
            }, 200);

        __appToastTimer = setTimeout(function() {
            $toast.animate({
                opacity: 0
            }, 200, function() {
                $(this).css('display', 'none');
            });
        }, 3000);
    }

    function showAlert(index, message) {
        if ([200, 201, 202, 204].includes(index)) {
            showToast('success', message);
        } else {
            showToast('error', message);
        }
    }
</script>

@if (session('toast'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast(
                "{{ session('toast')['icon'] }}",
                `{!! session('toast')['title'] !!}`
            );
        });
    </script>
@endif

<script>
    let lastScroll = 0;

    $(window).on('scroll', function() {

        let current = $(this).scrollTop();

        if (current > 80) {
            $('#stickTopBar').css('top', '0');
        } else {
            $('#stickTopBar').css('top', '58px');
        }

        lastScroll = current;
    });

    function changePassword() {
        $('#passwordModal').modal('show');
    }

    function cancelChangePassword() {
        $('#formChangePassword').form('reset');
    }

    function showHiddenPassword(index) {
        const currentPassword = document.querySelector("#password-" + index);
        const iEyeCurrentPassword = document.querySelector("#i-eye-password-" + index);
        iEyeCurrentPassword.addEventListener("click", function() {
            const inputType = currentPassword.getAttribute("type") === "password" ? "text" : "password";
            currentPassword.setAttribute("type", inputType);
            this.classList.toggle("bi-eye");
        });
    }

    function changepswd() {
        var lowerCaseLetters = /[a-z]/g;
        var upperCaseLetters = /[A-Z]/g;
        var numbers = /[0-9]/g;
        var myInput = document.getElementById("password-2");
        var password = $("#password-2").val();
        var confirmPassword = $("#password-3").val();

        if (password != confirmPassword) {
            if (password == '') {
                $("#eror2").show();
            } else {
                $("#eror2").hide();
            }

            if (confirmPassword == '') {
                $("#eror3").show();
            } else {
                $("#eror3").hide();
            }

            if (!lowerCaseLetters || !upperCaseLetters || !numbers || myInput.value.length < 8) {
                $("#eror1").show();
            } else {
                $("#eror1").hide();
            }

            $("#eror4").show();

        } else {
            $('#eror5').hide();
            $("#eror4").hide();

            if (password == '') {
                $("#eror2").show();
            } else {
                $("#eror2").hide();
            }

            if (confirmPassword == '') {
                $("#eror3").show();
            } else {
                $("#eror3").hide();
            }

            if (!lowerCaseLetters || !upperCaseLetters || !numbers || myInput.value.length < 8) {
                $("#eror1").show();
            } else {
                var userpk = $('#userpk').val();
                var pswd = $('#password-2').val();
                var cnfrm = $('#password-3').val();

                if (confirmPassword) {
                    var formData = {
                        userpk: userpk,
                        pswd: pswd,
                        cnfrm: cnfrm,
                    };

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('updtpswdb') }}",
                        data: formData,
                        success: function(data) {
                            $('#passwordModal').modal('hide');
                            $('#formChangePassword').form('reset');
                            showAlert(200, 'Password berhasil diubah');
                        },
                        error: function(xhr) {
                            showAlert(500, xhr.responseJSON?.message ?? 'Terjadi kesalahan');
                        }
                    });
                }
            }
        }
    }
</script>
