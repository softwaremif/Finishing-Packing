@extends('layout.main')

@section('css_custom')
<style>
  .button-add {
    justify-content: center;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    border-radius: 8px;
    background: #000;
    color: #FFF;
    height: 30px;
  }

    .btn-black {
        color: #fff;
        background: #000;
    }

    .btn-transparent {
        color: #000;
        background: transparent;
    }

    .rounded {
        border-radius: 8px;
    }

  .circle {
    width: 40px;
    height: 40px;
    background-color: red;
    color: white;
    font-size: 20px;
    font-weight: bold;
    text-align: center;
    line-height: 40px;
    border-radius: 50%;
    display: inline-block;
  }

  .btn-group-v1 {
    height: 34px;
    width: 140px;
  }

  .border-1 {
    border: 1px solid red;
  }

  .border-list-fltr {
    border-radius: 8px;
    border: 1px solid var(--Grey-light-3, #E0E0E0);
    background: #FFF;
  }

  .field-text {
    font-size: 16px;
    font-style: normal;
    font-weight: 400;
    line-height: 20px;
  }

  .text-qty-orders {
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 18px;
  }

  .text-filter {
    color: #000;
    font-size: 14px;
    font-style: normal;
    font-weight: 700;
    line-height: 18px;
  }

  .datagrid-cell {
    /* font-family: 'Arial'; */
    font-style: normal;
    font-weight: 400;
    font-size: 13px;
    line-height: 18px;
    white-space: normal;
  }

  .datagrid-row {
    height: auto !important;
    white-space: normal !important;
  }

  /* .datagrid-body {
    overflow-y: hidden !important;
    height: auto !important;
	} */

  .lines-no3 .datagrid-body td {
    border-right: 1px dotted transparent;
    border-bottom: 1px dotted transparent;
  }

  .dropdown-menu {
    max-height: 200px;
    overflow-y: auto;
    /* Add scroll if there are many checkboxes */
  }

  .imgsort {
    position: relative;
    width: 12px !important;
    height: 11.26px !important;
    left: 0px !important;
    top: 0px !important;
  }

  .dropdown-toggle-custom-sortlist {
    border-style: none !important;
  }

  .dropdown-toggle-custom-sortlist::after {
    display: none;
    border-style: none !important;
  }

  .dropdown-item:active {
    background-color: transparent;
    color: inherit;
    outline: none;
    box-shadow: none;
  }

  .w-filter {
    width: 165px !important;
    height: 34px !important;
  }

  .text-select-filter {
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 18px;
    width: 100px;
  }

  .text-select-filter2 {
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 18px;
    color: #359DD9;
    cursor: pointer;
  }

  .text-link {
    color: var(--Blue, #359DD9);
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 18px;
  }

  .text-title-modal {
    font-size: 16px;
    font-style: normal;
    font-weight: 700;
    line-height: 22px;
  }

  .text-subtitle-modal {
    font-size: 14px;
    font-style: normal;
    font-weight: 700;
    line-height: 22px;
  }

  .multi-filter {
    width: 1000px;
  }

  .combobox-item {
    border-bottom: 1px solid #AFAFAF;
    padding: 8px;
    margin-bottom: 0px;
    /* opacity: 50% !important; */
  }

  .bi::before,
  [class^="bi-"]::before,
  [class*=" bi-"]::before {
    display: inline-block;
    font-family: bootstrap-icons !important;
    font-style: normal;
    font-weight: normal !important;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    vertical-align: -.125em;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  .bi-eye-fill::before {
    content: url("{{ asset('public/css/images/caret-up-fill.svg') }}");
  }

  .bi-eye-slash-fill::before {
    content: url("{{ asset('public/css/images/caret-down-fill.svg') }}");
  }

  .bi-eye2::before {
    content: url("{{ asset('public/css/images/caret-up-fill.svg') }}");
  }

  .lines-no .datagrid-body td {
    border-right: 1px dotted transparent;
    border-bottom: 1px dotted transparent;
  }

  .lines-no2 .datagrid-header {
    border-right: 1px dotted transparent;
    border-bottom: 1px dotted transparent;
    background: #fff;
  }

  .col .datagrid-row-over,
  .col .datagrid-header td.datagrid-header-over {
    background: #fff;
    color: #000000;
    cursor: default;
  }

  .col .datagrid-row-selected {
    background: #fff;
    color: #000000;
  }
</style>
@endsection

@section('js_custom')
<script>
  $(function() {
    $('#dg-po-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
    $('#dg-po-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

    var dgspb = $('#dg-po').datagrid();
    dgspb.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
    dgspb.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
    dgspb.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
    dgspb.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
    dgspb.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
    dgspb.datagrid('getPanel').css('border', 'none');
    dgspb.datagrid('getPanel').addClass('lines-no');
    dgspb.datagrid('getPanel').addClass('lines-no3');

    $('#dg-po-mobile').datagrid('getPanel').find('div.datagrid-header').css('visibility', 'hidden');
    $('#dg-po-mobile').datagrid('getPanel').find('div.datagrid-header').addClass('h-0');
    $('#dg-po-mobile').datagrid('getPanel').css('border', 'none');
    $('#dg-po-mobile').datagrid('getPanel').addClass('lines-no');
    $('#dg-po-mobile').datagrid('getPanel').addClass('lines-no3');
  })

  function styler1(index, row) {
    return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;border-left:1px solid #ededed;border-top-left-radius:5px;' +
      'border-bottom-left-radius:5px;height:35px;';
  }

  function styler2(index, row) {
    return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;';
  }

  function styler3(index, row) {
    return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;border-right:1px solid #ededed;border-top-right-radius:5px;' +
      'border-bottom-right-radius:5px;';
  }

  function formatAttribute(value, row) {
    var hr = '<li><hr class="dropdown-divider mt-1 mb-1"></li>';
    var imageUrl = "{{ asset('public/css/images/More.png') }}"; // Pastikan asset ini tersedia
    var postingButton = '';

    if (!row.posting || row.posting == null) {
      // Belum posting
      postingButton = '<li><a class="dropdown-item dropdown-item-custom" id="btn-posting-' + (row.index || '') + '" onclick="KlikPosting(' + (row.popk || 'null') + ');" style="color:#359DD9;">Posting</a></li>';
    } else {
      // Sudah posting
      postingButton = '<li><a class="dropdown-item disabled">Posting</a></li>';
    }
      
    return '<div>' +
      '<button id="button-action-' + (row.index || '') + '" type="button" class="btn dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown">' +
      '<img src="' + imageUrl + '" height="15px" width="5px">' +
      '</button>' +
      (value || '') +
      '<ul id="dropdown-menu-action-' + (row.index || '') + '" class="dropdown-menu pt-1 pb-1">' +
      '<li><a class="dropdown-item dropdown-item-custom" id="btn-detail-' + (row.index || '') + '" onclick="KlikDetail(' + (row.popk || 'null') + ');" style="color:#359DD9;">Detail PO</a></li>' +
      hr +
      postingButton +
      hr +
      '<li><a class="dropdown-item dropdown-item-custom" id="btn-detail-' + (row.index || '') + '" onclick="KlikPrintPo(' + (row.popk || 'null') + ');" style="color:#359DD9;">Print</a></li>' +
      '</ul>' +
      '</div>';
  }

  function KlikPosting(popk){
    $('#popk').val(popk);
    $('#ConfirmPosting').modal('show');
  }

  function ConfirmPosting(){
    var popk = $('#popk').val();
      if (!popk) return;
      $.ajax({
          url: "{{ route('confirm.posting.po', '') }}/" + popk,
          method: 'POST',
          data: {
              "_token": "{{ csrf_token() }}",
          },
          success: function(response, textStatus, xhr) {
              const statusCode = xhr.status;
              console.log("HTTP Status Code:", statusCode);
              $('#ConfirmPosting').modal('hide');
              showAlert(statusCode, response.message);
              setTimeout(function() {
                  location.reload();
              }, 1300);
          },
          error: function(xhr, status, error) {
              console.error("Error detail:", xhr.responseText);
              alert('An error occurred. Please try again.');
          }
      });
  }

  function loadDataByFilter() {
    console.log(`loadDataByFilter RUNNING >>>>>>>>>>>>>>`);
    var searchByInput = $('#searchByInput').val();
    var filterByYear = $('#filterByYear').val();
    var filterByMonth = $('#filterByMonth').val();
    var sortlistByDate = $('#sortlistByDate').val();

    console.log(`searchByInput : ${searchByInput}`);
    $('#dg-po-shadow').datagrid('load', {
      searchByInput: searchByInput,
      filterByYear: filterByYear,
      filterByMonth: filterByMonth,
      sortlistByDate: sortlistByDate,
    });

    $('#dg-po').datagrid('load', {
      searchByInput: searchByInput,
      filterByYear: filterByYear,
      filterByMonth: filterByMonth,
      sortlistByDate: sortlistByDate,
    })
    console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
  }

  //FILTER SORT
  function sortlistByDate(value) {
    document.getElementById('sortlistByDate').value = value;
    if (value == 11) {
      $('#Invdatedesc').removeClass('d-none');
      $('#Invdateasc').addClass('d-none');
    } else if (value == 12) {
      $('#Invdatedesc').addClass('d-none');
      $('#Invdateasc').removeClass('d-none');
    } else {
      $('#Invdatedesc').removeClass('d-none');
      $('#Invdateasc').addClass('d-none');
    }
    loadDataByFilter();
  }

  function KlikDetail(popk) {
    const baseUrl = "{{ url('/')}}";
    window.location.href = baseUrl + "/purchase-order/detail/" + popk;
  }

  $(function() {
    $('#dg-po-shadow').datagrid({
      onLoadSuccess: function(data) {
        settingTableHeight(data.total);
      },
    })
  })

  function settingTableHeight(length) {
    var element = document.querySelector('.navbar');
    var computedStyle = window.getComputedStyle(element);
    var height = parseInt(computedStyle.getPropertyValue('height'));

    var screenWidth = window.innerWidth;
    var windowHeight = window.innerHeight - height * 5;

    console.log('height:', height);
    console.log('windowHeight:', windowHeight);

    if (length <= 6) {
      console.log("here <= 6");
      $('#dg-po').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
      $('#dg-po-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
    } else {
      console.log("here auto");
      $('#dg-po').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
      $('#dg-po-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
    }
  }

  function AddPo() {
    $.ajax({
      type: "POST",
      url: "{{ route('get.lastspo') }}",
      data: {
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function(data) {
        console.log(data);
        const baseUrl = "{{ url('/')}}";
        window.location.href = baseUrl + "/purchase-order/add-po/" + data.popk;
      },
      error: function(error) {
        console.log(error);
      }
    });
  }

  function KlikPrintPo(popk) {
    const baseUrl = "{{ url('/')}}";
    window.open(baseUrl + "/purchase-order/print/" + popk, "_blank");
  }

</script>
@endsection

@section('content')
<!-- Konfirmasi posting -->
<div class="modal fade" id="ConfirmPosting" tabindex="-1" aria-labelledby="ConfirmPostingLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog  modal-dialog-scrollable">
        <div class="modal-content p-4">
            <div class="modal-header d-flex flex-column m-0 p-0 py-2 border-0 align-items-start">
                <p class="p-0 m-0 fw-bold h5">Confirmation</p>
            </div>
            <div class="modal-body d-flex flex-column gap-3 m-0 p-0">
                <div class="d-flex text-left">
                    Pastikan data yang di isi sudah benar, posting akan di kirim dan tidak bisa edit lagi
                </div>
                <input type="hidden" id="popk">
            </div>
            <div class="modal-footer m-0 p-0 border-0">
                <div class="container-fluid">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="px-3 py-1 border-0 btn-transparent" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="px-3 py-1 btn-black rounded" data-bs-dismiss="modal" onclick="ConfirmPosting();">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="main-wrapper">
  <div class="container-fluid p-4">
    <div class="d-flex flex-column">
      <div class="p-2">
        <span class="txt-000000-700-24-28">List Purchase Order</span>
      </div>

      <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">

        <div class="p-0">
          <div class="input-group flex-nowrap input-group-search" style="height:34px;">
            <span class="input-group-text search">
              <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
            </span>
            <input type="text" class="form-control search" id="searchByInput" onkeyup="loadDataByFilter()" placeholder="Search...">
          </div>
        </div>


        <select class="easyui-combobox" id="filterByMonth" data-options="editable:false, onChange:loadDataByFilter" style="width:150px; height:34px;">
          <?php
          $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
          ];

          $currentMonth = date('m');
          foreach ($months as $key => $month) {
            $selected = ($key == $currentMonth) ? 'selected' : '';
            echo "<option value='$key' $selected>$month</option>";
          }
          ?>
        </select>

        <div class="p-0">
          <select class="easyui-combobox" id="filterByYear" data-options="editable:false, panelHeight:'auto', onChange:loadDataByFilter"
            style="width:100px;">
            <?php
            for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1) { ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php } ?>
          </select>
        </div>
        

        <button onclick="AddPo();" type="button" class="btn btn-dark" style="height:30px; line-height:30px; padding:0 10px;">&#10010; Create PO</button>

        <div class="ms-auto">
          <div id="sortlist-grup" class="p-1 pb-0 responsive">
            <input type="hidden" id="sortlistByDate" name="sortlistByDate" size="5">
            <button class="btn btn-sortlist dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown" data-bs-target="#menu-sortlist" aria-expanded="true">
              <img src="{{ asset('public/css/images/sort.jpg') }}" alt="" class="imgsort">
              <span id="Invdatedesc">Date PO (desc)</span>
              <span id="Invdateasc" class="d-none">Date PO (asc)</span>
            </button>
            <ul id="menu-sortlist" class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
              <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2" onclick="sortlistByDate(11)">Date PO (descending)</a></li>
              <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2" onclick="sortlistByDate(12)">Date PO (ascending)</a></li>
            </ul>
          </div>
        </div>

      </div>

      <div class="d-none">
        <table id="dg-po-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
          url="{{ route('api.get-po') }}">
        </table>
      </div>


      <div class="p-0">
      <table id="dg-po" class="easyui-datagrid" url="{{ route('api.get-po')}}" title="" style="width:99%;" align="center" toolbar="#tb" 
      striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" singleSelect="true" 
      collapsible="true" method="get"
        data-options="border:false">
        <thead>
          <tr>
              <th field="index" styler="styler1">No</th>
              <th field="tanggal" styler="styler2">Tanggal</th>
              <th field="nopo" styler="styler2">No PO</th>
              <th field="supnm" styler="styler2">Supplier</th>
              <th field="curid" styler="styler2">MU</th>
              <th field="totbeli" styler="styler2">Nilai</th>
              <th field="term" styler="styler2">Term</th>
              <th field="user" styler="styler2">User</th>
            <th field="act" styler="styler3" formatter="formatAttribute">Act</th>
          </tr>
        </thead>
      </table>
      </div>
    </div>
  </div>
</div>
@endsection