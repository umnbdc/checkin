<div class="modal fade" id="payModal" role="dialog" aria-labelledby="payModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="payModalLabel">Pay</h4>
      </div>
      <div class="modal-body">
        <p>Current outstanding membership dues: <span id="payModalCurrentOutstanding"></span></p>
        <div class="form-group">
          <label for="inputCreditKind">Pay for:</label>
          <select class="form-control" id="inputCreditKind">
            <option value="Membership">Membership</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inputCreditMethod">Payment method:</label>
          <select class="form-control" id="inputCreditMethod">
            <option value="Cash">Cash</option>
            <option value="Check">Check</option>
            <option id="creditMethodForgivenessOption" style="display: none" value="Forgiveness">Forgiveness</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inputCreditAmount">Payment amount:</label>
          <div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="text" class="form-control" id="inputCreditAmount" aria-label="Amount (to the nearest dollar)">
            <span class="input-group-addon">.00</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="payButton" type="button" class="btn btn-primary">Pay</button>
      </div>
    </div>
  </div>
</div>
