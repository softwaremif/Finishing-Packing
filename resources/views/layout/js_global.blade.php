{{-- <script src="{{ url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js') }}"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
</script> --}}
<script type="text/javascript" src="{!! asset('public/datepicker/jquery.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/datepicker/moment.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/datepicker/daterangepicker.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/media/jquery-easyui-1.9.15/jquery.easyui.min.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/media/jquery-easyui-1.9.15/jquery.edatagrid.js') !!}"></script>
<script type="text/javascript" src="{!! asset('public/bootstrap-5.3.2-dist/js/bootstrap.bundle.min.js') !!}"></script>
{{-- <script type="text/javascript" src="{!! asset('public/media/jquery-easyui-1.9.15/jquery.min.js') !!}"></script> --}}
<script type="text/javascript" src="{!! asset('public/css/ckeditor_fullpage/ckeditor.js') !!}"></script>

<script>
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
                            console.log("ini response data : " + JSON.stringify(data));
                            $('#passwordModal').modal('hide');
                            $('#formChangePassword').form('reset');
                        }
                    });
                }
            }
        }
    }

    function showAlert(index, message) {
        if (index == 200 || index == 201 || index == 202 || index == 204) {
            document.getElementById("success-message").textContent = message;
            $('#alert-success').toast('show');
            setTimeout(() => {
                $('#alert-success').toast('hide');
            }, 3000);
        } else {
            document.getElementById("error-message").textContent = message;
            $('#alert-error').toast('show');
            setTimeout(() => {
                $('#alert-error').toast('hide');
            }, 3000);
        }
    }
</script>
