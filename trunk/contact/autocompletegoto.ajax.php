<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  THE ADDRESS BOOK
 *  
 *
 *************************************************************
 *  query.php
 *  Connects to db to autocomplete relationship field 
 *
 *************************************************************/

chdir("..");

require_once('lib/init.php');

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
    exit();
    
require_once('DB.class.php');
require_once('StringHelper.class.php');

    if (isset($_COOKIE["searchtype"]))
        $type = StringHelper::cleanGPC($_COOKIE["searchtype"]);
    else
        $type = "name";
        
    $admin = intval($_SESSION['user']->isAtLeast('admin'));
        
    $p = $db->escape(StringHelper::cleanGPC($_POST['goTo']));
    if ($p[0] == "'")
        $p = mb_substr($p,1,-1);
    
    $limit = $options->getOption('autocompleteLimit');
        
    switch ($type)
    {
        case 'name':
            $sel_lname = "SELECT CONCAT(lastname,', ',firstname) AS fullname, '' AS value FROM " . TABLE_CONTACT . " AS contact WHERE ";
            $sel_fname = "SELECT CONCAT(firstname,' ',lastname) AS fullname, '' AS value FROM " . TABLE_CONTACT . " AS contact WHERE ";
            $sel_nname = "SELECT CONCAT(lastname,', ',firstname) AS fullname, nickname AS value FROM " . TABLE_CONTACT . " AS contact WHERE ";
            $where_lname = "(lastname LIKE '$p%') AND (hidden = 0 OR $admin)";
            $where_fname = "(firstname LIKE '$p%') AND (hidden = 0 OR $admin)";
            $where_nname = "(nickname LIKE '$p%') AND (hidden = 0 OR $admin)";
            $sql = "($sel_lname $where_lname) UNION ($sel_fname $where_fname) UNION ($sel_nname $where_nname) ORDER BY fullname ASC LIMIT $limit";
            break;
        case 'email':
        case 'www':
        case 'chat':
            $sel = "SELECT CONCAT(lastname,', ',firstname) AS fullname, properties.value AS value, properties.label AS label
            FROM " . TABLE_CONTACT . " AS contact, " . TABLE_PROPERTIES . " AS properties WHERE ";
            $where = "contact.id=properties.id AND properties.type = " . $db->escape($type) . " AND properties.value LIKE '%$p%' AND (properties.visibility = 'visible' OR $admin) AND (contact.hidden = 0 OR $admin) ORDER BY lastname LIMIT $limit ";
            $sql = "$sel $where";
            break;
        case 'phone':
            $sel = "
                     SELECT CONCAT(lastname,', ',firstname) AS fullname, properties.value AS value, properties.label AS label, address.type AS addrtype
                     FROM (" . TABLE_CONTACT . " AS contact, " . TABLE_PROPERTIES . " AS properties)
                     LEFT JOIN " . TABLE_ADDRESS . " AS address ON (properties.refid = address.refid) WHERE ";
            $where = "contact.id=properties.id AND properties.type = " . $db->escape($type) . " AND properties.value LIKE '%$p%' AND (properties.visibility = 'visible' OR $admin) AND (contact.hidden = 0 OR $admin) ORDER BY lastname LIMIT $limit ";
            $sql = "$sel $where";
            break;
        case 'address':
            $sel = "SELECT CONCAT(lastname,', ',firstname) AS fullname, line1 AS address1, line2 AS address2, CONCAT(city,' ',state,' ',zip) AS address3 FROM " . TABLE_CONTACT . " AS contact, " . TABLE_ADDRESS . " AS address WHERE ";
            $where = "contact.id=address.id AND (line1 like '%$p%' OR line2 like '%$p%' OR city like '%$p%') AND (hidden = 0 OR $admin) ORDER BY lastname LIMIT $limit ";
            $sql = "$sel $where";
            break;
        default:
            $n=count($CONFIG_SEARCH_CUSTOM);
            for($i=0;$i<$n;$i++)
            {
                if($type=="custom_$i")
                {
                    $sel = "SELECT CONCAT(lastname,', ',firstname) AS fullname, properties.value AS value, properties.label AS label
                    FROM " . TABLE_CONTACT . " AS contact, " . TABLE_PROPERTIES . " AS properties WHERE ";
                    $where = "contact.id=properties.id AND properties.type = 'other' AND properties.label = '".$CONFIG_SEARCH_CUSTOM[$i]."' AND properties.value LIKE '%$p%' AND (properties.visibility = 'visible' OR $admin) AND (contact.hidden = 0 OR $admin) ORDER BY lastname LIMIT $limit ";
                    $sql = "$sel $where";
                    break;
                }
            }
            break;
    }
    
    $db->query($sql);
    
    header('Content-Type: text/html; charset=UTF-8',true);
    
    echo("<ul class='autocompletegoto-contacts'>");

    while ($row=$db->next())
    { 
        echo("<li class='autocompletegoto-contact'>");
        echo("<div class='autocompletegoto-name'>".$row[0]."</div>");
        switch ($type)
        {
            case 'name':
                if ($row[1])
                    echo("<div class='autocompletegoto-other'><span class='informal'>$row[1]</span></div>");
                break;
            case "email":
                echo("<div class='autocompletegoto-other'><span class='informal'>$row[1]</span></div>");
                break;
            case "www":
                echo("<div class='autocompletegoto-other'><span class='informal'>$row[1]</span></div>");
                break;
            case "chat":
                echo("<div class='autocompletegoto-other'><span class='informal'>");
                if($row[2]!=""){echo("$row[2]: ");}
                echo("$row[1]</span></div>");
                break;
            case "address":
                echo("<div class='autocompletegoto-other'><span class='informal'>");
                if($row[1]!=""){echo("$row[1]<br/>");}
                if($row[2]!=""){echo("$row[2]<br/>");}
                if($row[3]!=""){echo("$row[3]<br/>");}
                echo("</span></div>");
                break;
            case "phone":
                echo("<div class='autocompletegoto-other'><span class='informal'>");
                if($row[3]!=""){echo("$row[3]: ");}
                echo("$row[1]");
                if($row[2]!=""){echo(" ($row[2])");}
                echo("<br /></span></div>");
                break;
            default:
                $n=count($CONFIG_SEARCH_CUSTOM);
                for($i=0;$i<$n;$i++)
                {
                    if($type=="custom_$i")
                    {
                        echo("<div class='autocompletegoto-other'><span class='informal'>$row[1]</span></div>");
                        break;
                    }
                }
        }
        echo("</li>");
    }
    echo("</ul>");
?>
