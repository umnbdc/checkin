<div class="modal fade" id="transactionsModal" role="dialog" aria-labelledby="transactionsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="transactionsModalLabel">Transactions</h4>
      </div>
      <div class="modal-body">
        <p>Cash and check credits this term</p>
        <table id="transactionsModalTable" class="table table-condensed">
          <thead>
            <tr>
              <th>Member</th>
              <th>Credit/Debit</th>
              <th>Amount</th>
              <th>Kind</th>
              <th>Method</th>
              <th>Charged/Paid</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
        <form id="transactionCSVForm" action="/resources/lib/csv.php" method="POST">
          <textarea id="transactionCSVFormObjectsField" name="objects" style="display: none"></textarea>
          <textarea id="transactionCSVFormKeysField" name="keys" style="display: none"></textarea>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button id="transactionsModalButton" class="btn btn-primary">Export to CSV</button>
        <script type="text/javascript">
          $("#transactionsModalButton").click(function() { $("#transactionCSVForm").submit(); });
        </script>
      </div>
    </div>
  </div>
</div>
