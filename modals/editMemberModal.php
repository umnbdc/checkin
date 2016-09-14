<div class="modal fade" id="editMemberModal" role="dialog" aria-labelledby="editMemberModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="editMemberModalLabel">Edit member information</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="inputFirstName">First name</label>
          <input type="text" class="form-control" id="inputEditFirstName" placeholder="First name">
        </div>
        <div class="form-group">
          <label for="inputLastName">Last name</label>
          <input type="text" class="form-control" id="inputEditLastName" placeholder="Last name">
        </div>
        <div class="form-group">
          <label for="inputNickname">Nickname</label>
          <input type="text" class="form-control" id="inputEditNickname" placeholder="Nickname">
        </div>
        <div class="form-group">
          <label for="inputEmail">Email</label>
          <input type="email" class="form-control" id="inputEditEmail" placeholder="Email">
        </div>
        <div class="form-group">
          <label for="inputProficiency">Proficiency</label>
          <select class="form-control" id="inputEditProficiency">
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="editMemberButton" type="button" class="btn btn-primary">Update</button>
      </div>
    </div>
  </div>
</div>
