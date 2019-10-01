function printWindow()
{
   bV = parseInt(navigator.appVersion);
   if (bV >= 4) window.print();
}

function displayWindow(url, width, height)
{
        var Win = window.open(url,"Popup",'width=' + width + ',height=' + height + ',toolbar=no,resizable=yes,scrollbars=yes,menubar=no,status=no,location=no');
}

function refresh()
{
    window.location.reload( true );
}

browser_name=navigator.appName;
browser_version=parseFloat(navigator.appVersion);
if (browser_name == "Netscape" && browser_version < 5.0)
{
   browserOk='false';
   document.write('<LINK REL=StyleSheet HREF="styles/MIGstyle_netscape.css" TYPE="text/css">');
}
else
{
   browserOk='true';
   document.write('<LINK REL=StyleSheet HREF="styles/MIGstyle.css" TYPE="text/css">');
}
