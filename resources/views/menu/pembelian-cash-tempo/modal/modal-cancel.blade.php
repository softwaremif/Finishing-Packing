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
                        <button type="button" class="px-3 py-1 border-0 btn-transparent" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="px-3 py-1 btn-black rounded" data-bs-dismiss="modal" onclick="ConfirmDeletePembelian();">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>