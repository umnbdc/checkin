
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
    <!-- Bootstrap sticky footer -->
    <link href="bootstrap/css/sticky-footer-navbar.css" rel="stylesheet">
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="js/jquery.cookie.js"></script>
    <script src="js/md5.js"></script>
    <script src="js/jquery.canvasjs.min.js"></script>
    
    <!-- Bootstrap Datepicker -->
    <link href="bootstrap/datepicker/css/datepicker.css" rel="stylesheet">
    <script src="bootstrap/datepicker/js/bootstrap-datepicker.js"></script>
    
    <script src="js/auth.js"></script>
    <script src="js/member.js"></script>

    <!-- Custom styles for this template -->
    <link href="css/main.css" rel="stylesheet">
  </head>

  <body>

    <!-- NAVIGATION BAR -->
    <nav class="navbar navbar-inverse navbar-fixed-top" style="margin-bottom: 0px; position: static">
      <div class="container">
        <div class="navbar-header">
          <!-- When the space for the navigation bar is too small, show a button to collapse/uncollapse -->
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand">UMN BDC Check-in</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <!--
            <li class="active"><a href="">Home</a></li>
            <li><a href="">Reports</a></li>
            <li><a href="">Help</a></li>
            -->
            <li id="waiverListLink" style="display: none" data-toggle="modal" data-target="#waiverListModal"><a style="cursor: pointer">Waiver List</a></li>
            <li id="competitionTeamLink" data-toggle="modal" data-target="#competitionTeamModal"><a style="cursor: pointer">Competition Team</a></li>
            <li id="transactionsLink" data-toggle="modal" data-target="#transactionsModal"><a style="cursor: pointer">Transactions</a></li>
            <li id="summaryLink" onclick="showSummaryContainer()"><a style="cursor: pointer">Summary</a></li>
            <li id="widgetsLink" data-toggle="modal" data-target="#widgetsModal"><a style="cursor: pointer">Widgets</a></li>
            <li id="createUserLink" style="display: none" data-toggle="modal" data-target="#createUserModal"><a style="cursor: pointer">New User</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a id="loggedInAs"></a></li>
            <li><a id="logoutButton" onclick="logout()">Logout</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <div class="container">
      <!-- <h1>UMN BDC Check-in</h1> -->
      <p><br/></p>
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

    <!-- include Containers -->
    <?php include "resources/containers/memberContainer.php"; ?>
    <?php include "resources/containers/memberListContainer.php"; ?>
    <?php include "resources/containers/summaryContainer.php"; ?>

    <footer class="footer">
      <div class="container">
        <p class="text-muted">Developed for the University of Minnesota Ballroom Dance Club</p>
      </div>
    </footer>

    <!-- include Modals -->
    <?php include "resources/modals/newMemberModal.php"; ?>
    <?php include "resources/modals/editMemberModal.php"; ?>
    <?php include "resources/modals/payModal.php"; ?>
    <?php include "resources/modals/purchaseModal.php"; ?>
    <?php include "resources/modals/waiverModal.php"; ?>
    <?php include "resources/modals/waiverListModal.php"; ?>
    <?php include "resources/modals/membershipModal.php"; ?>
    <?php include "resources/modals/checkinErrorModal.php"; ?>
    <?php include "resources/modals/loginModal.php"; ?>
    <?php include "resources/modals/createUserModal.php"; ?>
    <?php include "resources/modals/competitionTeamModal.php"; ?>
    <?php include "resources/modals/transactionsModal.php"; ?>
    <?php include "resources/modals/widgetsModal.php"; ?>
    <?php include "resources/modals/newCompMemberDiscountModal.php"; ?>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="bootstrap/js/bootstrap.min.js"></script>

    <!-- Our main JS for interface setup -->
    <script type="text/javascript">
      function showHome() {
        hidePrimaryContainers();
        history.pushState({page: "home"},"Home","?");
      }
      
      if ( $.cookie('auth_token') && $.cookie('auth_username') && $.cookie('auth_role') ) {
        $("#loggedInAs").html($.cookie('auth_username'));
        setEnvironment(false);
      
        <?php if ( $_GET['member_id'] ) { ?>
          showMember(<?php echo $_GET['member_id']; ?>);
        <?php } else if ( $_GET['search'] ) { ?>        
          $("#memberSearch").val("<?php echo $_GET['search']; ?>");
          runSearch();
        <?php } else if ( $_GET['summary'] ) { ?>
          showSummaryContainer();
        <?php } else { ?>
          showHome();
          $("#memberSearch").focus();
        <?php } ?>
      } else {
        $("#loginModal").modal('show');
        $("#inputLoginUsername").focus()
      }

      // Handle the state (what page you're viewing)
      function onpopstate(e) {
        if ( e.state.page == "home" ) {
          showHome();
        } else if ( e.state.page == "member" ) {
          showMember(e.state.id, true); // untrack = true
        } else if ( e.state.page == "list" ) {
          $("#memberSearch").val(e.state.query);
          runSearch(true); // untrack = true
        } else if ( e.state.page == "summary" ) {
          showSummaryContainer(true);
        }
      }
      window.addEventListener("popstate", onpopstate);

      // The "brand" button should navigate to home
      $(".navbar-brand").click(function(e) {
        showHome();
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

      // Only show the "Waiver List" option to SafetyAndFacilities and Admin roles
      if ( $.cookie('auth_role') == 'SafetyAndFacilities' || $.cookie('auth_role') == 'Admin' ) {
        $("#waiverListLink").show();
      } else {
        $("#waiverListLink").hide();
      }
      $('#waiverListModal').on('shown.bs.modal', setupWaiverListModal);
      
      $('#competitionTeamModal').on('shown.bs.modal', setupCompetitionTeamModal);
      $('#transactionsModal').on('shown.bs.modal', setupTransactionsModal);

      // Only show the "New User" option to admins
      if ( $.cookie('auth_role') == 'Admin' ) {
        $("#createUserLink").show();
      } else {
        $("#createUserLink").hide();
      }
    </script>
  </body>
</html>
