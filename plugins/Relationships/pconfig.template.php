<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  The Address Book Reloaded 3.0 - SingleSignOn
 *
 *  @author Thomas Katzlberger
 *
 */
 
// Restrict target contacts for relation in some strange way
//$GLOBALS['CONFIG_REL_DROPDOWN_SUBSET'] = "xsltDisplayType='expertise'";
$GLOBALS['CONFIG_REL_DROPDOWN_SUBSET'] = "xsltDisplayType='expertise'";

// Fetch related properties by label name and export them as XML array('xmlTagName'=>'Property Label')
$GLOBALS['CONFIG_REL_XML_OTHER_PROPERTIES'] = array();
$GLOBALS['CONFIG_REL_XML_DATE_PROPERTIES'] = array();

// Fetch target related properties by label name and export them as XML array('xmlTagName'=>'Property Label')
$GLOBALS['CONFIG_RELT_XML_OTHER_PROPERTIES'] = array('category'=>'Project Category','totalValue'=>'Total Value','swarcoValue'=>'SWARCO Value');
$GLOBALS['CONFIG_RELT_XML_DATE_PROPERTIES'] = array('awarded'=>'Awarded','completed'=>'Completed');

?>
