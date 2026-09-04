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
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script>
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
                // $("#eror1").hide();
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
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {

                            $('#passwordModal').modal('hide');
                            $('#formChangePassword').form('reset');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Password berhasil diubah',
                                timer: 2500,
                                showConfirmButton: false
                            });
                        },
                        error: function(xhr) {

                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ?? 'Terjadi kesalahan'
                            });

                        }
                    });
                }
            }
        }
    }

    // function showAlert(index, message) {
    //     if (index == 200 || index == 201 || index == 202 || index == 204) {
    //         document.getElementById("success-message").textContent = message;
    //         $('#alert-success').toast('show');
    //         setTimeout(() => {
    //             $('#alert-success').toast('hide');
    //         }, 3000);
    //     } else {
    //         document.getElementById("error-message").textContent = message;
    //         $('#alert-error').toast('show');
    //         setTimeout(() => {
    //             $('#alert-error').toast('hide');
    //         }, 3000);
    //     }
    // }

    function showAlert(index, message) {

        if ([200, 201, 202, 204].includes(index)) {

            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: message,
                timer: 2500,
                showConfirmButton: false
            });

        } else {

            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: message,
                confirmButtonText: 'OK'
            });

        }
    }
</script>
