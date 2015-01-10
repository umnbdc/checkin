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
    alert("There was an issued adding this member. Please try again.");
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
  console.log("runSearch: ", getMembers(query));
}

function getMembers(query) {
  var members = [];
  var toReturn = {};
  
  function getMembersSuccess(data, textStatus, jqXHR) {
    console.log(data, textStatus, jqXHR);
    toReturn = data;
  }

  function getMembersError(data, textStatus, jqXHR) {
    console.log(data, textStatus, jqXHR);
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
  
  return toReturn;
}