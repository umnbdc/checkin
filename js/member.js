var apiURL = "data.php";

var CURRENT_TERM = "Spring2015";

function addNewMember() {
  var memberObject = {
    firstName: $('#inputFirstName').val(),
    lastName: $('#inputLastName').val(),
    nickname: $('#inputNickname').val(),
    email: $('#inputEmail').val(),
    referredBy: $('#inputReferredBy').val()
  }
  
  console.log(memberObject);
  
  function addNewMemberSuccess(data, textStatus, jqXHR) {
    console.log("New member submission successful: ", data, textStatus, jqXHR);
    $('#newMemberModal').modal('hide');
    // show member page
  }
  
  function addNewMemberError(data, textStatus, jqXHR) {
    console.log("New member submission failed: ", data, textStatus, jqXHR);
    alert("There was an issue adding this member. Please try again.");
  }
  
  var data = {type: "newMember", member: memberObject};
  
  $.ajax({
    async: false,
    type: "POST",
    url: apiURL,
    data: data,
    success: addNewMemberSuccess,
    error: addNewMemberError,
    dataType: 'json'
  }); 
}

function runSearch() {
  var query = $("#memberSearch").val();
  var members = getMembers(query);
  if ( typeof members === 'undefined' ) {
    alert("Failed to search for members.");
  } else {
    showMemberList(getMembers(query));
  }
}

function getElementOfTerm(elements, term) {
  var element = null;
  elements.forEach(function(e) {
    if ( e.term = term ) {
      element = e;
    }
  });
  return element;
}

function calculateBalance(transactions) {
  // we'll want to handle the kind matching later, but for now...
  var balance = 0;
  transactions.forEach(function(t) {
    balance += parseInt(t.amount);
  });
  return balance;
}

function formatAmount(amount) {
  var negString = ""
  if ( amount < 0 ) {
    negString = "-";
    amount = -1 * amount;
  }
  var cents = amount % 100;
  var dollars = amount / 100;
  var centString = cents == 0 ? "00" : cents;
  var dollarString = dollars == 0 ? "0" : dollars;
  
  return negString + "$" + dollarString + "." + centString;
}

function showMember(id) {
  var memberData = getMember(id);
  if ( typeof memberData === 'undefined' ) {
    alert("Failed to find member with id " + id);
    return;
  }
  console.log("SHOW MEMBER: ", memberData);
  
  var member = memberData.member;
  var memberships = memberData.memberships;
  var waiverStatus = memberData.waiverStatus;
  var debitCredits = memberData.debitCredits;
  var checkIns = memberData.checkIns;
  
  // Clear member info tables
  $("#memberInfoTable tbody").empty();
  $("#memberCreditDebitTable tbody").empty();
  $("#memberHistoryTable tbody").empty();
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
  var currentWaiverStatus = getElementOfTerm(waiverStatus,CURRENT_TERM);
  infoRow.append($("<td>", {html: currentWaiverStatus && currentWaiverStatus.completed != 0 ? 'Yes' : 'No'}));
  infoRow.append($("<td>", {html: formatAmount(calculateBalance(debitCredits))}));
  $("#memberInfoTable tbody").append(infoRow);
  
  // fill credits and debits table
  debitCredits.forEach(function(t) {
    var row = $("<tr>");
    row.append($("<td>", {html: parseInt(t.amount) > 0 ? "Credit" : "Debit"}));
    row.append($("<td>", {html: formatAmount(parseInt(t.amount))}));
    row.append($("<td>", {html: t.kind}));
    row.append($("<td>", {html: t.date_time}));
    $("#memberCreditDebitTable tbody").append(row);
  });
  
  // fill check in history table
  checkIns.forEach(function(c) {
    var row = $("<tr>");
    row.append($("<td>", {html: c.date_time}));
    $("#memberHistoryTable tbody").append(row);
  });
  
  // assign buttons functions
  var checkInButton = $("#memberInfoCheckinButton");
  checkInButton.click(function() {checkInMember(member.id, checkInButton)});
  $("#memberInfoEditButton").click(function() {});
  $("#memberInfoPayButton").off();
  $("#memberInfoMembershipButton").off();
  
  $("#memberListContainer").hide();
  $("#memberContainer").show();
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

  $.ajax({
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

function checkInMember(id, button) {
  function checkInMemberSuccess(data, textStatus, jqXHR) {
    console.log("Member checkin successful: ", data, textStatus, jqXHR);
    if ( data.wasAlreadyCheckedIn ) {
      alert("Member was already checked in today.");
    }
    button.prop('disabled', true);
  }
  
  function checkInMemberError(data, textStatus, jqXHR) {
    console.log("Member checkin failed: ", data, textStatus, jqXHR);
    alert("There was an issue checking in member with id " + id + ". Please try again.");
  }
  
  $.ajax({
    type: "POST",
    url: apiURL,
    data: {type: "checkInMember", id: id},
    success: checkInMemberSuccess,
    error: checkInMemberError,
    dataType: 'json'
  });
}

function showMemberList(members) {
  // TODO what if members == []
  
  
  // First clear member table
  var tableBody = $("#memberListTable tbody");
  tableBody.empty();
  
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
    
    tableBody.append(row);
  }
  members.forEach(addRow);
  
  $("#memberContainer").hide();
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
  
  $.ajax({
    async: false,
    type: "POST",
    url: apiURL,
    data: {type: "getMembers", query: query},
    success: getMembersSuccess,
    error: getMembersError,
    dataType: 'json'
  });
  
  return responseData;
}