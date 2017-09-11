<div class="modal fade" id="newMemberModal" role="dialog" aria-labelledby="newMemberModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="newMemberModalLabel">Add a new member</h4>
      </div>
      <div class="modal-body">
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
	<div class="form-group">                                                                                                  <label for="inputPublicity">How did you hear about us?</label>                                                          <select class="form-control" id="inputPublicity">                                                                             <option value="" disabled selected>Please Choose...</option>                                                            <option value="Postcard">Postcard</option>                                                                              <option value="Flyer">Flyer</option>                                                                                    <option value="Chalk">Chalking</option>                                                                                 <option value="FB">Facebook</option>                                                                                    <option value="Insta">Instagram</option>                                                                                <option value="RecWell">RecWell Open House</option>                                                                     <option value="ExploreU">ExploreU</option>                                                                              <option value="Friend">Friend</option>                                                                                  <option value="Other">Other</option>                                                                              </select>                                                                                                             </div> 
        <div class="form-group">
          <label for="inputReferredBy">Referred by</label>
          <div class="input-group">
            <input type="text" class="form-control" id="referSearch" placeholder="Search for referrer...">
            <span class="input-group-btn">
              <button class="btn btn-default" id="memberReferGoButton" type="button" onclick="searchAndFillReferOptions()">Go!</button>
            </span>
          </div>
          <script type="text/javascript">
            // submits refer search on enter
            $("#referSearch").keypress(function(e) {
              if(e.which == 13) {
                this.blur();
                $("#memberReferGoButton").click();
              }
            });
            function searchAndFillReferOptions() {
              var query = $("#referSearch").val();
              var members = getMembers(query);
              if ( typeof members === 'undefined' ) {
                alert("Failed to search for referring members.");
              } else {
                $("#newMemberReferForm").empty();
                if ( members.length > 0 ) {
                  members.forEach(function (member) {
                    var radioDiv = $("<div>", {class: "radio"});
                    var label = $("<label>", {html: member.first_name + " " + member.last_name + " (" + member.email + ")"});
                    label.prepend($("<input>", {
                      type: "radio",
                      name: "inputReferredBy",
                      value: member.id
                    }));
                    radioDiv.append(label);
                    $("#newMemberReferForm").append(radioDiv);
                  });
                } else {
                  $("#newMemberReferForm").append($("<p>", {html: "No matches found"}));
                }
              }
            }
          </script>
          <form id="newMemberReferForm">
          </form>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button id="addMemberButton" onclick="addNewMember()" type="button" class="btn btn-primary">Add</button>
      </div>
    </div>
  </div>
</div>
