<div class="modal fade" id="waiverModal" role="dialog" aria-labelledby="waiverModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="waiverModalLabel">Set Waiver</h4>
      </div>
      <div class="modal-body">
        <p>This action should be done by the Safety and Facilities Coordinator.</p>
        <p>Current term: <span id="waiverModalCurrentTerm"></span></p>
        <div class="form-group">
          <label for="inputWaiverStatus">Waiver Status</label>
          <select class="form-control" id="inputWaiverStatus">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="updateWaiverButton" type="button" class="btn btn-primary">Update</button>
      </div>
    </div>
  </div>
</div>
