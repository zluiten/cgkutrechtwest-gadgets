<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* Shows the import interface. Curretly no security (only after post).
*/

chdir('../../'); // goto main directory

require_once('lib/init.php');
require_once('plugins/Import/PageImport.class.php');

$page = Page::newPage('PageImport');
echo $page->create();
exit();

?>
