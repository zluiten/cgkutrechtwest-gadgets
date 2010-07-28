<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link ContactImage} and uses {@link ImageResampler}
* @author Tobias Schlatter
* @package frontEnd
*/

/** */

require_once('StringHelper.class.php');
require_once('Options.class.php');
require_once('ErrorHandler.class.php');
require_once('ImageResampler.class.php');

/**
* represents the mugshot of a certain contact. Example: $ci = new ContactImage($contact). echo $ci->uri();
* @package frontEnd
*/

class ContactImage
{
    /**
    * @var Contact the contact, which the image belongs to
    */
    var $contact;
    
    /**
    * Constructor
    * 
    * Initializes {@link $contact}
    * @param Contact $contact contact which the image should belong to
    */
function ContactImage(&$contact) {
        $this->contact = &$contact;
    }
    
    /**
    * Resamples/crops image file
    *
    * This method reads the requested file, checks, whether it should
    * just resample it (make the file at most as big as the size defined in
    * the options) or if it should crop it in a way that it will fill the whole
    * space available
    * @param string $file path of the file to resample/crop
    * @return string jpeg image stream
    * @global ErrorHandler used for error handling
    * @global Options used to determine mode and dimensions
    */
function resampleAndReturn($file) {
        
        global $errorHandler, $options;
        
        $resampler = new ImageResampler($file,$error);
        
        if ($error) {
            $errorHandler->error('image',$error);
            return false;
        }
        
        $resampler->resample($options->getOption('picWidth'),$options->getOption('picHeight'),$options->getOption('picCrop'));
        
        return $resampler->asJPEG();

    }
    
    /**
    * creates the full src uri to the mugshot image of a contact or returns empty string
    * 
    * @global Options used to determine, whether to show a nobody picture or nothing, if no picture is available
    * @return string uri to display image or '' if none.
    */
function uri()
    {
        global $CONFIG_TAB_ROOT;
        
        if ($this->contact->contact['pictureURL'])
            return $this->contact->contact['pictureURL'];
                
        if ($this->contact->contact['pictureData'])
            return $CONFIG_TAB_ROOT . 'contact/media.php?id=' . $this->contact->contact['id'];
                
        return '';
    }

    /**
    * creates the html img tag to display the mugshot of a contact
    * 
    * @param string $class the css class of the img tag
    * @global Options used to determine, whether to show a nobody picture or nothing, if no picture is available.
    *         picWidth>0 adds width=xxx to IMG tag this has no effect on DB pics but on URL referenced pics. picHeight is ignored to preserve aspect ratio.
    * @return string html to display image
    */
function html($class)
    {
        global $options,$CONFIG_NOBODY_PICTURE;

        $htmlspec = '';
        if($options->getOption('picWidth') >= 0 && $options->getOption('picForceWidth'))
            $htmlspec .= 'width="' . $options->getOption('picWidth') . ' "';
        if($options->getOption('picHeight') >= 0 && $options->getOption('picForceHeight'))
            $htmlspec .= 'height="' . $options->getOption('picHeight') . ' "';

        
        $uri = $this->uri();
        $title = $this->contact->generateFullName('text');
        if(!empty($uri))
            return "<a href='$uri' rel='lytebox' title='$title'><img class='$class' src='$uri' alt='$title' $htmlspec/></a>";
        
        if ($options->getOption('picAlwaysDisplay'))
            return '<img class="' . $class . '" src="../'.(isset($CONFIG_NOBODY_PICTURE) ? $CONFIG_NOBODY_PICTURE : 'lib/icons/nobody.gif').'" alt="' .
                $this->contact->contact['firstname'] . ' ' . $this->contact->contact['lastname'] . '" '.$htmlspec.'/>';
        
        return '';
    }
    
    /**
    * gets the type of the image, i.e. the way the image is saved
    * 
    * possible returns are 'file', 'data' or 'empty'
    * file and empty are straightforward, data means the image is saved in
    * the database as a blob
    * @return string type of image
    * @global Options used to determine, whether empty should be returned if no picture
    */
function getType() {
        
        global $options;
        
        if ($this->contact->contact['pictureURL'])
            return 'file';
                
        if ($this->contact->contact['pictureData'])
            return 'data';
        
        if ($options->getOption('picAlwaysDisplay'))
            return 'file';
                
        return 'empty';
        
    }
    
    /**
    * returns the size of the image
    * 
    * @return array array of type 'width' => xy, 'height' => xy, or null if no image
    */
function getSize() {
        
        if ($this->getType() == 'empty')
            return null;
            
        if ($this->getType() == 'file') {
            $tmp = @getimagesize($this->getData());
            if (!$tmp)
                return null;
            return array(
                'width' => $tmp[0],
                'height' => $tmp[1]
            );
        }
        
        $tmpFileName = tempnam('mugshots/','tmp');
        $tmpFile = fopen($tmpFileName,'wb');
        fwrite($tmpFile,$this->getData());
        fclose($tmpFile);
        $tmp = getimagesize($tmpFileName);
        unlink($tmpFileName);
        return array(
            'width' => $tmp[0],
            'height' => $tmp[1]
        );
        
    }
    
    /**
    * returns image data
    *
    * this function either returns the path of the image (file), a jpeg stream (data), or 
    * the empty string (empty), according to the value returned by {@link getType()}
    * @global Options used to determine, whether to show a nobody picture or nothing, if no picture is available
    * @global string used to transfer an absolute http url (i.e. /tab/mugshots) to a filesystem path (i.e. /srv/www/htdocs/tab/mugshots)
    * @return string filename or jpeg stream or empty string
    */
function getData() {
        
        global $options, $CONFIG_ABSOLUTE_LOCAL_MUGSHOT_PATH, $CONFIG_NOBODY_PICTURE;
        
        if ($this->contact->contact['pictureURL'])
            if (substr($this->contact->contact['pictureURL'],0,1) == '/')
                return $CONFIG_ABSOLUTE_LOCAL_MUGSHOT_PATH . substr(strrchr($this->contact->contact['pictureURL'],'/'),1);
            else
                return $this->contact->contact['pictureURL'];
                
        if ($this->contact->contact['pictureData'])
            return $this->contact->contact['pictureData'];
        
        if ($options->getOption('picAlwaysDisplay'))
            return (isset($CONFIG_NOBODY_PICTURE) ? $CONFIG_NOBODY_PICTURE : 'lib/icons/nobody.gif');
                
        return '';
        
    }
}

?>
