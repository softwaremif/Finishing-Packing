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
                    <input type="text" class="form-control search" id="searchByInputOrder" onkeyup="loadDataByFilterModalPr()" placeholder="Search...">
                </div>

                <select class="easyui-combobox" id="filterByMonthPr" data-options="editable:false, onChange:loadDataByFilterModalPr" style="width:150px; height:34px;">
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
                    <select class="easyui-combobox" id="filterByYearPr" data-options="editable:false, panelHeight:'auto', onChange:loadDataByFilterModalPr"
                        style="width:100px; height:34px;">
                        <?php
                        for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <input type="hidden" name="popk2" id="popk2" value="{{ $dt_po->popk }}">

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