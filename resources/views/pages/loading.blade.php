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
      
    </style>
  </head>
  <body>
    @include('layout.js_global')
    <script>
      $(function(){
        var segment = sessionStorage.getItem('segment') ?? null;
        var pda = sessionStorage.getItem('pda') ?? null;

        console.log("segment : " + segment);
        console.log("pda : " + pda);
        
        var parsedDataSegment = segment ? JSON.parse(segment) : null;
        var parsedDataPDA = pda ? JSON.parse(pda) : null;
      
        console.log("parsedDataSegment : " + JSON.stringify(parsedDataSegment));
        console.log("parsedDataPDA : " + JSON.stringify(parsedDataPDA));

        if (parsedDataSegment && parsedDataSegment.segment &&  parsedDataSegment.segment != "" && parsedDataSegment.mekanikpk &&  parsedDataSegment.mekanikpk != "" && parsedDataPDA && parsedDataPDA.pda && parsedDataPDA.pda != "") {
          console.log("login mekanik here");

          let url = "{{ route('logout.mekanik.pda', ['mekanikpk' => ':mekanikpk', 'pda' => ':pda']) }}";
          url = url.replace(':mekanikpk', parsedDataSegment.mekanikpk);
          url = url.replace(':pda', parsedDataPDA.pda);

          console.log("url logout mekanik : " + url);

          $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
              console.log("success logout mekanik with new log mekanik data here >>>>> " + JSON.stringify(response));
              window.location.href = "{{ route('login.mekanik.pda.index', '') }}/" + parsedDataPDA.pda;
            },
            error: function(xhr, status, error) {
              console.error("error logout mekanik with new log mekanik data here >>>>> " + JSON.stringify(xhr));
            }
          });
        } else {
          console.log("login umum here");

          window.location.href = "{{ route('login.index') }}";
        }

      });
    </script>

    <div class="wrapper bg-light">
      <div class="main-wrapper bg-light">
        <div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light">
          <div class="d-flex justify-content-center">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>