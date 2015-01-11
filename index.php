
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>UMN BDC Check-in</title>

    <!-- Bootstrap core CSS -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap theme -->
    <link href="bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    
    <script src="js/member.js"></script>

    <!-- Custom styles for this template -->
    <link href="starter-template.css" rel="stylesheet">
  </head>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">UMN BDC Check-in</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Home</a></li>
            <li><a href="#about">Reports</a></li>
            <li><a href="#contact">Help</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <div class="container">
      <h1>UMN BDC Check-in</h1>
      <p>
        <div class="input-group">
          <input type="text" class="form-control" id="memberSearch" placeholder="Search for member...">
          <span class="input-group-btn">
            <button class="btn btn-default" id="memberSearchGoButton" type="button" onclick="runSearch()">Go!</button>
          </span>
        </div>
      </p>
      <p>
        <div class="btn-group">
          <button type="button" class="btn btn-default" data-toggle="modal" data-target="#newMemberModal">Add new member</button>
        </div>
      </p>
    </div><!-- /.container -->
    
    <div class="container" id="memberContainer" style="display: none">
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
          <button type="button" id="memberInfoPayButton" class="btn btn-default">Pay and Purchase</button>
          <button type="button" id="memberInfoMembershipButton" class="btn btn-default" data-toggle="modal" data-target="#membershipModal">Membership and Fee Status</button>
        </div>
      </p>
      <h3>Credits and debits</h3>
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
      <h3>Check In History</h3>
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
    
    <div class="container" id="memberListContainer" style="display: none">
      <h2>Member Search Results</h2>
      <table id="memberListTable" class="table table-condensed table-hover">
        <thead>
          <tr>
            <th></th>
            <th>First</th>
            <th>Last</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>

    <!-- New member modal -->
    <?php include "modals/newMemberModal.php"; ?>
    
    <!-- Edit member modal -->
    <?php include "modals/editMemberModal.php"; ?>
    
    <!-- Membership modal -->
    <?php include "modals/membershipModal.php"; ?>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="bootstrap/js/bootstrap.min.js"></script>
    
    <script type="text/javascript">
      $("#memberSearch").focus();
      
      $(".navbar-brand").click(function(e) {
        $("#memberContainer").hide();
        $("#memberListContainer").hide();
      });
      
      // submits member search on enter
      $("#memberSearch").keypress(function(e) {
        if(e.which == 13) {
          this.blur();
          $("#memberSearchGoButton").click();
        }
      });

      // clear the new member form every time it's shown
      $('#newMemberModal').on('shown.bs.modal', function() {
          $('#inputFirstName').val("");
          $('#inputLastName').val("");
          $('#inputNickname').val("");
          $('#inputEmail').val("");
          $('#referSearch').val("");
          $("#newMemberReferForm").empty();
      });
    </script>
  </body>
</html>
