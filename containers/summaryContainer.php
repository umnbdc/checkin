<div class="container primary-container" id="summaryContainer" style="display: none">
  <h2>
    Summary
  </h2>
  <div role="tabpanel">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation" class="active"><a id="daySummaryTab" href="#daySummaryPanel" aria-controls="day" role="tab" data-toggle="tab">Day</a></li>
      <li role="presentation"><a href="#weekSummaryPanel" aria-controls="week" role="tab" data-toggle="tab">Week</a></li>
      <li role="presentation"><a href="#termSummaryPanel" aria-controls="term" role="tab" data-toggle="tab">Term</a></li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
      <div role="tabpanel" class="tab-pane active" id="daySummaryPanel">
        <h3></h3>
        <table id="daySummaryTable" class="table table-condensed">
          <thead>
            <tr>
              <th>Check-ins</th>
              <th>New memberships</th>
              <th>Income</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td id="daySummaryTableCheckins"></td>
              <td id="daySummaryTableNewMemberships"></td>
              <td id="daySummaryTableIncome"></td>
            </tr>
          </tbody>
        </table>
        <div style="text-align: center; overflow: hidden">
          <div id="checkinsByMembershipPie" class="summaryChartBox"></div>
          <div id="newMembershipsPie" class="summaryChartBox"></div>
          <div id="checkinsByTimeAreaChart" class="summaryChartBox"></div>
        </div>
      </div>
      <div role="tabpanel" class="tab-pane" id="weekSummaryPanel"></div>
      <div role="tabpanel" class="tab-pane" id="termSummaryPanel"></div>
    </div>
  </div>
</div>