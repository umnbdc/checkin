var apiURL = "data.php";

var CURRENT_TERM = "";

function isUndefined(e) {
  return typeof e === 'undefined';
}

function setEnvironment() {
  function environmentSuccess(data, textStatus, jqXHR) {
    console.log("Environment retrieval successful: ", data, textStatus, jqXHR);
    CURRENT_TERM = data.CURRENT_TERM;
  }
  
  function environmentError(data, textStatus, jqXHR) {
    console.log("Environment retrieval failed: ", data, textStatus, jqXHR);
    alert("There was an issue retrieving environment info. Please try again.");
  }
  
  $.ajax({
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
    $('#newMemberModal').modal('hide');
    showMember(data[0].id);
  }
  
  function addNewMemberError(data, textStatus, jqXHR) {
    console.log("New member submission failed: ", data, textStatus, jqXHR);
    alert("There was an issue adding this member. Please try again.");
  }
  
  var data = {type: "newMember", member: memberObject};
  
  $.ajax({
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
  var negString = ""
  if ( amount < 0 ) {
    negString = "-";
    amount = -1 * amount;
  }
  var cents = amount % 100;
  var dollars = Math.floor(amount / 100);
  var centString = cents == 0 ? "00" : cents;
  var dollarString = dollars == 0 ? "0" : dollars;
  
  return negString + "$" + dollarString + "." + centString;
}

function updateMember(id) {
  firstName = $("#inputEditFirstName").val();
  lastName = $("#inputEditLastName").val();
  nickName = $("#inputEditNickname").val();
  email = $("#inputEditEmail").val();
  
  function updateMemberSuccess(data, textStatus, jqXHR) {
    console.log("Update member info successful: ", data, textStatus, jqXHR);
    $("#editMemberModal").modal('hide');
    showMember(id);
  }
  
  function updateMemberError(data, textStatus, jqXHR) {
    console.log("Update member info failed: ", data, textStatus, jqXHR);
    alert("There was an issue updating this member's info. Please try again.");
  }
  
  $.ajax({
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
    showMember(id);
  }
  
  function updateMembershipError(data, textStatus, jqXHR) {
    console.log("Update membership failed: ", data, textStatus, jqXHR);
    alert("There was an issue updating this membership and fee status. Please try again.");
  }
  
  $.ajax({
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
    showMember(id);
  }
  
  function addPaymentError(data, textStatus, jqXHR) {
    console.log("Payment submission failed: ", data, textStatus, jqXHR);
    alert("There was an issue submitting this payment. Please try again.");
  }
  
  $.ajax({
    type: "POST",
    url: apiURL,
    data: {type: "payment", kind: kind, method: method, amount: amount, member_id: id},
    success: addPaymentSuccess,
    error: addPaymentError,
    dataType: 'json'
  }); 
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
  var feeStatus = memberData.feeStatus;
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
  
  // fill check in history table
  checkIns.forEach(function(c) {
    var row = $("<tr>");
    row.append($("<td>", {html: c.date_time}));
    $("#memberHistoryTable tbody").append(row);
  });
  
  // setup checkin button
  var checkInButton = $("#memberInfoCheckinButton");
  checkInButton.click(function() {checkInMember(member.id, checkInButton)});
  // disable button if already checked in
  isCheckedInToday(member.id, true, function() {checkInButton.prop('disabled',true)}, function() {checkInButton.prop('disabled',false)});
  
  // setup membership button
  // no code for button, done through modal data attributes
  // set modal selects
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
  // set update membership button
  $("#updateMembershipButton").off();
  $("#updateMembershipButton").click(function() { updateMembershipAndFeeStatus(member.id) });
  
  // setup edit button
  // no code for button, done through modal data attributes
  // set modal data
  $("#inputEditFirstName").val(member.first_name);
  $("#inputEditLastName").val(member.last_name);
  $("#inputEditNickname").val(member.nick_name);
  $("#inputEditEmail").val(member.email);
  // set edit member button
  $("#editMemberButton").off();
  $("#editMemberButton").click(function() { updateMember(member.id) });
  
  // setup pay button
  // no code for button, done through modal data attributes
  // set modal data
  // set edit member button
  $("#payModalCurrentOutstanding").html(formatAmount(currentOutstandingMembershipDues));
  $("#inputCreditAmount").val("");
  $("#payButton").off();
  $("#payButton").click(function() { payDialogSubmit(member.id) });
  
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
  
  $.ajax({
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
    // disable button if already checked in
    isCheckedInToday(member.id, true, function() {button.prop('disabled',true)}, null);
    
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