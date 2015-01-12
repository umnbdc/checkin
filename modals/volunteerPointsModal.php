<div class="modal fade" id="volunteerPointsModal" role="dialog" aria-labelledby="volunteerPointsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="volunteerPointsModalLabel">Add Volunteer Points</h4>
      </div>
      <div class="modal-body">
        <p>Each volunteer point is worth a credit of $6.</p>
        <p>Adding volunteer points should only be done at one time at the start of the term.</p>
        <p>Current outstanding membership dues: <span id="volunteerPointsModalCurrentOutstanding"></span></p>
        <div class="form-group">
          <label for="inputPointsAmount">Number of points to add:</label>
          <div class="input-group">
            <input type="text" class="form-control" id="inputPointsAmount" aria-label="Number of points">
            <span class="input-group-addon">points</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="volunteerPointsButton" type="button" class="btn btn-primary">Credit</button>
      </div>
    </div>
  </div>
</div>
