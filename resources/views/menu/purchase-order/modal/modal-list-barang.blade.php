<div class="modal fade" id="ModalLookUpBarang" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="border:none;">
                <h5>Lookup From Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="KlikCloseModalBarang();"></button>
            </div>

            <div class="d-flex flex-wrap ps-3 pb-2 gap-2 align-items-center">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInputBarang" onkeyup="loadDataByFilterModalBarang()" placeholder="Search...">
                </div>
            </div>

            <input hidden name="popk1" id="popk1" value="{{ $dt_po->popk }}">
            <div class="modal-body">
                <div>
                    <table id="dgLookUpBarang" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get-barang') }}"
                        align="center" toolbar="#tb" striped="true" pagination="true" fitColumns="true" idField="brgpk"
                        pageList="[100,200,300,500]"  pageSize="100" method="get" rownumbers="false" multiSelect="true" collapsible="true"
                        data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false, selectOnCheck:false">
                        <thead>
                            <tr>
                                <th field="ck" width="15" checkbox="true"></th>
                                <!-- <th field="index" width="4%">No</th> -->
                                <th field="brgnm" width="20%">Nama Barang</th>
                                <th field="noseri" width="20%">No Seri</th>
                                <th field="merknm" width="20%">Merk</th>
                                <th field="qtyawal" width="7%">Qty Awal</th>
                                <th field="qtymasuk" width="7%">Qty Masuk</th>
                                <th field="qtykeluar" width="7%">Qty Keluar</th>
                                <th field="qtyakhir" width="7%">Qty Akhir</th>
                                <th field="lastupdate" width="10%">Last Update</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="border:none;">
                <button type="button" id="get_buttonBarang" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookUpBarang()">
                    <i class="fa fa-square-check"></i> GET
                </button>
            </div>
        </div>
    </div>
</div>