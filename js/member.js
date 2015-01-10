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

function getMembers(query) {
  var members = [];
  
  function getMembersSuccess(data, textStatus, jqXHR) {
    members = data.members;
  }

  function getMembersError(data, textStatus, jqXHR) {
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
}