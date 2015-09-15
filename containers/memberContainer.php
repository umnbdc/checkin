<div class="container primary-container" id="memberContainer" style="display: none">
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
        <th>Proficiency</th>
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
      <button type="button" id="memberInfoPurchaseButton" class="btn btn-default" data-toggle="modal" data-target="#purchaseModal">Purchase</button>
      <button type="button" id="memberInfoVolunteerPointsButton" style="display: none" class="btn btn-default" data-toggle="modal" data-target="#volunteerPointsModal">Volunteer Points</button>
      <button type="button" id="memberInfoWaiverButton" style="display: none" class="btn btn-default" data-toggle="modal" data-target="#waiverModal">Waiver</button>
      <button type="button" id="memberInfoMembershipButton" class="btn btn-default" data-toggle="modal" data-target="#membershipModal">Membership and Fee Status</button>
    </div>
  </p>
  
  <div class="panel-group" id="memberPanels" role="tablist" aria-multiselectable="true">
    <div class="panel panel-default">
      <div class="panel-heading" role="tab" id="creditsAndDebitsPanelHeading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#memberPanels" href="#creditsAndDebitsPanel" aria-expanded="true" aria-controls="creditsAndDebitsPanel">
            Credits and debits
          </a>
        </h4>
      </div>
      <div id="creditsAndDebitsPanel" class="panel-collapse collapse" role="tabpanel" aria-labelledby="creditsAndDebitsPanelHeading">
        <div class="panel-body">
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
        </div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading" role="tab" id="rewardsPanelHeading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#memberPanels" href="#rewardsPanel" aria-expanded="true" aria-controls="rewardsPanel">
            Rewards
          </a>
        </h4>
      </div>
      <div id="rewardsPanel" class="panel-collapse collapse" role="tabpanel" aria-labelledby="rewardsPanelHeading">
        <div class="panel-body">
          <table id="memberRewardsTable" class="table table-condensed table-hover">
            <thead>
              <tr>
                <th>Kind</th>
                <th>Term</th>
                <th>Issue Date</th>
                <th>Claimed</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading" role="tab" id="checkinHistoryPanelHeading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#memberPanels" href="#checkinHistoryPanel" aria-expanded="true" aria-controls="checkinHistoryPanel">
            Check in history
          </a>
        </h4>
      </div>
      <div id="checkinHistoryPanel" class="panel-collapse collapse" role="tabpanel" aria-labelledby="checkinHistoryPanelHeading">
        <div class="panel-body">
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
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading" role="tab" id="referencesPanelHeading">
        <h4 class="panel-title">
          <a data-toggle="collapse" data-parent="#memberPanels" href="#referencesPanel" aria-expanded="true" aria-controls="referencesPanel">
            References
          </a>
        </h4>
      </div>
      <div id="referencesPanel" class="panel-collapse collapse" role="tabpanel" aria-labelledby="referencesPanelHeading">
        <div class="panel-body">
          <p id="referredByP">Referred by: <span id="referredBySpan"></span></p>
          <table id="referredTable" class="table table-condensed table-hover">
            <thead>
              <tr>
                <th>Referred</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
</div>