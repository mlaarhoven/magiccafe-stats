   function add_application ( application_index_new )
                  {
                  var application_index_current =  $("#application_index_current").val(); 
                  console.log ( "application_index_current=" + application_index_current );
                  var application_index_new = Number(application_index_current) + 1;
                  $("#application_index_current").val( application_index_new );

                  var html_new_application ='<table  class="table table-responsive-sm table-bordered table-striped table-sm">';
                  html_new_application = html_new_application + '<tr><td>Index</td><td>'+ application_index_new +'</td></tr>';
                  html_new_application = html_new_application + '<tr><td>packageName</td><td><input type="text" name="applications['+ application_index_new +'][packageName]" value="" size="40"></td></tr>';
                  html_new_application = html_new_application + '<tr><td>defaultPermissionPolicy</td><td>';
                  html_new_application = html_new_application + '<select name="applications['+ application_index_new +'][defaultPermissionPolicy]">';
                  html_new_application = html_new_application + '<option name="PERMISSION_POLICY_UNSPECIFIED" value="PERMISSION_POLICY_UNSPECIFIED">PERMISSION_POLICY_UNSPECIFIED</option>';
                  html_new_application = html_new_application + '<option name="PROMPT" value="PROMPT">PROMPT</option>';
                  html_new_application = html_new_application + '<option name="GRANT" value="GRANT" selected="selected">GRANT</option>';
                  html_new_application = html_new_application + '<option name="DENY" value="DENY">DENY</option>';
                  html_new_application = html_new_application + '</select> ';
                  html_new_application = html_new_application + '</td></tr>';
                  html_new_application = html_new_application + '<tr><td>installType</td>';
                  html_new_application = html_new_application + '<td><select name="applications['+ application_index_new +'][installType]">';
                  html_new_application = html_new_application + '<option name="KIOSK" value="KIOSK">KIOSK</option>';
                  html_new_application = html_new_application + '<option name="FORCE_INSTALLED" value="FORCE_INSTALLED" selected="selected">FORCE_INSTALLED</option>';
                  html_new_application = html_new_application + '<option name="AVAILABLE" value="AVAILABLE">AVAILABLE</option>';
                  html_new_application = html_new_application + '</select>'; 
                  html_new_application = html_new_application + '</td></tr>';
                  html_new_application = html_new_application + '</table>';

                  $( "#applications" ).append( html_new_application );   
                  }