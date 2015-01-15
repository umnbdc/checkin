function logout() {
  
  function logoutSuccess(data, textStatus, jqXHR) {
    console.log("Logout successful: ", data, textStatus, jqXHR);
    $.removeCookie("auth_token");
    $.removeCookie("auth_username");
    $.removeCookie("auth_role");
    location.reload();
  }
  
  function logoutError(data, textStatus, jqXHR) {
    console.log("Logout failed: ", data, textStatus, jqXHR);
    alert("There was an issue logging out. Please try again.");
  }
  
  $.ajax({
    type: "POST",
    url: apiURL,
    data: {type: "logout", auth_token: $.cookie('auth_token')},
    success: logoutSuccess,
    error: logoutError,
    dataType: 'json'
  });
}

function authAjax(ajaxArgObj) {
  var originalSuccessFn = ajaxArgObj.success;
  var originalErrorFn = ajaxArgObj.error;
  
  function error(data, textStatus, jqXHR) {
    if ( data.unauthorized ) {
      console.log("Unauthorized api access. Deleting auth cookies");
      logout();
    } else if ( originalErrorFn ) {
      originalErrorFn(data, textStatus, jqXHR);
    }
  }
  
  function success(data, textStatus, jqXHR) {
    if ( data.unauthorized ) {
      error(data, textStatus, jqXHR);
      return;
    } else if ( originalSuccessFn ) {
      originalSuccessFn(data, textStatus, jqXHR);
    }
  }
  
  ajaxArgObj.data.auth_token = $.cookie("auth_token");
  ajaxArgObj.data.auth_username = $.cookie("auth_username");
  ajaxArgObj.data.auth_role = $.cookie("auth_role");
  ajaxArgObj.success = success;
  ajaxArgObj.error = error;
  
  $.ajax(ajaxArgObj);
}

function login() {
  var username = $("#inputLoginUsername").val();
  var password = $("#inputLoginPassword").val();

  function loginSuccess(data, textStatus, jqXHR) {
    console.log("Login successful: ", data, textStatus, jqXHR);
    if ( data.succeeded ) {
      var expiryDate = new Date();
      var seconds = data.seconds_to_expiry ? data.seconds_to_expiry : 60*60*4;
      expiryDate.setTime(expiryDate.getTime() + (seconds * 1000));
      $.cookie("auth_token", data.auth_token, { expires: expiryDate });
      $.cookie("auth_role", data.auth_role, { expires: expiryDate });
      $.cookie("auth_username", username, { expires: expiryDate });
      location.reload();
    } else {
      alert("Username and password do not match");
    }
  }
  
  function loginError(data, textStatus, jqXHR) {
    console.log("Login failed: ", data, textStatus, jqXHR);
    alert("There was an issue logging in. Please try again.");
  }
  
  $.ajax({
    type: "POST",
    url: apiURL,
    data: {type: "login", username: username, passwordHash: md5(password)},
    success: loginSuccess,
    error: loginError,
    dataType: 'json'
  });
}

function createUser() {
  var username = $("#inputCreateUserUsername").val();
  var role = $("#inputCreateUserRole").val();
  var password = $("#inputCreateUserPassword").val();
  var password_confirm = $("#inputCreateUserPasswordConfirm").val();
  
  if ( !username || !role || !password ) {
    alert("Missing information. Username, role, and password are required.");
    return;
  }
  if ( password != password_confirm ) {
    alert("Passwords do not match.");
    return;
  }
  
  function createUserSuccess(data, textStatus, jqXHR) {
    console.log("User creation successful: ", data, textStatus, jqXHR);
    if ( data.succeeded ) {
      location.reload();
    } else {
      alert(data.reason);
    }
  }
  
  function createUserError(data, textStatus, jqXHR) {
    console.log("User creation failed: ", data, textStatus, jqXHR);
    alert("There was an issue creating a new user. Please try again.");
  }
  
  $.ajax({
    type: "POST",
    url: apiURL,
    data: {type: "createUser", username: username, role: role, passwordHash: md5(password)},
    success: createUserSuccess,
    error: createUserError,
    dataType: 'json'
  });
}
