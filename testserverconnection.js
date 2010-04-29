
function adobetestConnection(obj) {
// This function will open a popup window to test the server parameters for a successful connection.

    if ((obj.id_s__adobeconnect_host.value.length == 0) || (obj.id_s__adobeconnect_host.value == '')) {
      return false;
    }
        
    var queryString = "";
    
    queryString += "serverURL=" + escape(obj.id_s__adobeconnect_host.value);
    queryString += "&port=" + obj.id_s__adobeconnect_port.value;
    queryString += "&authUsername=" + escape(obj.id_s__adobeconnect_admin_login.value);
    queryString += "&authPassword=" + escape(obj.id_s__adobeconnect_admin_password.value);
    queryString += "&authHTTPheader=" + escape(obj.id_s__adobeconnect_admin_httpauth.value);
    
    if (obj.id_s__adobeconnect_email_login.checked) {
        queryString += "&authEmaillogin=1";
    } else {
        queryString += "&authEmaillogin=0";
    }

    var myobject =new testwindow('/mod/adobeconnect/conntest.php?' + queryString, 'connectiontest',
                                'Adobe Connect Pro test window',
                                'scrollbars=yes,resizable=no,width=640,height=300');
    
    /*return openpopup('onclick', '/mod/adobeconnect/conntest.php?' + queryString, 'connectiontest', 'scrollbars=yes,resizable=no,width=640,height=300');*/

    return openpopup('onclick', myobject);
}

function testwindow(url, name, options) {
    this.url = url;
    this.name = name;
    this.options = options;
}