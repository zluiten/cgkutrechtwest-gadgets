<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  THE ADDRESS BOOK RELOADED
 *   
 *************************************************************
 *
 *  contact/media.php
 *  Returns a media of a contact (currently only images).
 *
 *************************************************************/

// If a whitespace is output from header files
ob_start();

chdir('..');
require_once('lib/init.php');

require_once('Contact.class.php');
require_once('RightsManager.class.php');
require_once('ErrorHandler.class.php');

// kill whitespaces
ob_end_clean();

$rightsManager = RightsManager::getSingleton();

if (!isset($_GET['id']))
    $_GET['id'] = '';
    
$contact = Contact::newContact(intval($_GET['id']));

if (!$rightsManager->mayViewContact($contact))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

$length = 0;
$mimeType = '';
$media = &$contact->getMedia('pictureData',$mimeType,$length);

if($media == null)
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $length);

echo $media;

exit();

?>
