<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  THE ADDRESS BOOK RELOADED 3.0 - Export Plugin
 *   
 *
 ****************************************************************
 *  export.php
 *  Exports entries to a variety of other formats.
 *
 *************************************************************/

chdir('../../');
    
require_once('lib/init.php');
require_once('ContactImportExport.class.php');

// logged in?
$rightsManager = RightsManager::getSingleton();

if(!isset($_GET['mode']))
    $_GET['mode'] = 'default';

if(!isset($_GET['id'])) // invalid
    $errorHandler->error('invArg','Contact id missing.',basename($_SERVER['SCRIPT_NAME']));

// export whom?
$contact = Contact::newContact($_GET['id']);

// allowed to view?
if(!$rightsManager->mayViewContact($contact))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
    
// ** EXPORT FORMATS **
switch($_GET['format']) 
{
    // How about this? http://www.oasis-open.org/committees/ciq/ciq.html#6
    
    default:
    /* SEE: http://vcardmaker.wackomenace.co.uk/, http://tools.ietf.org/html/rfc2426
     * There is still lots of info missing: Occupation -> TITLE:, Company -> ?, Department
     * Please post improvements to: http://sourceforge.net/tracker/?group_id=172286&atid=861164 as attachment!!
     * Or to the developer forums at: http://sourceforge.net/forum/forum.php?forum_id=590644
    */
    case "vcard":
        $output = ContactImportExport::vCardExport($contact,'WORK');
        
        $fn = mb_convert_encoding($contact->contact['firstname'],'ASCII') . mb_convert_encoding($contact->contact['lastname'],'ASCII') . '.vcf';
        header("Content-Type: text/x-vCard; name=$fn");
        header("Content-Disposition: attachment; filename=$fn");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0', true);
        header('Pragma: none', true);
        header("Content-Length: ".strlen($output));
        echo $output;
        break; // end vcard
    case "xml":
        $output = ContactImportExport::xmlExport($contact);
        
        $fn = mb_convert_encoding($contact->contact['firstname'],'ASCII') . mb_convert_encoding($contact->contact['lastname'],'ASCII') . '.xml';
        header("Content-Type: text/xml; name=$fn");
        //header("Content-Disposition: attachment; filename=$fn"); // download?
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0', true);
        header('Pragma: none', true);
        header("Content-Length: ".strlen($output));
        echo $output;
        break; // end vcard
}


/********************************************************************************
 ** EUDORA NICKNAMES FORMAT
 **
 ********************************************************************************
        case "eudora":

            // Retrieve data associated with given ID
            $nnListQuery = "SELECT contact.id, CONCAT(firstname,' ', lastname) AS fullname, email FROM " . TABLE_CONTACT . " AS contact, " . TABLE_EMAIL . " AS email WHERE contact.id=email.id ORDER BY contact.id";

            $r_contact = mysql_query($nnListQuery, $db_link)
                or die(reportSQLError($nnListQuery));

            // OUTPUT
            header("Content-type: text/plain");
            header("Content-disposition: attachment; filename=NNdbase.txt");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: 0");

            while ($tbl_contact = mysql_fetch_array($r_contact)) {
                echo("\n");
                echo('alias "' . 
                      $tbl_contact['fullname'] . '" ' .
                      $tbl_contact['fullname'] . ' <' . 
                      $tbl_contact['email'] . '>');
            }
        
            // END
            break;

/********************************************************************************
 ** COMMA-SEPARATED VALUES (CSV) FORMAT
 **
 ** thanks to sineware
 ********************************************************************************
        case "csv":

            // QUERY
            $csvQuery = "SELECT contact.id, firstname, middlename, lastname, birthday, notes, 
                                   email.email, address.line1, address.line2, address.city, address.state, address.zip, 
                                address.phone1, address.phone2, otherphone.phone, websites.webpageURL
                         FROM ". TABLE_CONTACT ." AS contact
                        LEFT JOIN ". TABLE_EMAIL ." AS email ON contact.id=email.id
                        LEFT JOIN ". TABLE_ADDRESS ." AS address ON address.id=contact.id
                        LEFT JOIN ". TABLE_OTHERPHONE ." AS otherphone ON contact.id=otherphone.id
                        LEFT JOIN ". TABLE_WEBSITES ." AS websites ON contact.id=websites.id";
            $r_contact = mysql_query($csvQuery, $db_link)
                or die(reportSQLError($csvQuery));

            // OUTPUT
            header("Content-Type: text/comma-separated-values");
            header("Content-disposition: attachment; filename=tab.csv");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: 0");

            echo("firstname,middlename,lastname,birthday,email,address1,address2,city,state,zip,phone1,phone2,phone3,website,notes\n");
            while ($tbl_contact = mysql_fetch_array($r_contact)) {
                // Most  variables are checked for the comma (,) character, which will be
                // removed if found. This is to prevent these fields from breaking the CSV format.
                echo(str_replace(",","",$tbl_contact['firstname']) . "," .
                    str_replace(",","",$tbl_contact['middlename']) . "," .
                    str_replace(",","",$tbl_contact['lastname']) . "," .
                    $tbl_contact['birthday'] . "," .
                    $tbl_contact['email'] . "," . 
                    str_replace(",","",$tbl_contact['line1']) . "," . 
                    str_replace(",","",$tbl_contact['line2']) . "," . 
                    str_replace(",","",$tbl_contact['city']) . "," . 
                    str_replace(",","",$tbl_contact['state']) . "," . 
                    str_replace(",","",$tbl_contact['zip']) . "," . 
                    str_replace(",","",$tbl_contact['phone1']) . "," .
                    str_replace(",","",$tbl_contact['phone2']) . "," .
                    str_replace(",","",$tbl_contact['phone']) . "," .
                    str_replace(",","",$tbl_contact['webpageURL']) . "," .
                    str_replace(",","",$tbl_contact['notes']) . "\n");
            }

            // END
            break;


/********************************************************************************
 ** TEXT FORMAT
 **
 ** (thanks to David Léonard) -- Beta, but working. -- broken pending existence of acessBD.php
 ********************************************************************************
        case "text":

            // QUERY
                $query ="
                    SELECT 
                      `address_contact`.`id`,
                      `address_contact`.`firstname`,
                      `address_contact`.`lastname`,
                      `address_contact`.`middlename`,
                      `address_contact`.`primaryAddress`,
                      `address_contact`.`birthday`,
                      `address_contact`.`nickname`,
                      `address_contact`.`pictureURL`,
                      `address_contact`.`notes`,
                      `address_contact`.`lastUpdate`,
                      `address_contact`.`hidden`,
                      `address_contact`.`whoAdded`,
                      `address_address`.`type`,
                      `address_address`.`line1`,
                      `address_address`.`line2`,
                      `address_address`.`city`,
                      `address_address`.`state`,
                      `address_address`.`zip`,
                      `address_address`.`country`,
                      `address_address`.`phone1`,
                      `address_address`.`phone2`
                    FROM
                      `address_contact`
                      INNER JOIN `address_address` ON (`address_contact`.`id` = `address_address`.`id`)
                    
                ";
            
            $data     = new accesBDlecture ($query,"","");
            $query    = "SELECT * FROM address_grouplist WHERE 1";
            $entete = new accesBDlecture($query,"","");
            
            // OUTPUT
            header("Content-type: text/plain");
            header("Content-disposition: attachment; filename=tab.txt");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: 0");

            
            //affichage des entetes communs
            echo "NUMERO\tPRENOM\tNOM\tTITRE\tANNIVERSAIRE\tMÀJ LE\tPROPRIETAIRE\tTYPE ADRESSE\tADRESSE1\tADRESSE2\tVILLE\tETAT\tNPA\tPAYS\tTEL1\tTEL2\t";
            
            //affichage des entetes correspondant aux noms des groupes
            foreach ($entete->row as $courant) {
                if ($courant == NULL) break;
                if ($courant->groupid <3)
                    {continue;}
                else
                    {echo"$courant->groupname\t";}
            }
            echo"\n";
            
            //remplissage des données suivant les entetes
            foreach ($data->row as $donnee) {
                if ($donnee == NULL) break;
                //sélection du nom du pays
                $query                 = "SELECT countryname FROM address_country WHERE id = ".$donnee->country." ";
                $pays                 = new accesBDlecture($query,"","");
                $paysCourant     = $pays->row[0]->countryname;
                
                //affichage des données communes
                echo "$donnee->id\t$donnee->firstname\t$donnee->lastname\t$donnee->nickname\t$donnee->birthday\t$donnee->lastUpdate\t$donnee->whoAdded\t$donnee->type\t$donnee->line1\t$donnee->line2\t$donnee->city\t$donnee->state\t$donnee->zip\t$paysCourant\t$donnee->phone1\t$donnee->phone2\t";
                
                //sélection des des groupes dont fait partie l'adresse courante
                $query = "SELECT * FROM address_groups WHERE id =".$donnee->id." ";
                $groupe = new accesBDlecture($query,"","");
                $query    = "SELECT * FROM address_grouplist WHERE 1 ORDER BY 1";
                $entete = new accesBDlecture($query,"","");
                foreach ($entete->row as $courant) {
                        if ($courant == NULL) break;
                        if ($courant->groupid <3)
                            {continue;}
                        else
                            {
                                $valide = "NON\t";
                            foreach ($groupe->row as $groupeCourant) {
                                if ($groupeCourant == NULL)break;
                                //comparaison avec les groupes actuels
                                if ($courant->groupid == $groupeCourant->groupid) $valide = "OUI\t";
                            }
                            echo $valide;
                    }
                }
                echo "\n";
                unset ($query,$pays,$paysCourant);

            }

            // END
            break;


/********************************************************************************
 ** GMAIL-IMPORTABLE CSV FORMAT
 **
 ********************************************************************************
        case "gmail":

            // QUERY
            $gmailQuery = "SELECT firstname, lastname, email, type FROM ". TABLE_CONTACT ." AS contact LEFT JOIN ". TABLE_EMAIL ." AS email ON contact.id=email.id WHERE email.email IS NOT NULL";
            $r_contact = mysql_query($gmailQuery, $db_link)
                or die(reportSQLError($gmailQuery));

            // OUTPUT
            header("Content-Type: text/comma-separated-values");
            header("Content-disposition: attachment; filename=tab_gmail.csv");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Expires: 0");

            echo("Name,Email Address\n");
            while ($tbl_contact = mysql_fetch_array($r_contact)) {
                // First Name, Last Name, and Type variables are checked for the comma (,) character, which will be
                // removed if found. This is to prevent these fields from breaking the CSV format.
                echo(str_replace(",", "",$tbl_contact['firstname']) . " " . str_replace(",", "",$tbl_contact['lastname']));
                if(str_replace(",", "",$tbl_contact['type'])) {
                    echo(" (" . str_replace(",", "",$tbl_contact['type']) . ")");
                }
                echo("," . $tbl_contact['email'] . "\n");
            }

            // END
            break;
*/
?>
