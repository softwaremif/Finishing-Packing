<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="{!! asset('public/css/images/morich.gif') !!}" />
  <title>Packing List Login</title>

  @include('layout.css_global')

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>

    body{
      background:#f4f6f9;
    }

    .login-wrapper{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .login-card{
      width:420px;
      border:none;
      border-radius:16px;
      box-shadow:0 10px 30px rgba(0,0,0,.08);
      padding:20px;
    }

    .logo{
      width:80px;
      height:auto;
    }

    .title-app{
      font-size:22px;
      font-weight:700;
      margin-top:10px;
    }

    .subtitle{
      font-size:14px;
      color:#666;
    }

    .input-login{
      height:48px;
      font-size:15px;
      border-radius:10px;
      padding:10px 14px;
    }

    .btn-login{
      height:48px;
      border-radius:10px;
      font-weight:600;
    }

    .password-box{
      position:relative;
    }

    .toggle-eye{
      position:absolute;
      right:12px;
      top:50%;
      transform:translateY(-50%);
      cursor:pointer;
      color:#666;
      font-size:18px;
    }

    .error-text{
      font-size:13px;
      margin-top:8px;
      text-align:center;
    }

  </style>
</head>

<body>

@include('layout.js_global')

<script>

$(function(){

  $('#username,#password-0').on("keypress", function(e){
    if(e.which===13) login();
  });

});

function validate(){

  let u = $('#username').val();
  let p = $('#password-0').val();

  if(!u || !p){

    $('#loginErrorMessage').text(
      !u ? 'Username wajib diisi' : 'Password wajib diisi'
    );

    return false;
  }

  $('#loginErrorMessage').text('');
  return true;
}

function login(){

  if(!validate()) return;

  $.ajax({

    type:'POST',
    url:"{{ route('login') }}",
    data:{
      _token:"{{ csrf_token() }}",
      username:$('#username').val(),
      password:$('#password-0').val()
    },

    success:function(res){

      $('#formLogin')[0].reset();

      let route = res.route ?? '';
      window.location.href = route ? route : '/';

    },

    error:function(xhr){

      $('#loginErrorMessage')
        .text(xhr.responseJSON?.message ?? 'Login gagal');

    }

  });

}

function togglePassword(){

  let input = document.getElementById("password-0");

  if(input.type === "password"){
    input.type = "text";
    $('#eyeIcon').removeClass('bi-eye-slash').addClass('bi-eye');
  }else{
    input.type = "password";
    $('#eyeIcon').removeClass('bi-eye').addClass('bi-eye-slash');
  }

}

</script>

<div class="login-wrapper">

  <div class="card login-card">

    <div class="text-center">

      <img src="{!! asset('public/css/images/morich.gif') !!}" class="logo">

      <div class="title-app">
        Packing List System
      </div>

      <div class="subtitle">
        Login to your account
      </div>

    </div>

    <form id="formLogin" class="mt-4">

      <!-- USERNAME -->
      <div class="mb-3">
        <input id="username"
          type="text"
          class="form-control input-login"
          placeholder="Username">
      </div>

      <!-- PASSWORD -->
      <div class="mb-2 password-box">

        <input id="password-0"
          type="password"
          class="form-control input-login"
          placeholder="Password">

        <i id="eyeIcon"
          class="bi bi-eye-slash toggle-eye"
          onclick="togglePassword()"></i>

      </div>

      <div id="loginErrorMessage"
        class="text-danger error-text">
      </div>

      <button type="button"
        class="btn btn-dark w-100 btn-login mt-3"
        onclick="login()">

        Masuk

      </button>

    </form>

  </div>

</div>

</body>
</html>