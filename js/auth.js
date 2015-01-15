function authAjax(ajaxArgObj) {
  var originalSuccessFn = ajaxArgObj.success;
  var originalErrorFn = ajaxArgObj.error;
  
  function error(data, textStatus, jqXHR) {
    if ( data.unauthorized ) {
      console.log("Unauthorized api access. Deleting cookie \"auth_token\"");
      $.removeCookie("auth_token");
      location.reload();
    } else if ( originalErrorFn ) {
      originalErrorFn(data, textStatus, jqXHR);
    }
  }
  
  function success(data, textStatus, jqXHR) {
    if ( data.unauthorized ) {
      error(data, textStatus, jqXHR);
      return;
    }
    if ( originalSuccessFn ) {
      originalSuccessFn(data, textStatus, jqXHR);
    }
  }
  
  ajaxArgObj.data.auth_token = $.cookie("auth_token");
  ajaxArgObj.success = success;
  ajaxArgObj.error = error;
  
  $.ajax(ajaxArgObj);
}

function login() {
  var date = new Date();
  var minutes = 30;
  date.setTime(date.getTime() + (minutes * 60 * 1000));
  $.cookie("auth_token", "1234", { expires: date });
  location.reload();
}