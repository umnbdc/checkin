<div class="modal fade" id="purchaseModal" role="dialog" aria-labelledby="purchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="purchaseModalLabel">Pay</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="inputPurchaseKind">Purchase:</label>
          <select class="form-control" id="inputPurchaseKind">
            <option value="Jacket">Jacket ($45)</option>
            <option value="Shoes_Men">Men's Shoes ($30)</option>
            <option value="Shoes_Women">Women's Shoes ($25)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inputPurchaseMethod">Payment method:</label>
          <select class="form-control" id="inputPurchaseMethod">
            <option value="Cash">Cash</option>
            <option value="Check">Check</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="purchaseButton" type="button" class="btn btn-primary">Purchase</button>
      </div>
    </div>
  </div>
</div>
