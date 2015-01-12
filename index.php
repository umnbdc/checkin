
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
    <link href="css/main.css" rel="stylesheet">
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

    <!-- include Modals -->
    <?php include "containers/memberContainer.php"; ?>
    <?php include "containers/memberListContainer.php"; ?>

    <!-- include Modals -->
    <?php include "modals/newMemberModal.php"; ?>
    <?php include "modals/editMemberModal.php"; ?>
    <?php include "modals/payModal.php"; ?>
    <?php include "modals/volunteerPointsModal.php"; ?>
    <?php include "modals/waiverModal.php"; ?>
    <?php include "modals/membershipModal.php"; ?>
    <?php include "modals/checkinErrorModal.php"; ?>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="bootstrap/js/bootstrap.min.js"></script>
    
    <script type="text/javascript">
      setEnvironment();
      
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
