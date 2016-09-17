<div class="modal fade" id="membershipModal" role="dialog" aria-labelledby="membershipModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="membershipModalLabel">Change Membership</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="inputFeeStatus">Fee Status</label>
          <select class="form-control" id="inputFeeStatus">
            <option value="StudentServicesFees">Student Services Fees</option>
            <option value="Affiliate">Affiliate</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inputMembership">Membership</label>
          <select class="form-control" id="inputMembership">
            <option value="Full">Full</option>
            <option value="Competition">Competition</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="updateMembershipButton" type="button" class="btn btn-primary">Update</button>
      </div>
    </div>
  </div>
</div>
