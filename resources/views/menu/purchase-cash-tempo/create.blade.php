@extends('layout.main')

@section('css_custom')
    @include('menu.purchase-cash-tempo.parts.css')
@endsection

@section('js_custom')
    @include('menu.purchase-cash-tempo.parts.js')
@endsection

@section('content')
    <div class="main-wrapper min-vh-100">
        <div class="container-fluid">
            <div class="d-flex flex-column p-4 m-3">

                <!-- FORM ATAS -->
                <form id="form_prpk">
                    @csrf
                    <div class="d-flex gap-3">
                        <a href="{{ route('po-cash-tempo.index') }}" id="back_edit" class="d-flex pointer">
                            <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                        </a>
                        <div id="back_input" class=" d-none d-flex pointer" onclick="back_input_barang()">
                            <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                        </div>
                        <div class="d-flex flex-column flex-fill">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex fw-bold " id="notran"></div>

                                <div class="d-flex gap-1 d-none" id="edit_data">
                                    @if (isset($beli) && $beli->userpk == Session('userpk'))
                                        <button class="btn-black rounded px-3"
                                            onclick="event.preventDefault(); edit_data();">Edit</button>
                                    @endif
                                </div>

                                <div class="d-flex gap-4 d-none" id="cancel_submit">
                                    <button class="btn-transparent border-0 fw-bold"
                                        onclick="event.preventDefault(); back_input_barang()">Cancel</button>
                                    <button class="btn-black rounded px-3 d-none" id="save_submit"
                                        onclick="event.preventDefault(); submit_pr('submit');">Submit</button>
                                </div>

                                <div class="d-flex gap-4 d-none" id="cancel_save_edit">
                                    <button class="btn-transparent border-0 fw-bold"
                                        onclick="event.preventDefault(); cancel_edit()">Cancel</button>
                                    <button class="btn-black rounded px-3 d-none" id="save_edit"
                                        onclick="event.preventDefault(); submit_pr('edit');">Save</button>
                                </div>
                            </div>
                            <div class="d-flex my-3">
                                <div class="d-flex flex-column flex-fill">
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">No. Bukti</div>
                                        <input id="nobukti" class="easyui-validatebox height_input" readonly
                                            name="nobukti">
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">No. Invoice</div>
                                        <input id="noinvoice" class="easyui-validatebox height_input" name="noinv"
                                            value="{{ isset($beli) ? $beli->noinv : '' }}"
                                            placeholder="Masukkan No. Invoice">
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Tgl Invoice </div>
                                        <input id="tglInvoice" name="tglinv" class="easyui-datebox" style="width:220px;"
                                            value="{{ isset($beli) ? \Carbon\Carbon::parse($beli->tglinv)->format('d/m/Y') : now() }}"
                                            placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser">
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Term</div>
                                        <select class="easyui-combobox" id="term" name="abpk"
                                            onchange="loadDataByFilter(this.value)"
                                            data-options="editable:false, panelHeight:'auto'"
                                            style="width:220px;height:34px;">
                                            @foreach ($ab as $item)
                                                @if (isset($beli))
                                                    @if ($item->abpk == $beli->abpk)
                                                        <option value="{{ $item->abpk }}" selected>{{ $item->abnm }}
                                                        </option>
                                                    @else
                                                        <option value="{{ $item->abpk }}">{{ $item->abnm }}</option>
                                                    @endif
                                                @else
                                                    <option value="{{ $item->abpk }}">{{ $item->abnm }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Mata Uang</div>
                                        <select class="easyui-combobox" id="concurrency" name="curpk"
                                            onchange="loadDataByFilter(this.value)"
                                            data-options="editable:false, panelHeight:'auto'"
                                            style="width:220px; height:34px;">
                                            @foreach ($cur as $item)
                                                @if (isset($beli))
                                                    @if ($item->curpk == $beli->curpk)
                                                        <option value="{{ $item->curpk }}" selected>{{ $item->curnm }}
                                                        </option>
                                                    @else
                                                        <option value="{{ $item->curpk }}">{{ $item->curnm }}</option>
                                                    @endif
                                                @else
                                                    <option value="{{ $item->curpk }}">{{ $item->curnm }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-fill">
                                </div>
                                <div class="d-flex flex-column flex-fill">
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Supplier</div>
                                        <input id="sup" class="easyui-combobox col-2" style="width: 220px;"
                                            name="suppk" method="get" placeholder="Masukkan Supplier"
                                            value="{{ isset($beli) ? $beli->suppk : '' }}"
                                            data-options="valueField:'suppk', textField:'supnm', url:'{{ route('api.get-sup') }}', editable:true, limitToList:'true'">
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Jenis</div>
                                        <input id="jenis" class="easyui-combobox col-2"
                                            style="height: 30px; width: 220px;" name="kelpk" method="get"
                                            value="{{ isset($beli) ? $beli->kelpk : '' }}" placeholder="Masukkan Jenis"
                                            data-options="valueField:'kelpk', textField:'kelnm', url:'{{ route('api.get-dep') }}', editable:true, limitToList:'true'">
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Untuk</div>
                                        <input id="untuk" class="easyui-combobox col-2"
                                            style="height: 30px; width: 220px;" name="mifpk" method="get"
                                            placeholder="Masukkan Untuk" value="{{ isset($beli) ? $beli->mifpk : '' }}"
                                            data-options="valueField:'mifpk', textField:'mifnm', url:'{{ route('api.get-mif') }}', editable:true, limitToList:'true', panelHeight:'auto'">
                                    </div>
                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Total</div>
                                        <input id="total" class="easyui-validatebox height_input" name="totbeli"
                                            value="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="d-flex align-items-center gap-2">

                    <div class="d-flex justify-content-end align-items-center gap-3 mb-3">
                        <div id="add" class="p-0 d-none d-flex gap-3">
                            <div class="font-5 color-view-blue pointer fw-bold" onclick="tambahrow();"
                                style="color:#359DD9; text-decoration:none;">Add Data</div>
                            <div class="font-5 blue pointer fw-bold d-none" id="savedt" onclick="saverow();">Save
                            </div>
                        </div>

                        <div id="lookuppr" class="flex-grow-0 d-none">
                            <button onclick="KlikLoopUpPr()" class="btn btn-sm btn-dark">Lookup PR</button>
                        </div>

                    </div>

                </div>

                <div class="p-0">
                    <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb"
                        striped="false" pagination="true" method="get" rownumbers="false"
                        pageList="[100,200,300,500]" pageSize="100" singleSelect="false" collapsible="true"
                        fitColumns="true" idField="index"
                        data-options="onAfterEdit: function(index, row, changes) {hitungTotalJumlahHarga(); simpanTotal();}, onCheck:function(){menu();}, onCheckAll:function(){menu();}, onUncheck:function(){menu();}, onUncheckAll:function(){},
                        multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false,
                        rowStyler:function(index,row){
                            if (row.copy==3){
                                return 'color:#000000;background-color:#D6D6D6;';
                            }
                        }">
                        <thead>
                            <tr>
                                <th field="ck" width="auto" styler="styler1" checkbox="true" hidden="true"></th>
                                <th field="index" width="3%" styler="styler2" editor="disabled">No</th>
                                <th field="brgnm" width="auto" styler="styler2" editor="text">Keterangan</th>
                                <th field="jmlbeli" width="auto" styler="styler2" editor="text">Qty</th>
                                <th field="unit" width="auto" styler="styler2" editor="text">Satuan</th>
                                <th field="hrgbeli" width="auto" styler="styler2" formatter="money" editor="text">
                                    Harga Satuan</th>
                                <th field="jmlhrg" width="auto" formatter="money" styler="styler3" editor="disabled">
                                    Jumlah Harga</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="header" id="myHeader" onclick="" style="display:none;">
        <div id="count" style="display:none;"></div>

        <div class="delete" id="mdelete" style="width:auto; display:none;">
            <a href="javascript:void(0)" plain="true" onclick="delete_list_podt();"
                style="color: #FFFFFF; text-decoration:none;"> Delete </a>
        </div>

        <div class="delete" id="mclose" style="width:auto;">
            <a href="javascript:void(0)" plain="true"
                onclick="$('#myHeader').hide(); $('#dg').datagrid('clearSelections'); $('#dg').datagrid('clearChecked'); closemenu();"
                style="color: #FFFFFF; text-decoration:none;"> Close menu </a>
        </div>
    </div>

    <!-- Konfirmasi cancel -->
    <div class="modal fade" id="ConfirmCancel" tabindex="-1" aria-labelledby="ConfirmCancelLabel" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog  modal-dialog-scrollable">
            <div class="modal-content p-4">
                <div class="modal-header d-flex flex-column m-0 p-0 py-2 border-0 align-items-start">
                    <p class="p-0 m-0 fw-bold h5">Confirmation</p>
                </div>
                <div class="modal-body d-flex flex-column gap-3 m-0 p-0">
                    <div class="d-flex text-left">
                        Data yang sudah diisi akan hilang jika Anda keluar dari halaman ini.
                        Mohon konfirmasinya.
                    </div>
                </div>
                <div class="modal-footer m-0 p-0 border-0">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-end">
                            <button type="button" class="px-3 py-1 border-0 btn-transparent"
                                data-bs-dismiss="modal">Close</button>
                            <button type="button" class="px-3 py-1 btn-black rounded" data-bs-dismiss="modal"
                                onclick="ConfirmDelete();">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ModalLookUpPr" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            
            <div class="modal-header" style="border:none;">
                <h5>Lookup From Purchase Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="KlikCloseModalPr();"></button>
            </div>

            <div class="d-flex flex-wrap ps-3 pb-2 gap-2 align-items-center">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInputOrder" onkeyup="SearchPurchaseRequest()" placeholder="Search...">
                </div>
            </div>

            <input type="text" name="belipk2" id="belipk2" value="">

            <div class="modal-body">
                <div>
                    <table id="dgLookUpPr" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get-lookup-detpr') }}"
                        align="center" toolbar="#tb" striped="true" pagination="true" fitColumns="true" idField="prdtpk"
                        pageList="[100,200,300,500]"  pageSize="100" method="get" rownumbers="false" multiSelect="true" collapsible="true"
                        data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false">
                        <thead>
                            <tr>
                                <th field="ck" width="15" checkbox="true"></th>
                                <th field="tanggal" width="10%">Tanggal</th>
                                <th field="nopr" width="9%">No PR</th>
                                <th field="brgnm" width="14%">Nama Barang/Spesifikasi</th>
                                <th field="unit" width="8%">Unit</th>
                                <th field="jmlbeli" width="8%">Total Item</th>
                                <th field="user" width="10%">Pengirim</th>
                                <th field="depnm" width="10%">Penerima</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="nilai_unit_filter" name="nilai_unit_filter">
                <button type="button" id="get_button" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookup()">
                   <i class="fa fa-square-check"></i> GET
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
