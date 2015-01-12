<div class="container" id="memberContainer" style="display: none">
  <h2>
    <span class="firstName"></span>
    <span class="lastName"></span>
  </h2>
  <table id="memberInfoTable" class="table table-condensed">
    <thead>
      <tr>
        <th>First</th>
        <th>Last</th>
        <th>Nickname</th>
        <th>Email</th>
        <th>Membership</th>
        <th>Fee Status</th>
        <th>Waiver</th>
        <th>Outstanding Membership Dues</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
  <p>
    <div class="btn-group">
      <button type="button" id="memberInfoCheckinButton" class="btn btn-primary">Check in</button>
      <button type="button" id="memberInfoEditButton" class="btn btn-default" data-toggle="modal" data-target="#editMemberModal">Edit info</button>
      <button type="button" id="memberInfoPayButton" class="btn btn-default" data-toggle="modal" data-target="#payModal">Pay</button>
      <button type="button" id="memberInfoVolunteerPointsButton" style="display: none" class="btn btn-default" data-toggle="modal" data-target="#volunteerPointsModal">Volunteer Points</button>
      <button type="button" id="memberInfoWaiverButton" class="btn btn-default" data-toggle="modal" data-target="#waiverModal">Waiver</button>
      <button type="button" id="memberInfoMembershipButton" class="btn btn-default" data-toggle="modal" data-target="#membershipModal">Membership and Fee Status</button>
    </div>
  </p>
  <h3>Credits and debits</h3>
  <table id="memberCreditDebitTable" class="table table-condensed">
    <thead>
      <tr>
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
  <h3>Check In History</h3>
  <table id="memberHistoryTable" class="table table-condensed">
    <thead>
      <tr>
        <th>When</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
</div>