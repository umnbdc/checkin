
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
        <span class="firstName">Kevin</span>
        <span class="lastName">Viratyosin</span>
      </h2>
      <table class="table table-condensed">
        <thead>
          <tr>
            <th>First</th>
            <th>Last</th>
            <th>Nickname</th>
            <th>Email</th>
            <th>Membership</th>
            <th>Waiver</th>
            <th>Outstanding</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Kevin</td>
            <td>Viratyosin</td>
            <td></td>
            <td>virat001@umn.edu</td>
            <td>Competition Team</td>
            <td>No</td>
            <td>$140.00</td>
          </tr>
        </tbody>
      </table>
      <p>
        <div class="btn-group">
          <button type="button" class="btn btn-primary">Check in</button>
          <button type="button" class="btn btn-default">Pay</button>
          <button type="button" class="btn btn-default">Membership</button>
        </div>
      </p>
      <h3>Outstanding credits and debits</h3>
      <table class="table table-condensed">
        <thead>
          <tr>
            <th>Credit/Debit</th>
            <th>Amount</th>
            <th>Kind</th>
            <th>Due/Paid</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Debit</td>
            <td>$50</td>
            <td>Membership</td>
            <td>2/4/2015</td>
            <td>Competition team 1</td>
          </tr>
          <tr>
            <td>Credit</td>
            <td>$10</td>
            <td>Membership</td>
            <td>1/31/2015</td>
            <td>Cash</td>
          </tr>
        </tbody>
      </table>
      <h3>History</h3>
      <table class="table table-condensed">
        <thead>
          <tr>
            <th>What</th>
            <th>When</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Check in</td>
            <td>5:45pm 1/12/2015</td>
          </tr>
          <tr>
            <td>Check in</td>
            <td>5:55pm 1/14/2015</td>
          </tr>
          <tr>
            <td>Check in</td>
            <td>5:47pm 1/15/2015</td>
          </tr>
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
    <div class="modal fade" id="newMemberModal" role="dialog" aria-labelledby="newMemberModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="newMemberModalLabel">Add a new member</h4>
          </div>
          <div class="modal-body">
            <form  >
              <div class="form-group">
                <label for="inputFirstName">First name</label>
                <input type="text" class="form-control" id="inputFirstName" placeholder="First name">
              </div>
              <div class="form-group">
                <label for="inputLastName">Last name</label>
                <input type="text" class="form-control" id="inputLastName" placeholder="Last name">
              </div>
              <div class="form-group">
                <label for="inputNickname">Nickname</label>
                <input type="text" class="form-control" id="inputNickname" placeholder="Nickname">
              </div>
              <div class="form-group">
                <label for="inputEmail">Email</label>
                <input type="email" class="form-control" id="inputEmail" placeholder="Email">
              </div>
              <div class="form-group">
                <label for="inputReferredBy">Referred by</label>
                <select class="form-control" id="inputReferredBy">
                  <option></option>
                  <option value="1">One</option>
                  <option value="2">Two</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button id="addMemberButton" onclick="addNewMember()" type="button" class="btn btn-primary">Add</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="bootstrap/js/bootstrap.min.js"></script>
    
    <script type="text/javascript">
      // submits member search on enter
      $("#memberSearch").keypress(function(e) {
        if(e.which == 13) {
          this.blur();
          $("#memberSearchGoButton").click();
        }
      });
    </script>
  </body>
</html>
