<div class="modal fade" id="checkinErrorModal" role="dialog" aria-labelledby="checkinErrorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="checkinErrorModalLabel">Checkin Error</h4>
      </div>
      <div class="modal-body">
        <p>This member could not be checked in. Reason: "<span id="checkInErrorReason"></span>"</p>
        <p>Please check the member's membership.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="intermediateButton" type="button" class="btn btn-warning">Make Intermediate</button>
        <button id="onePassButton" type="button" class="btn btn-warning">One Lesson Pass</button>
        <button id="overrideButton" type="button" class="btn btn-danger">Override</button>
      </div>
    </div>
  </div>
</div>
