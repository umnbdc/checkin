<div class="modal fade" id="loginModal" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="loginModalLabel">Login</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="inputLoginUsername">Username</label>
          <input type="text" class="form-control" id="inputLoginUsername" placeholder="First name">
        </div>
        <div class="form-group">
          <label for="inputLoginPassword">Password</label>
          <input type="password" class="form-control" id="inputLoginPassword" placeholder="Last name">
        </div>
      </div>
      <div class="modal-footer">
        <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button> -->
        <button id="loginButton" onclick="login()" type="button" class="btn btn-primary">Login</button>
      </div>
    </div>
  </div>
</div>
