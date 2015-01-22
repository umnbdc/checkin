var apiURL = "data.php";

var CURRENT_TERM = "";

function isUndefined(e) {
  return typeof e === 'undefined';
}

function setEnvironment(async) {
  function environmentSuccess(data, textStatus, jqXHR) {
    console.log("Environment retrieval successful: ", data, textStatus, jqXHR);
    CURRENT_TERM = data.CURRENT_TERM;
  }
  
  function environmentError(data, textStatus, jqXHR) {
    console.log("Environment retrieval failed: ", data, textStatus, jqXHR);
    alert("There was an issue retrieving environment info. Please try again.");
  }
  
  authAjax({
    async: async,
    type: "POST",
    url: apiURL,
    data: {type: "environment"},
    success: environmentSuccess,
    error: environmentError,
    dataType: 'json'
  });
}

// returns true if list contains an empty string
function containsEmptyString(list) {
  return list.some(function(e) {
    return e === "";
  });
}

function isPositiveIntegerString(string) {
  return /^[1-9][0-9]*$/.test(string);
}

function hidePrimaryContainers() {
  $(".primary-container").hide();
}

function addNewMember() {
  var firstName = $('#inputFirstName').val();
  var lastName = $('#inputLastName').val();
  var nickname = $('#inputNickname').val();
  var email = $('#inputEmail').val();
  var referrerFormData = $("#newMemberReferForm").serializeArray();
  var referredBy = referrerFormData.length > 0 ? referrerFormData[0].value : null;

  if ( containsEmptyString([firstName, lastName, email]) ) {
    alert("Missing member information. First name, last name, and email are required.");
    return;
  }
    
  var memberObject = {
    firstName: firstName,
    lastName: lastName,
    nickname: nickname,
    email: email,
    referredBy: referredBy
  }
  
  function addNewMemberSuccess(data, textStatus, jqXHR) {
    console.log("New member submission successful: ", data, textStatus, jqXHR);
    if ( data.succeeded ) {
      $('#newMemberModal').modal('hide');
      showMember(data.member.id);
    } else if ( data.reason ) {
      alert(data.reason);
    } else {
      addNewMemberError(data, textStatus, jqXHR);
    }
  }
  
  function addNewMemberError(data, textStatus, jqXHR) {
    console.log("New member submission failed: ", data, textStatus, jqXHR);
    alert("There was an issue adding this member. Please try again.");
  }
  
  var data = {type: "newMember", member: memberObject};
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: data,
    success: addNewMemberSuccess,
    error: addNewMemberError,
    dataType: 'json'
  }); 
}

function runSearch(untrack) { // untrack optional, default: false
  var query = $("#memberSearch").val();
  var members = getMembers(query);
  showMemberList(members);
  if ( isUndefined(untrack) || untrack == false ) { 
    history.pushState({page: "list", query: query}, "Member Search", "?search="+query);
  }
}

function getSummaryData(summary_kind, day) {
  var responseData = undefined;
  
  function success(data, textStatus, jqXHR) {
    console.log("Summary data retrieval successful: ", data, textStatus, jqXHR);
    responseData = data;
  }

  function error(data, textStatus, jqXHR) {
    console.log("Summary data retrieval failed: ", data, textStatus, jqXHR);
    alert("There was an issue retrieving summary data. Please try again.");
  }
  
  authAjax({
    async: false,
    type: "POST",
    url: apiURL,
    data: {type: "getSummaryData", summary_kind: summary_kind, day: day},
    success: success,
    error: error,
    dataType: 'json'
  });
  
  return responseData;
}

function formatDayString(string) {
  var day = isUndefined(string) ? new Date() : new Date(string);
  var toReturn = {};
  toReturn.weekday = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][day.getDay()];
  toReturn.dateString = day.getFullYear() + "-" + (day.getMonth()+1) + "-" + day.getDate();
  return toReturn;
}

function fillDaySummaryTab(dayString) {
  var day = formatDayString(dayString);
  $("#daySummaryPanel h3").html(day.weekday + " " + day.dateString);
  var summaryData = getSummaryData('day', day.dateString);
  
  $("#daySummaryTableCheckins").html(summaryData.checkins.length);
  $("#daySummaryTableNewMemberships").html(summaryData.newMemberships.length);
  $("#daySummaryTableIncome").html(formatAmount(summaryData.credits.reduce(function(accum,credit) { return accum + parseInt(credit.amount); },0)));
  
  var charts = [];
  
  if ( summaryData.checkins.length > 0 ) {
    var checkinMembershipCounts = {};
    var keys = [];
    summaryData.checkins.forEach(function (c) {
      if ( c.membership in checkinMembershipCounts ) {
        checkinMembershipCounts[c.membership] += 1;
      } else {
        checkinMembershipCounts[c.membership] = 1;
        keys.push(c.membership);
      }
    });
    var checkinsByMembershipDataPoints = [];
    keys.forEach(function (k) {
      checkinsByMembershipDataPoints.push({name: k, y: checkinMembershipCounts[k]});
    });
    
    charts.push(new CanvasJS.Chart("checkinsByMembershipPie", {
      title: {text: "Checkins by Membership"},
      animationEnabled: false,
      legend: {
        verticalAlign: "bottom",
        horizontalAlign: "center",
        fontSize: 16
      },
      theme: "theme1",
      data: [{        
        type: "doughnut",      
        indexLabelFontSize: 16,
        startAngle:0,
        indexLabelFontColor: "black",       
        indexLabelLineColor: "darkgrey", 
        indexLabelPlacement: "outside", 
        toolTipContent: "{name}: {y} checkins",
        showInLegend: true,
        indexLabel: "{y} (#percent%)", 
        dataPoints: checkinsByMembershipDataPoints
      }]
    }));
  }
  
  if ( summaryData.newMemberships.length > 0 ) {
    var newMembershipCounts = {};
    var keys = [];
    summaryData.newMemberships.forEach(function (m) {
      if ( m.kind in newMembershipCounts ) {
        newMembershipCounts[m.kind] += 1;
      } else {
        newMembershipCounts[m.kind] = 1;
        keys.push(m.kind);
      }
    });
    var newMembershipDataPoints = [];
    keys.forEach(function (k) {
      newMembershipDataPoints.push({name: k, y: newMembershipCounts[k]});
    });
    
    charts.push(new CanvasJS.Chart("newMembershipsPie", {
      title: {text: "New Memberships"},
      animationEnabled: false,
      legend: {
        verticalAlign: "bottom",
        horizontalAlign: "center",
        fontSize: 16
      },
      theme: "theme1",
      data: [{        
        type: "doughnut",      
        indexLabelFontSize: 16,
        startAngle:0,
        indexLabelFontColor: "black",       
        indexLabelLineColor: "darkgrey", 
        indexLabelPlacement: "outside", 
        toolTipContent: "{name}: {y} checkins",
        showInLegend: true,
        indexLabel: "{y} (#percent%)", 
        dataPoints: newMembershipDataPoints
      }]
    }));
  }
  
  charts.forEach(function(c) { c.render(); });
}

function fillWeekSummaryTab(dayString) {
}

function fillTermSummaryTab() {
}

function showSummaryContainer(untrack) { // untrack optional, default: false
  $('#daySummaryTab').on('shown.bs.tab', function() {fillDaySummaryTab();});
  $('#daySummaryTab').on('shown.bs.tab', function() {fillWeekSummaryTab();});
  $('#daySummaryTab').on('shown.bs.tab', function() {fillTermSummaryTab();});

  hidePrimaryContainers();
  $("#summaryContainer").show();
  
  fillDaySummaryTab();
  
  if ( isUndefined(untrack) || untrack == false ) { 
    history.pushState({page: "summary"}, "Summary", "?summary=true");
  }
}

function getElementOfTerm(elements, term) {
  var element = null;
  elements.forEach(function(e) {
    if ( e.term == term ) {
      element = e;
    }
  });
  return element;
}

function calculateMembershipDuesBalance(transactions) {
  // only considers credits/debits with kind matching "Membership *"
  var balance = 0;
  transactions.forEach(function(t) {
    if ( t.kind.indexOf("Membership") == 0 ) {
      balance += parseInt(t.amount);
    }
  });
  return balance;
}

function formatAmount(amount) {
  var negString = "";
  var classString = "transactionAmount";
  if ( amount < 0 ) {
    negString = "-";
    amount = -1 * amount;
    classString = classString + " negative";
  } else if ( amount > 0 ) {
    classString = classString + " positive";
  }
  var cents = amount % 100;
  var dollars = Math.floor(amount / 100);
  var centString = cents == 0 ? "00" : cents;
  var dollarString = dollars == 0 ? "0" : dollars;
  
  return "<span class='" + classString + "'>" + negString + "$" + dollarString + "." + centString + "</span>";
}

function updateMember(id) {
  firstName = $("#inputEditFirstName").val();
  lastName = $("#inputEditLastName").val();
  nickName = $("#inputEditNickname").val();
  email = $("#inputEditEmail").val();
  
  function updateMemberSuccess(data, textStatus, jqXHR) {
    console.log("Update member info successful: ", data, textStatus, jqXHR);
    $("#editMemberModal").modal('hide');
    refreshMember(id);
  }
  
  function updateMemberError(data, textStatus, jqXHR) {
    console.log("Update member info failed: ", data, textStatus, jqXHR);
    alert("There was an issue updating this member's info. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {
      type: "updateMember",
      id: id,
      firstName: firstName,
      lastName: lastName,
      nickName: nickName,
      email: email
    },
    success: updateMemberSuccess,
    error: updateMemberError,
    dataType: 'json'
  }); 
}

function updateMembershipAndFeeStatus(id) {
  var feeStatus = $("#inputFeeStatus").val();
  var membership = $("#inputMembership").val();
  
  function updateMembershipSuccess(data, textStatus, jqXHR) {
    console.log("Update membership successful: ", data, textStatus, jqXHR);
    $("#membershipModal").modal('hide');
    refreshMember(id);
  }
  
  function updateMembershipError(data, textStatus, jqXHR) {
    console.log("Update membership failed: ", data, textStatus, jqXHR);
    alert("There was an issue updating this membership and fee status. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {
      type: "updateMembershipAndFeeStatus",
      id: id,
      feeStatus: feeStatus,
      membership: membership,
      term: CURRENT_TERM
    },
    success: updateMembershipSuccess,
    error: updateMembershipError,
    dataType: 'json'
  }); 
}

function payDialogSubmit(id) {
  var kind = $("#inputCreditKind").val();
  var method = $("#inputCreditMethod").val();
  var amount = $("#inputCreditAmount").val();
  
  if ( containsEmptyString([kind, method]) ) {
    alert("Kind and method cannot be empty strings");
    return;
  }
  
  if ( !isPositiveIntegerString(amount) ) {
    alert("Amount must be a positive integer");
    return;
  }
  amount = amount * 100; // amount needs to be in cents
  
  function addPaymentSuccess(data, textStatus, jqXHR) {
    console.log("Payment submission successful: ", data, textStatus, jqXHR);
    $('#payModal').modal('hide');
    refreshMember(id);
  }
  
  function addPaymentError(data, textStatus, jqXHR) {
    console.log("Payment submission failed: ", data, textStatus, jqXHR);
    alert("There was an issue submitting this payment. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "payment", kind: kind, method: method, amount: amount, member_id: id},
    success: addPaymentSuccess,
    error: addPaymentError,
    dataType: 'json'
  }); 
}

function volunteerPointsDialogSubmit(member_id) {
  var points = $("#inputPointsAmount").val();
  if ( !isPositiveIntegerString(points) ) {
    alert("Points must be a positive integer");
    return;
  }
  
  function addVolunteerPointsSuccess(data, textStatus, jqXHR) {
    console.log("Volunteer points submission successful: ", data, textStatus, jqXHR);
    if ( data.succeeded ) {
      $('#volunteerPointsModal').modal('hide');
      refreshMember(member_id);
    } else if ( data.reason ) {
      alert(data.reason);
      $('#volunteerPointsModal').modal('hide');
    } else {
      addVolunteerPointsError(data, textStatus, jqXHR);
    }
  }
  
  function addVolunteerPointsError(data, textStatus, jqXHR) {
    console.log("Volunteer points failed: ", data, textStatus, jqXHR);
    alert("There was an issue submitting this payment. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "addVolunteerPoints", member_id: member_id, points: points},
    success: addVolunteerPointsSuccess,
    error: addVolunteerPointsError,
    dataType: 'json'
  }); 
}

function waiverDialogSumbit(member_id) {
  var completed = $("#inputWaiverStatus").val();
  
  function updateWaiverSuccess(data, textStatus, jqXHR) {
    console.log("Waiver update successful: ", data, textStatus, jqXHR);
    if ( data.succeeded ) {
      $('#waiverModal').modal('hide');
      refreshMember(member_id);
    } else if ( data.reason ) {
      alert(data.reason);
      $('#waiverModal').modal('hide');
    } else {
      updateWaiverError(data, textStatus, jqXHR);
    }
  }
  
  function updateWaiverError(data, textStatus, jqXHR) {
    console.log("Waiver update failed: ", data, textStatus, jqXHR);
    alert("There was an issue updating this user's waiver status. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "updateWaiver", member_id: member_id, completed: completed, term: CURRENT_TERM},
    success: updateWaiverSuccess,
    error: updateWaiverError,
    dataType: 'json'
  });
}

function setupWaiverListModal() {
  
  function success(data, textStatus, jqXHR) {
    console.log("Present, waiver-less members retrieval successful: ", data, textStatus, jqXHR);
    if ( data.succeeded ) {
      var members = data.members;
      members.sort(function(a,b) { return a.last_name < b.last_name ? -1 : 1; });
      $("#waiverListModalTable tbody").empty();
      members.forEach(function (m) {
        var row = $("<tr>", {html: m.first_name + " " + m.last_name, style: "cursor: pointer"});
        row.click(function() {
          showMember(m.id);
          $('#waiverListModal').modal('hide');
        });
        $("#waiverListModalTable tbody").append(row);
      });
    } else if ( data.reason ) {
      alert(data.reason);
      $('#waiverListModal').modal('hide');
    } else {
      error(data, textStatus, jqXHR);
    }
  }
  
  function error(data, textStatus, jqXHR) {
    console.log("Present, waiver-less members retrieval failed: ", data, textStatus, jqXHR);
    alert("There was an issue retrieving present, waiver-less members. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "getPresentWaiverlessMembers"},
    success: success,
    error: error,
    dataType: 'json'
  }); 
}

function setupCompetitionTeamModal() {
  
  function success(data, textStatus, jqXHR) {
    console.log("Competition team members retrieval successful: ", data, textStatus, jqXHR);
    var members = data;
    members.sort(function(a,b) { return a.last_name < b.last_name ? -1 : 1; });
    $("#competitionTeamModalTable tbody").empty();
    members.forEach( function (m) {
      var row = $("<tr>");
      
      var nameCol = $("<td>", {html: m.last_name + ", " + m.first_name, style: "cursor: pointer"});
      nameCol.click(function() {
        showMember(m.id);
        $('#competitionTeamModal').modal('hide');
      });
      row.append(nameCol);
      
      var buttonCol = $("<td>");
      var button = $("<button class='btn btn-xs btn-primary'>Mark present</button>");
      buttonCol.append(button);
      button.click(function(e) {
        checkInMember(m.id, button);
        e.stopPropagation();
      });
      row.append(buttonCol);
      isCheckedInToday(m.id, true, function() {button.prop('disabled',true)}, null);
      
      row.append($("<td>", {html: formatAmount(m.balance)}));
      $("#competitionTeamModalTable tbody").append(row);
    });
  }
  
  function error(data, textStatus, jqXHR) {
    console.log("Present, waiver-less members retrieval failed: ", data, textStatus, jqXHR);
    alert("Competition team members retrieval failed. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "getCompetitionTeamList"},
    success: success,
    error: error,
    dataType: 'json'
  }); 
}

function setupTransactionsModal() {
  
  function success(data, textStatus, jqXHR) {
    console.log("Transactions retrieval successful: ", data, textStatus, jqXHR);
    $("#transactionsModalTable tbody").empty();
    data.transactions.forEach(function(t) {
      var row = $("<tr>");
      row.append($("<td>", {html: t.member_name}));
      row.append($("<td>", {html: parseInt(t.amount) > 0 ? "Credit" : "Debit"}));
      row.append($("<td>", {html: formatAmount(parseInt(t.amount))}));
      row.append($("<td>", {html: t.kind}));
      row.append($("<td>", {html: t.method}));
      row.append($("<td>", {html: t.date_time}));
      $("#transactionsModalTable tbody").append(row);
    });
    if ( data.transactions.length == 0 ) {
      $("#transactionsModalTable tbody").append("<tr><td colspan='5'>No debits or credits</td></tr>");
    }
    $("#transactionCSVFormObjectsField").val(JSON.stringify(data.transactions));
    $("#transactionCSVFormKeysField").val(JSON.stringify(['member_name', 'member_id', 'amount', 'kind', 'method', 'date_time']));
  }
  
  function error(data, textStatus, jqXHR) {
    console.log("Transactions retrieval failed: ", data, textStatus, jqXHR);
    alert("Transactions retrieval failed. Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "getTransactions", methods: ['Cash','Check']},
    success: success,
    error: error,
    dataType: 'json'
  });
}

function claimReward(reward) {
  if ( reward.claimed == "0" ) {
    function claimRewardSuccess(data, textStatus, jqXHR) {
      console.log("Reward claim successful: ", data, textStatus, jqXHR);
      refreshMember(reward.member_id);
    }
  
    function claimRewardError(data, textStatus, jqXHR) {
      console.log("Reward claim failed: ", data, textStatus, jqXHR);
      alert("There was an issue claiming this reward. Please try again.");
    }
  
    authAjax({
      type: "POST",
      url: apiURL,
      data: {type: "claimReward", reward: reward},
      success: claimRewardSuccess,
      error: claimRewardError,
      dataType: 'json'
    });
  }
}

function showMember(id, untrack) { // untrack optional, default: false
  var memberData = getMember(id);
  if ( typeof memberData === 'undefined' ) {
    alert("Failed to find member with id " + id);
    return;
  }
  console.log("SHOW MEMBER: ", memberData);
  
  var member = memberData.member;
  var memberships = memberData.memberships;
  var waiverStatus = memberData.waiverStatus;
  var feeStatus = memberData.feeStatus;
  var debitCredits = memberData.debitCredits;
  var checkIns = memberData.checkIns;
  var references = memberData.references;
  var rewards = memberData.rewards;
  
  // Clear member info tables
  $("#memberInfoTable tbody").empty();
  $("#memberCreditDebitTable tbody").empty();
  $("#memberHistoryTable tbody").empty();
  $("#referredTable tbody").empty();
  $("#memberRewardsTable tbody").empty();
  
  // Clear button event listeners
  $("#memberInfoCheckinButton").off();
  $("#memberInfoEditButton").off();
  $("#memberInfoPayButton").off();
  $("#memberInfoMembershipButton").off();
  
  $("#memberContainer h2 .firstName").html(member.first_name);
  $("#memberContainer h2 .lastName").html(member.last_name);
  
  // fill info table
  var infoRow = $("<tr>");
  infoRow.append($("<td>", {html: member.first_name}));
  infoRow.append($("<td>", {html: member.last_name}));
  infoRow.append($("<td>", {html: member.nick_name}));
  infoRow.append($("<td>", {html: member.email}));
  var currentMembership = getElementOfTerm(memberships,CURRENT_TERM);
  infoRow.append($("<td>", {html: currentMembership ? currentMembership.kind : 'None'}));
  var currentFeeStatus = getElementOfTerm(feeStatus,CURRENT_TERM);
  infoRow.append($("<td>", {html: currentFeeStatus ? currentFeeStatus.kind : 'None'}));
  var currentWaiverStatus = getElementOfTerm(waiverStatus,CURRENT_TERM);
  infoRow.append($("<td>", {html: currentWaiverStatus && currentWaiverStatus.completed != 0 ? 'Yes' : 'No'}));
  var currentOutstandingMembershipDues = calculateMembershipDuesBalance(debitCredits);
  infoRow.append($("<td>", {html: formatAmount(currentOutstandingMembershipDues)}));
  $("#memberInfoTable tbody").append(infoRow);
  
  // fill credits and debits table
  debitCredits.forEach(function(t) {
    var row = $("<tr>");
    row.append($("<td>", {html: parseInt(t.amount) > 0 ? "Credit" : "Debit"}));
    row.append($("<td>", {html: formatAmount(parseInt(t.amount))}));
    row.append($("<td>", {html: t.kind}));
    row.append($("<td>", {html: t.method}));
    row.append($("<td>", {html: t.date_time}));
    $("#memberCreditDebitTable tbody").append(row);
  });
  if ( debitCredits.length == 0 ) {
    $("#memberCreditDebitTable tbody").append("<tr><td colspan='5'>No debits or credits</td></tr>");
  }
  
  // fill check in history table
  checkIns.forEach(function(c) {
    var row = $("<tr>");
    row.append($("<td>", {html: c.date_time}));
    $("#memberHistoryTable tbody").append(row);
  });
  if ( checkIns.length == 0 ) {
    $("#memberHistoryTable tbody").append("<tr><td colspan='1'>No checkins</td></tr>");
  }
  
  // fill in references table
  if ( member.referred_by ) {
    $("#referredBySpan").html(member.referred_by_name);
    $("#referredBySpan").click(function() {
      showMember(member.referred_by);
    });
    $("#referredByP").show();
  } else {
    $("#referredByP").hide();
  }
  references.forEach(function(r) {
    var row = $("<tr><td>" + r.referred_name + "</td></tr>");
    row.click(function() {
      showMember(r.referred_id);
    });
    $("#referredTable tbody").append(row);
  });
  if ( references.length == 0 ) {
    $("#referredTable tbody").append("<tr><td colspan='1'>No referrals by this member</td></tr>");
  }
  
  // fill in rewards table
  rewards.forEach(function(r) {
    var row = $("<tr>");
    row.append($("<td>", {html: r.kind}));
    row.append($("<td>", {html: r.term}));
    row.append($("<td>", {html: r.issue_date_time}));
    var claimedTd = $("<td>");
    if ( r.claimed == "1" ) {
      claimedTd.html(r.claim_date_time);
    } else {
      var claimButton = $("<button class='btn btn-xs btn-success'>Claim</button>");
      claimButton.click(function () {
        var confirmationMessage = "Rewards should only be marked claimed after the reward has been given in full (e.g. if the reward is free shoes, make the reward as claimed after giving the member the shoes). Are you sure you want to continue?";
        if ( confirm(confirmationMessage) ) {
          claimReward(r);
        }
      });
      claimedTd.append(claimButton);
    }
    row.append(claimedTd);
    $("#memberRewardsTable tbody").append(row);
  });
  if ( rewards.length == 0 ) {
    $("#memberRewardsTable tbody").append("<tr><td colspan='1'>No rewards</td></tr>");
  }
  
  // setup checkin button
  var checkInButton = $("#memberInfoCheckinButton");
  checkInButton.click(function() {checkInMember(member.id, checkInButton)});
  // disable button if already checked in
  isCheckedInToday(member.id, true, function() {checkInButton.prop('disabled',true)}, function() {checkInButton.prop('disabled',false)});
  
  // setup membership modal
  if ( currentFeeStatus ) {
    $("#inputFeeStatus").val(currentFeeStatus.kind);
  } else {
    $("#inputFeeStatus").children()[0].selected = true;
  }
  if ( currentMembership ) {
    $("#inputMembership").val(currentMembership.kind);
  } else {
    $("#inputMembership").children()[0].selected = true;
  }
  $("#updateMembershipButton").off();
  $("#updateMembershipButton").click(function() { updateMembershipAndFeeStatus(member.id) });
  
  // setup waiver modal
  if ( $.cookie("auth_role") == "SafetyAndFacilities" || $.cookie("auth_role") == "Admin" ) {
    $("#inputWaiverStatus").val( currentWaiverStatus ? currentWaiverStatus.completed : 0 );
    $("#waiverModalCurrentTerm").html(CURRENT_TERM);
    $("#updateWaiverButton").off();
    $("#updateWaiverButton").click(function() { waiverDialogSumbit(member.id) });
    $("#memberInfoWaiverButton").show();
  } else {
    $("#memberInfoWaiverButton").hide();
  }
  
  // setup edit modal
  $("#inputEditFirstName").val(member.first_name);
  $("#inputEditLastName").val(member.last_name);
  $("#inputEditNickname").val(member.nick_name);
  $("#inputEditEmail").val(member.email);
  $("#editMemberButton").off();
  $("#editMemberButton").click(function() { updateMember(member.id) });
  
  // setup pay modal
  $("#payModalCurrentOutstanding").html(formatAmount(currentOutstandingMembershipDues));
  $("#inputCreditAmount").val("");
  $("#payButton").off();
  $("#payButton").click(function() { payDialogSubmit(member.id) });
  
  // setup volunteer points modal
  if ( ($.cookie("auth_role") == "Fundraising" || $.cookie("auth_role") == "Admin") && currentMembership && currentMembership.kind == 'Competition' ) {
    $("#volunteerPointsModalCurrentOutstanding").html(formatAmount(currentOutstandingMembershipDues));
    $("#inputPointsAmount").val("");
    $("#volunteerPointsButton").off();
    $("#volunteerPointsButton").click(function() { volunteerPointsDialogSubmit(member.id) });
    $("#memberInfoVolunteerPointsButton").show();
  } else {
    $("#memberInfoVolunteerPointsButton").hide();
  }
  
  hidePrimaryContainers();
  $("#memberContainer").show();
  
  // prompt payment if member needs to pay dues (except for Competition which can be on a plan)
  if ( currentOutstandingMembershipDues < 0 && currentMembership && currentMembership.kind != 'Competition' ) {
    $("#payModal").modal('show');
  }
  
  if ( isUndefined(untrack) || untrack == false ) {
    history.pushState({page: "member", id: id}, member.first_name + " " + member.last_name, "?member_id="+id);
  }
}

function refreshMember(id) {
  showMember(id, true);
}

function getMember(id) {
  var responseData = undefined;
  
  function getMemberSuccess(data, textStatus, jqXHR) {
    console.log("Member retrieval successful: ", data, textStatus, jqXHR);
    responseData = data;
  }

  function getMemberError(data, textStatus, jqXHR) {
    console.log("Member retrieval failed: ", data, textStatus, jqXHR);
    alert("There was an issue retrieving member with id " + id + ". Please try again.");
  }

  authAjax({
    async: false,
    type: "POST",
    url: apiURL,
    data: {type: "getMemberInfo", id: id},
    success: getMemberSuccess,
    error: getMemberError,
    dataType: 'json'
  });
  
  return responseData;
}

function isCheckedInToday(id, async, yesAction, noAction) {
  var response = true;
  
  function isCheckedInSuccess(data, textStatus, jqXHR) {
    console.log("Member 'is checked in?' successful: ", data, textStatus, jqXHR);
    response = data.checkedIn;
    if ( response ) {
      if ( yesAction != null ) {
        yesAction();
      }
    } else {
      if ( noAction != null ) {
        noAction();
      }
    }
  }
  function isCheckedInError(data, textStatus, jqXHR) {
    console.log("Member 'is checked in?' failed: ", data, textStatus, jqXHR);
  }
  
  authAjax({
    async: async,
    type: "POST",
    url: apiURL,
    data: {type: "checkedIn?", id: id},
    success: isCheckedInSuccess,
    error: isCheckedInError,
    dataType: 'json'
  });
  
  if ( !async ) {
    return response;
  }
}

function showCheckinErrorModal(id, reason, button) {
  $("#checkInErrorReason").html(reason);
  $("#overrideButton").off();
  $("#overrideButton").click(function() {
    var confirmation = confirm("Are you sure you want to checkin this member outside his/her membership?");
    if ( confirmation ) {
      checkInMember(id, null, true);  
      $("#checkinErrorModal").modal('hide');
      if ( button ) {
        button.prop('disabled', true);
      }
    }
  });
  
  $("#checkinErrorModal").modal('show');
}

function checkInMember(id, button, override) { // override is optional
  if ( isUndefined(override) ) {
    override = false;
  }
  
  function checkInMemberSuccess(data, textStatus, jqXHR) {
    console.log("Member checkin successful: ", data, textStatus, jqXHR);
    if ( data.permitted ) {
      if ( data.wasAlreadyCheckedIn ) {
        alert("Member was already checked in today.");
      }
      if ( button != null ) {
        button.prop('disabled', true);
      }
    } else {
      showCheckinErrorModal(id, data.permission_reason, button);
    }
  }
  
  function checkInMemberError(data, textStatus, jqXHR) {
    console.log("Member checkin failed: ", data, textStatus, jqXHR);
    alert("There was an issue checking in member with id " + id + ". Please try again.");
  }
  
  authAjax({
    type: "POST",
    url: apiURL,
    data: {type: "checkInMember", id: id, override: override},
    success: checkInMemberSuccess,
    error: checkInMemberError,
    dataType: 'json'
  });
}

function showMemberList(members) {
  // First clear member table
  var tableBody = $("#memberListTable tbody");
  tableBody.empty();
  
  if ( members.length == 0 ) {
    tableBody.append("<tr><td colspan='4'>No members matched the search term</td></tr>");
  }
  
  function addRow(member) {
    var row = $("<tr>");
    
    var buttonCol = $("<td>");
    var button = $("<button class='btn btn-xs btn-primary'>Check in</button>");
    buttonCol.append(button);
    row.append(buttonCol);

    row.append($("<td>", {html: member.first_name}));
    row.append($("<td>", {html: member.last_name}));
    row.append($("<td>", {html: member.email}));
    
    button.click(function(e) {
      checkInMember(member.id, button);
      e.stopPropagation();
    });
    row.click(function() {
      showMember(member.id);
    });
    // disable button if already checked in
    isCheckedInToday(member.id, true, function() {button.prop('disabled',true)}, null);
    
    tableBody.append(row);
  }
  members.forEach(addRow);
  
  hidePrimaryContainers();
  $("#memberListContainer").show();
}

function getMembers(query) {
  var responseData = undefined;
  
  function getMembersSuccess(data, textStatus, jqXHR) {
    console.log("Members retrieval successful: ", data, textStatus, jqXHR);
    responseData = data;
  }

  function getMembersError(data, textStatus, jqXHR) {
    console.log("Members retrieval failed: ", data, textStatus, jqXHR);
    alert("There was an issue retrieving members. Please try again.");
  }
  
  authAjax({
    async: false,
    type: "POST",
    url: apiURL,
    data: {type: "getMembers", query: query},
    success: getMembersSuccess,
    error: getMembersError,
    dataType: 'json'
  });
  
  return responseData ? responseData : [];
}