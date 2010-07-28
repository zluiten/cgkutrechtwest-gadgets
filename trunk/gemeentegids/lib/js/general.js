/* This file is included by Page.class.php in all pages. */
// Strings must be passed from PHP (Translation)

function createCookie(name,value,days) {
  if (days) {
    var date = new Date();
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = '; expires='+date.toGMTString();
  }
  else expires = '';
  document.cookie = name+'='+value+expires+'; path=/';
}

function readCookie(name) {
  var nameEQ = name + '=';
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

/* PageContact */

function reportIncorrectEntry(id,message) {
    if (confirm(message)) {
        window.location.href = '../contact/contact.php?id=' + id + '&mode=incorrect';
    }
}

/* PageContactEdit */

function deleteEntry(id) {
    if (confirm("Are you sure you want to delete this entry? This record will be removed from the database and this cannot be undone.")) {
        window.location.href = '../contact/contact.php?id=' + id + '&mode=delete';
    }
}

function deleteAddress(x) {
    document.getElementsByName('address[' + x + '][type]').item(0).value = '';
    document.getElementsByName('address[' + x + '][line1]').item(0).value = '';
    document.getElementsByName('address[' + x + '][line2]').item(0).value = '';
    document.getElementsByName('address[' + x + '][city]').item(0).value = '';
    document.getElementsByName('address[' + x + '][state]').item(0).value = '';
    document.getElementsByName('address[' + x + '][zip]').item(0).value = '';
    document.getElementsByName('address[' + x + '][phone1]').item(0).value = '';
    document.getElementsByName('address[' + x + '][phone2]').item(0).value = '';
    document.getElementsByName('address[' + x + '][country]').item(0).value = '';
}

function saveEntry() {
    createCookie('save','',-1);
    
    // for widgEditor
    if(typeof(document.editEntry.onsubmit) == "function")
        document.editEntry.onsubmit();
    
    document.editEntry.submit();
}

function adminsaveEntry() {
    createCookie('save','adminsave',1);
    
    // for widgEditor
    if(typeof(document.editEntry.onsubmit) == "function")
        document.editEntry.onsubmit();
    
    document.editEntry.submit();
}

/* AdminPanel */
function deleteUser(id) {
    if (confirm("Are you sure you want to delete this user? This user will be removed from the database and this cannot be undone.")) {
        window.location.href = '../admin/adminPanel.php?mode=delete&userid=' + id;
    }
}

function uninstallPlugin(name) {
    if (confirm("Are you sure you want to uninstall this plugin? This will ERASE ALL EXISTING data created by this plugin from the database and this cannot be undone.")) {
        window.location.href = '../admin/adminPanel.php?mode=uninstall&plugin=' + name;
    }
}

/* PageSearchList */
function generateMailto() {
    
    checkednames = '';
    i=0;
    while(e = document.getElementById('cx'+i))
    {
        if(e.checked == true)
             checkednames = checkednames + e.name +', ';
        
        i++;
    }
    document.getElementById("mailtoSelected").setAttribute("href","mailto:"+checkednames);
}

/* DHTML find element under mouse call: onclick="clickedElement(event);" */
function clickedElement(e)
{
    var target;
    
    if(!e) 
        var e = window.event;
    
    if(e.target)
        target = e.target;
    else if(e.srcElement)
        target = e.srcElement;
    
    if (target.nodeType == 3) // defeat Safari bug
       target = target.parentNode;
    
    return target;
}

/* DHTML/AJAX insert a row below a table cell and remove it again - broken for tables with TH
 * insertURL = URL to retrieve content from foo/goo.php?param=abc
 * Please set the unique id of the table using this method table.id -> example_17 for row 17
 * <img src="lib/icons/plus.png" onclick="TABR_tableRowExpander(event,\''.prepare($locations[$i]['tab1']).'\',6,\'lib/icons/\');">
 * prepare() = str_replace("'","\\'",str_replace("\r",'',str_replace("\n",'<br>',$html)));
 */
function TABR_ajaxTableRowExpander(e, insertURL, colspan, iconPath, oncomplt)
{
    var plusImage = clickedElement(e);
 
    if(plusImage.expanded == true)
        return TABR_tableRowShrinker(e,iconPath);
    
    plusImage.src= iconPath + 'minus.png';
    plusImage.expanded=true;
    
    var row = plusImage.parentNode.parentNode.parentNode;
    var idx = row.rowIndex;
    var table = row.parentNode;
    var newRow = table.insertRow(idx+1);
    var newCell = newRow.insertCell(0);
    newCell.colSpan = colspan;
    newCell.id = table.id+idx;
    
    if(insertURL=='')
        newCell.innerHTML = 'URL to load is empty!';
    else
    {
        newCell.innerHTML = 'loading ...';
        
        new Ajax.Updater(newCell.id, insertURL, { method: 'get' , evalScripts: true,
            onFailure: function(){ newCell.innerHTML="Failed to fetch content." },
            onComplete: function(){ eval(oncomplt); } }); // generally this will deliver the 404 page instead
    }
}

/* DHTML/AJAX private to: TABR_tableRowExpander */   
function TABR_tableRowShrinker(e,iconPath)
{
    var plusImage = clickedElement(e);
    
    plusImage.src=iconPath + 'plus.png';
    plusImage.expanded=false;
    
    var row = plusImage.parentNode.parentNode.parentNode;
    var idx = row.rowIndex;
    var table = row.parentNode;
    var newRow = table.deleteRow(idx+1);        
}

/* DHTML insert a row below a table cell and remove it again - broken for tables with TH
 * insertHTML = HTML content to show
 * Please set the unique id of the table using this method table.id -> example_17 for row 17
 */
function TABR_tableRowExpander(e, insertHTML, colspan, iconPath)
{
    var plusImage = clickedElement(e);
 
    if(plusImage.expanded == true)
        return TABR_tableRowShrinker(e,iconPath);
    
    plusImage.src = iconPath + 'minus.png';
    plusImage.expanded=true;
    
    var row = plusImage.parentNode.parentNode.parentNode;
    var idx = row.rowIndex;
    var table = row.parentNode;
    var newRow = table.insertRow(idx+1);
    var newCell = newRow.insertCell(0);
    newCell.colSpan = colspan;
    newCell.id = table.id+idx;
    newCell.innerHTML = insertHTML;
}

