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
                    <input type="text" class="form-control search" id="searchByInputPr" onkeyup="SearchPurchaseRequest()" placeholder="Search...">
                </div>
            </div>

            <input type="hidden" name="belipk2" id="belipk2" value="{{ $dt_beli->belipk }}">

            <div class="modal-body">
                <div>
                    <table id="dgLookUpPr" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get-lookup-detpr-in-pembelian') }}"
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
                <button type="button" id="get_button" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookUp()">
                   <i class="fa fa-square-check"></i> GET
                </button>
            </div>
        </div>
    </div>
</div>