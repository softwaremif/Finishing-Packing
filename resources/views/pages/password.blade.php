<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{!! asset('public/css/images/morich.gif') !!}"/>
    <title>Morich Indo Fashion</title>
    @include('layout.css_global')
    
    <style>
      .card.custom {
        border-color: transparent;
        box-shadow: 0px 0px 16px -4px rgba(0, 0, 0, 0.12);
        width: 35%;
      }

      .img-fluid {
        width:20%;
      }

      h2.title {
        font-family: 'Arial';
        font-style: normal;
        font-weight: 700;
        font-size: 24px;
        line-height: 28px;
        color: #000000;
      }

      span.title {
        font-family: 'Arial';
        font-style: normal;
        font-weight: 700;
        font-size: 14px;
        line-height: 20px;
      }

      input.easyui-validatebox {
        width: 250px;
        height: 34px;
        font-size: 14px;
      }

      input.easyui-validatebox:focus {
        border-width: 2px;
      }

      .btn.custom {
        min-width: 120px;
      }

      @media screen and (max-width: 1114px) {
        .card.custom {
          width: auto;
        }
      }

      @media screen and (max-width: 380px) {
        .flex-responsive {
          flex-direction: column;
        }

        .easyui-validatebox.validatebox-text.w-auto {
          width: 100% !important;
        }
      }
    </style>
  </head>
  <body>
    @include('layout.js_global')
    <script>
      $(function(){
        $("#eror1").hide();
        $("#eror2").hide();
        $("#eror3").hide();
        $("#eror4").hide();
        $('#formUpdatePassword').form('reset');
        document.getElementById('stepOneUpdatePassword').classList.remove('d-none');
        document.getElementById('stepTwoUpdatePassword').classList.add('d-none');
      });

      function validate() {
        var newPassword = $('#password-4').val() ?? null;
        var confirmPassword = $('#password-5').val() ?? null;

        if (!newPassword || !confirmPassword) {
          if (!newPassword) $("#eror2").show(); else $("#eror2").hide();
          if (!confirmPassword) $("#eror3").show(); else $("#eror3").hide();
          return false;
        }

        return true;
      }

      function onSubmitUpdatePassword(){
        var validateResult = validate();

        if (!validateResult) return;

        var lowerCaseLetters = /[a-z]/g;
        var upperCaseLetters = /[A-Z]/g;
        var numbers = /[0-9]/g;
        var myInput = document.getElementById("password-4");
        var password = $("#password-4").val();
        var confirmPassword = $("#password-5").val();
        
        if (password != confirmPassword){
          if (password=='') {
            $("#eror2").show();
          } else {
            $("#eror2").hide();
          }

          if (confirmPassword=='') {
            $("#eror3").show();
          } else {
            $("#eror3").hide();
          }

          if (!lowerCaseLetters || !upperCaseLetters || !numbers || myInput.value.length < 8 ) {
            $("#eror1").show();
          } else {
            $("#eror1").hide();
          }

          $("#eror4").show();
        } else {
          $("#eror4").hide();

          if (password=='') {
            $("#eror2").show();
          } else {
            $("#eror2").hide();
          }

          if (confirmPassword=='') {
            $("#eror3").show();
          } else {
            $("#eror3").hide();
          }

          if (!lowerCaseLetters || !upperCaseLetters || !numbers || myInput.value.length < 8 ) {
            $("#eror1").show();
          } else {
            var userpk = $('#userpk').val();
            var pswd = $('#password-4').val();
            var cnfrm = $('#password-5').val();

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
                success: function(data){
                  $('#formUpdatePassword').form('reset');
                  $("#eror1").hide();
                  $("#eror2").hide();
                  $("#eror3").hide();
                  $("#eror4").hide();
                  document.getElementById('stepOneUpdatePassword').classList.add('d-none');
                  document.getElementById('stepTwoUpdatePassword').classList.remove('d-none');
                }
              });
            }
          }
        }
      }

      function onCancelUpdatePassword(){
        let url = "{{ route('logout') }}";
        window.location.href= url;
      }

      function onContinueUpdatePassword() {
        // var gusernm = "{{ Session::get('gusernm') }}";

        // if (gusernm == "MD") {
        //   window.location.href="style";
        // } else if (gusernm == "Sample" || gusernm == "Pattern") {
        //   window.location.href="sample";
        // } else if (gusernm == "IE") {
        //   window.location.href="ie";
        // } else if (gusernm == "Cutting") {
        //   window.location.href="cutting/todo/web";
        // } else if(gusernm =="Sewing"){
        //   window.location.href="sewing/todo/web";
        // } else if (gusernm == "QA") {
        //   window.location.href="qa/todo";
        // } else if (gusernm == "Manager") {
        //   window.location.href="man";
        // } else if (gusernm == "Pola") {
        //   window.location.href="pola";
        // } else if (gusernm == "Embellishment") {
        //   window.location.href="embellishment/todo/web";
        // } else if (gusernm == "Washing") {
        //   window.location.href="washing/todo/web";
        // }
        let url = "{{ route('index.poreq') }}";
        window.location.href= url;

        console.log("gusernm tidak terdaftar, cek blade pages.password");
      }
    </script>

    <div class="wrapper bg-light">
      <div class="main-wrapper">
        <div class="container min-vh-100 p-5">
          <div class="d-flex justify-content-center">
            <div class="card custom border-0 bg-white">
              <div class="card-body p-4 pt-3">
                <div id="stepOneUpdatePassword" class="d-flex flex-column">
                  <div class="d-flex flex-row">
                    <input id="userpk" name="userpk" type="text" value="{{Session::get('userpk')}}" hidden>
                    <form id="formUpdatePassword">
                      <div class="pt-2">
                        <h5 style="font-family: 'Arial';font-style: normal;font-weight: 700;font-size: 18px;line-height: 24px;">Renew your password</h5>
                      </div>
                      <div class="pt-2">
                        <p style="font-family: 'Arial';font-style: normal;font-weight: 400;font-size: 14px;line-height: 18px;">For security reasons, please renew your password. <br> Password renewal is required every 6 months.</p>
                      </div>
                      <div class="pb-0">
                        <p class="mb-0" style="font-family:'Arial'; font-style: normal;font-weight: 700;font-size: 14px;line-height: 18px;">New password</p>
                      </div>
                      <div class="pt-1">
                        <input id="password-4" name="password-4" type="password" class="easyui-validatebox validatebox-text w-auto" autocomplete="false" onchange="validate()" required>
                        <i class="bi bi-eye-slash" id="i-eye-password-4" style="margin-left: -30px; cursor: pointer;" onmouseover="showHiddenPassword(4);" onmouseout="showHiddenPassword(4);"></i>
                      </div>
                      <div class="pb-0 pt-2">
                        <p class="mb-0" style="font-family:'Arial'; font-style: normal;font-weight: 700;font-size: 14px;line-height: 18px;">Confirm new password</p>
                      </div>
                      <div class="pt-1">
                        <input id="password-5" name="password-5" type="password" class="easyui-validatebox validatebox-text w-auto" autocomplete="false" onchange="validate()" required>
                        <i class="bi bi-eye-slash" id="i-eye-password-5" style="margin-left: -30px; cursor: pointer;" onmouseover="showHiddenPassword(5);" onmouseout="showHiddenPassword(5);"></i>
                      </div>
                    </form>
                  </div>
                  <div class="d-flex">
                    <div class="pt-3">
                      <li id="eror1" class="text-danger" style="display: none">Password must be at least 8 characters and contain upper case, lowercase, numbers, and special characters.</li>
                      <li id="eror2" class="text-danger" style="display: none">New password is required.</li>
                      <li id="eror3" class="text-danger" style="display: none">Confirm new password is required.</li>
                      <li id="eror4" class="text-danger" style="display: none">Password confirmation does not match.</li>
                    </div>
                  </div>
                  <div class="d-inline-flex justify-content-end flex-responsive gap-2 pt-4">
                    <button class="btn btn-block btn-light custom" onclick="onCancelUpdatePassword()">Cancel</button>
                    <button class="btn btn-block btn-dark custom" onclick="onSubmitUpdatePassword()">Submit</button>
                  </div>
                </div>
                <div id="stepTwoUpdatePassword" class="d-flex flex-column d-none">
                  <div class="d-flex pt-2 justify-content-center">
                    <img src="{!! asset('public/css/images/centang.png') !!}" width="30" height="30">
                  </div>
                  <div class="d-flex pt-2 justify-content-center">
                    <p style="font-family: 'Arial';font-style: normal;font-weight: 400;font-size: 14px;line-height: 18px;">Password successfully updated.</p>
                  </div>
                  <div class="d-flex pt-4 justify-content-center">
                    <button class="btn btn-block btn-dark custom" onclick="onContinueUpdatePassword()">Continue</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      
    </div>
  </body>
</html>