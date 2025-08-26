<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- <div class="modal-header">
        <h1 class="modal-title fs-5" id="passwordModalLabel">Modal title</h1>
      </div> -->
      <div class="modal-body mt-2">
        <div class="container-fluid">
          <div class="d-flex flex-column gap-0 pt-0">
            <div class="p-1">
              <h6 id="passwordModalLabel" class="modal-title modal-title-custom title-password">Updating Password</h6>
            </div>
            <div class="d-flex flex-row p-1 pt-3">
              <form id="formChangePassword">
                <input id="userpk" name="userpk" type="text" value="{{Session::get('userpk')}}" hidden>
                <div class="pb-0 d-none">
                  <label for="password-1" class="form-label label-password">Current Password</label>
                </div>
                <div class="pb-2 d-none">
                  <input id="password-1" name="password-1" type="password" class="easyui-validatebox validatebox-text" autocomplete="false" required>
                  <i class="bi bi-eye-slash" id="i-eye-password-1" style="margin-left: -30px; cursor: pointer;" onmouseover="showHiddenPassword(1);" onmouseout="showHiddenPassword(1);"></i>
                </div>
                <div class="pb-0">
                  <label for="password-2" class="form-label label-password">New Password</label>
                </div>
                <div class="pb-2">
                  <input id="password-2" name="password-2" type="password" class="easyui-validatebox validatebox-text" autocomplete="false">
                  <i class="bi bi-eye-slash" id="i-eye-password-2" style="margin-left: -30px; cursor: pointer;" onmouseover="showHiddenPassword(2);" onmouseout="showHiddenPassword(2);"></i>
                </div>
                <div class="pb-0">
                  <label for="password-3" class="form-label label-password">Confirm New Password</label>
                </div>
                <div class="pb-2">
                  <input id="password-3" name="password-3" type="password" class="easyui-validatebox validatebox-text" autocomplete="false">
                  <i class="bi bi-eye-slash" id="i-eye-password-3" style="margin-left: -30px; cursor: pointer;" onmouseover="showHiddenPassword(3);" onmouseout="showHiddenPassword(3);"></i>
                </div>
              </form>
            </div>
            <div id="error-change-password" class="p-1">
              <li id="eror1" class="text-danger" style="display: none">Password must be at least 8 characters and contain upper case, lowercase, numbers, and special characters.</li>
              <li id="eror2" class="text-danger" style="display: none">New password is required.</li>
              <li id="eror3" class="text-danger" style="display: none">Confirm new password is required.</li>
              <li id="eror4" class="text-danger" style="display: none">Password confirmation does not match.</li>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top:none;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" onclick="cancelChangePassword()">Cancel</button>
        <button type="button" class="btn btn-dark" onclick="changepswd()">Submit</button>
      </div>
    </div>
  </div>
</div>