var apiURL = "test.php";

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
  showMemberList(getMembers(query));
}

function showMember(id) {
  alert("Show member " + id);
}

function showMemberList(members) {
  $("#memberContainer").hide();
  
  // First clear member table
  var tableBody = $("#memberListTable tbody");
  tableBody.empty();
  
  function addRow(member) {
    var row = $("<tr>");
    row.append($("<td>", {html: member.first_name}));
    row.append($("<td>", {html: member.last_name}));
    row.append($("<td>", {html: member.email}));
    
    row.click(function() {
      showMember(member.id);
    });
    
    tableBody.append(row);
  }
  members.forEach(addRow);
  
  $("#memberListContainer").show();
}

function getMembers(query) {
  var responseData = {};
  
  function getMembersSuccess(data, textStatus, jqXHR) {
    console.log("Members retrieval successful: ", data, textStatus, jqXHR);
    responseData = data;
  }

  function getMembersError(data, textStatus, jqXHR) {
    console.log(data, textStatus, jqXHR);
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