<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="{!! asset('public/css/images/morich.gif') !!}" />
  <title>Morich Indo Fashion</title>
  @include('layout.css_global')
  <style>

    .card {
      --bs-card-spacer-y: 1rem;
      --bs-card-spacer-x: 6.2rem;
    }

    .img-fluid {
      width: 20%;
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

    .btn-dark.custom {
      width: 150px;
      height: 34px;
    }
  </style>
</head>

<body>
  @include('layout.js_global')
  <script>
    $(function() {
      $('#username').on("keypress", function(e) {
        if (e.which === 13) {
          login();
        }
      });
      $('#password-0').on("keypress", function(e) {
        if (e.which === 13) {
          login();
        }
      });
    });

    function validate() {
      var username = $('#username').val() ?? null;
      var password = $('#password-0').val() ?? null;

      if (!username || !password) {
        if (!username) document.getElementById('loginErrorMessage').textContent = "username wajib diisi";
        if (!password) document.getElementById('loginErrorMessage').textContent = "password wajib diisi";
        return false;
      }

      return true;
    }

    function login() {
      var validateResult = validate();
      var username = $('#username').val() ?? null;
      var password = $('#password-0').val() ?? null;
      var csrfToken = "{{ csrf_token() }}";

      var formData = {
        _token: csrfToken,
        username: username,
        password: password,
      }

      if (!validateResult) return;

      $.ajax({
        type: 'POST',
        url: "{{ route('login') }}",
        data: formData,
        success: function(response) {
          console.log("ini response : " + JSON.stringify(response.data));
          $('#formLogin').form('reset');

          var route = response.route ?? null;
          var pathArray = window.location.pathname.split("/");
          var pathname = pathArray[1];
          var url = `${location.origin+'/'+pathname+'/'}`;

          if (route != null) url = url + route;

          window.location.href = url;
        },
        error: function(xhr, status, error) {
          $('#formLogin').form('reset');

          if (xhr.responseJSON.message) document.getElementById('loginErrorMessage').textContent = xhr.responseJSON.message;
          if (!xhr.responseJSON.message) document.getElementById('loginErrorMessage').textContent = JSON.stringify(error);
        }
      });
    }
  </script>

  <div class="wrapper bg-light">
    <div class="main-wrapper bg-light">
      <div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card custom bg-white">
          <div class="card-body">
            <div class="d-grid">
              <div class="d-inline-flex justify-content-center pt-5">
                <img src="{!! asset('public/css/images/morich.gif') !!}" class="img-fluid">
              </div>
              <div class="d-inline-flex justify-content-center pt-2">
                <h2 class="title">Finishing & Packing</h2>
              </div>
              <div class="d-inline-flex justify-content-center pt-5">
                <span class="title">Login into your account</span>
              </div>
              <form id="formLogin">
                <div class="d-flex flex-row justify-content-center">
                  <div class="p-1">
                    <input id="username" type="text" class="easyui-validatebox validatebox-text" placeholder="Enter Username">
                  </div>
                </div>
                <div class="d-flex flex-row justify-content-center">
                  <div class="p-1">
                    <input id="password-0" name="password-0" type="password" class="easyui-validatebox validatebox-text" placeholder="Enter password" autocomplete="false">
                    <i class="bi bi-eye-slash" id="i-eye-password-0" style="margin-left: -23px; cursor: pointer;" onmouseover="showHiddenPassword(0);" onmouseout="showHiddenPassword(0);"></i>
                  </div>
                </div>
              </form>
              <div class="d-flex flex-row justify-content-center">
                <div class="p-1">
                  <span id="loginErrorMessage" class="text-danger"></span>
                </div>
              </div>
              @if(Session::has('failed'))
              <div id="eror" class="font-gagal-login" style="width:100%; text-align:center;">
                {{ Session::get('failed') }}
              </div>
              @endif
              <div class="d-inline-flex justify-content-center pt-4 pb-5">
                <button class="btn btn-block btn-dark custom" onclick="login()">Masuk</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>