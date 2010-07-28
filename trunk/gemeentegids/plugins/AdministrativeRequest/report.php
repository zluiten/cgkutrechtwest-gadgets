<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* Shows a feature/rank list where 'feature' is any property label for example 
* from Additional Information, but you could sort by date too. Then sorts by value.
* This thingy can be popped in instead of the contact/changedlist.php
*
* NO SECURITY! ALWAYS will display a name and the feature. 
* The links to contacts cannot be followed if not logged in.
*/

chdir('../../'); // goto main directory

require_once('lib/init.php');

if((@include_once('plugins/AdministrativeRequest/pconfig.php'))!=1)
    require_once('plugins/AdministrativeRequest/pconfig.template.php');

require_once('plugins/AdministrativeRequest/PageAdministrativeRequestsReport.class.php');

// currently this will look for properties with the label 'ELO'
$page = new PageAdministrativeRequestsReport(); // descending, limit 100
echo $page->create();

exit();

?>
