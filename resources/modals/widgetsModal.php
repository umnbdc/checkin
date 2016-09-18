<div class="modal fade" id="widgetsModal" role="dialog" aria-labelledby="widgetsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="widgetsModalLabel">Widgets</h4>
      </div>
      <div class="modal-body">
        <script type="text/javascript">
          function widget_nowaiver_hasmembership_alert() {
            var members = getComplexMembers().filter(_nowaiverfilter).filter(_hasmembership);
            members = members.map(function(m) { return m.first_name + " " + m.last_name + ": " + m.email });
            if ( members.length == 0 ) {
              alert("None");
            } else {
              alert(members.join("\n"));
            }
          }
          function widget_urcmembership_alert() {
            var members = getComplexMembers().filter(function(m) { return m.fee_status.length > 0 && m.fee_status[0].kind == "URCMembership" });
            members = members.map(function(m) { return m.first_name + " " + m.last_name + ": " + m.email });
            if ( members.length == 0 ) {
              alert("None");
            } else {
              alert(members.join("\n"));
            }
          }
        </script>
        <p><a style="cursor: pointer" onclick="widget_nowaiver_hasmembership_alert()">Members with memberships this term who have not filled a waiver</a></p>
        <p><a style="cursor: pointer" onclick="widget_urcmembership_alert()">Members with fee status: URC Membership</a></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
