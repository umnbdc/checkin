<div class="modal fade" id="createUserModal" role="dialog" aria-labelledby="createUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="createUserModalLabel">Create User</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="inputCreateUserUsername">Username</label>
          <input type="text" class="form-control" id="inputCreateUserUsername" placeholder="Username">
        </div>
        <div class="form-group">
          <label for="inputCreateUserRole">Role</label>
          <select class="form-control" id="inputCreateUserRole">
            <option value=""></option>
            <option value="President">President</option>
            <option value="VicePresident">Vice President</option>
            <option value="Treasurer">Treasurer</option>
            <option value="Secretary">Secretary</option>
            <option value="Travel">Travel Coordinator</option>
            <option value="SafetyAndFacilities">Safety and Facilities Coordinator</option>
            <option value="Fundraising">Fundraising Coordinator</option>
            <option value="Dance">Dance Coordinator</option>
            <option value="Music">Music Coordinator</option>
            <option value="Publicity">Publicity Coordinator</option>
            <option value="Web">Web Coordinator</option>
            <option value="Volunteer">Volunteer</option>
          </select>
        </div>
        <div class="form-group">
          <label for="inputCreateUserPassword">Password</label>
          <input type="password" class="form-control" id="inputCreateUserPassword" placeholder="Password">
        </div>
        <div class="form-group">
          <label for="inputCreateUserPasswordConfirm">Confirm Password</label>
          <input type="password" class="form-control" id="inputCreateUserPasswordConfirm" placeholder="Confirm Password">
        </div>
      </div>
      <div class="modal-footer">
        <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button> -->
        <button id="createUserButton" onclick="createUser()" type="button" class="btn btn-primary">Create</button>
      </div>
    </div>
  </div>
</div>
