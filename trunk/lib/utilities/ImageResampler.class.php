<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link ImageResampler}
* @author Tobias Schlatter, Thomas Katzlberger
* @package utilities
*/

/** */

/**
* Reusable Object for image scaling. No direct error handling.
*
* 1. loads an image from a file
* 2. resampling/croping image to a defined size
* 3. returns jpeg data as string
*
* @package utilities
*/

class ImageResampler
{
    /**
    * @var binary the GD Image data
    */
    var $imageData;

    /**
    * @var int width of the image
    */
    var $width;
    
    /**
    * @var int height of the image
    */
    var $height;
    
    /**
    * @var int maximum width of the image (default 1600x1200). Will refuse to load and return an error. New cameras produce huge images that bust the memory limit of the server.
    */
    var $maxWidth;
    
    /**
    * @var int maximum height of the image (default 1600x1200). Will refuse to load and return an error. New cameras produce huge images that bust the memory limit of the server.
    */
    var $maxHeight;
    
    /**
    * Constructor
    * 
    * Loads an image from a file and sets the max. processing size of the image.
    * Calculations: 1600 x 1200 x 4 = 8MB, 3072 x 2304 x 4 = 32MB, most sizes beyond will terminate the script due to memory limits.
    * @param string $file filename of uploaded temp file etc.
    * @param string $errorString returns the error if object could not be created or null = no error
    * @param int $maxW max allowed image size that is below server's memory limit. Default: 1600x1200
    * @param int $maxH max allowed image size that is below server's memory limit. Default: 1600x1200
    */
function ImageResampler($file,&$errorString,$maxW=1600,$maxH=1200)
    {
        $errorString = null;
        
        if (!function_exists('imagejpeg') || !function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled') ||
            !function_exists('imagecreatefromgif') || !function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng'))
        {
             $errorString = 'ImageResampler: gd-lib >= 2.1.0 missing!';
             return NULL;
        }
        
        $this->maxWidth  = $maxW;
        $this->maxHeight = $maxH;
        
        // Crop mode (2) background color.
        $this->backgroundR = 255;
        $this->backgroundG = 255;
        $this->backgroundB = 255;
        
        $this->imageData = $this->_loadImageFromFile($file,$this->width,$this->height,$errorString);
        
        if (!$this->imageData) 
            return NULL;
    }

  /**
    * Set BG color value for crop mode 2 (black bar mode) Default is white bars.
    */
function setBackgroundRGB($r,$g,$b) // crop mode 2 only; default white bars
    {
        $this->backgroundR = $r;
        $this->backgroundG = $g;
        $this->backgroundB = $b;
    }
    
function _loadImageFromFile($file,&$width,&$height,&$error)
    {
        $s = getimagesize($file);
        
        if (!$s)
        {
            $error = 'Could not get image size.';
            return NULL;
        }
        
        $width = $s[0];
        $height = $s[1];
        
        if($width > $this->maxWidth || $height > $this->maxHeight)
        {
            $error = 'Image is too wide or high. Please reduce its dimensions and try again.';
            return NULL;
        }
        
        $img=NULL;
        
        // ret[2] ... 1 = GIF, 2 = JPG, 3 = PNG
        switch ($s[2])
        {
            case 1:
                $img = @imagecreatefromgif($file);
                break;
            case 2:
                $img = @imagecreatefromjpeg($file);
                break;
            case 3:
                $img = @imagecreatefrompng($file);
                break;
        }
        
        return $img;
    }
    
    /**
    * Resamples/crops image file to specified width/height
    *
    * This method reads the requested file, checks, whether it should
    * just resample it (make the file at most as big as the size defined in
    * the options) or if it should crop it in a way that it will fill the whole
    * space available
    * @param int $maxWidth desired width
    * @param int $maxHeight desired height
    * @param int $cropMode 1 = resample and crop to fit, 2 = resample and fill black bars, 3 = mystery
    */
function resample($maxWidth,$maxHeight,$cropMode = 1)
    {  
        $width = $this->width;
        $height = $this->height;
        
        if(1 == $cropMode) // crop smaller portion
        {
            $newImg = imagecreatetruecolor($maxWidth,$maxHeight);

            if($width>$height) //landscape
                 $scale = $maxHeight / $height;
            else // portrait
                 $scale = $maxWidth / $width;
            
            $srcX = ($width - $maxWidth/$scale)/2;
            $srcY = ($height - $maxHeight/$scale)/2;
            
            imagecopyresampled($newImg,$this->imageData,0,0,$srcX,$srcY,
                $maxWidth,$maxHeight, // dst
                $maxWidth/$scale,$maxHeight/$scale); //src
            
            $this->width = $maxWidth;
            $this->height = $maxHeight;
        }
        else if(2 == $cropMode) // fit wider portion (black bars)
        {
            $newImg = imagecreatetruecolor($maxWidth,$maxHeight);
            // background color fill
            imagefilledrectangle($newImg,0,0,$maxWidth,$maxHeight,imagecolorallocate($newImg,$this->backgroundR,$this->backgroundG,$this->backgroundB));
            
            if($width>$height) //landscape
                 $scale = $maxWidth / $width;
            else // portrait
                 $scale = $maxHeight / $height;
            
            $dstX = ($maxWidth - $width*$scale)/2;
            $dstY = ($maxHeight-$height*$scale)/2;
            
            imagecopyresampled($newImg,$this->imageData,$dstX,$dstY,0,0,
                $width*$scale,$height*$scale, // dst
                $width,$height); //src
                
            $this->width = $maxWidth;
            $this->height = $maxHeight;
        }
        else 
        {
            if ($width / $maxWidth > $height / $maxHeight) {
                $newWidth = $maxWidth;
                $newHeight = $height * $maxWidth / $width;
            } else {
                $newHeight = $maxHeight;
                $newWidth = $width * $maxHeight / $height;
            }
            
            $newImg = imagecreatetruecolor($newWidth,$newHeight);
            
            imagecopyresampled($newImg,$this->imageData,0,0,0,0,$newWidth,$newHeight,$width,$height);
            
            $this->width = $newWidth;
            $this->height = $newHeight;
        }
                    
        $this->imageData = $newImg;
        
        return true;
    }
    
    /**
    * FNCTION NEVER TESTED! Composites a mini image into this image. (magnify button etc.)
    * @param string $file filename of mini image
    * @param int $dx delta x offset from closest border, default = 0
    * @param int $dy delta y offset from closest border, default = 0
    * @param string $anchor 'tl' | 'tr' | 'bl' | 'br' default 'br' (bottom right) t=top, b=bottom, l=left, r=right
    */
function compositeOver($file,$anchor='br',$dx=0,$dy=0)
    {
        $mini = $this->_loadImageFromFile($file,$w,$h,$error);
        
        if($mini === NULL) // file not found, etc.
            return NULL;
        
        $x = 0 + $dx; $y = 0 + $dy; // default 'tl'
        switch($anchor)
        {
            case 'br': $x = $this->width - $w - $dx; $y = $this->height - $h - $dy; break;
            case 'bl': $x = $this->width + $dx; $y = $this->height - $h - $dy; break;
            case 'tr': $x = $this->width - $w - $dx; $y = $this->height + $dy; break;
        }
        
        imagecopy($this->imageData, $mini, $x, $y, 0, 0, $w, $h);
    }
    
    /**
    * converts the GD image to a JPEG and returns the data as string (does not modify internal image)
    * 
    * @param int $quality desired JPEG quality
    * @return string jpegData for writing to DB or file
    */ 
function asJPEG($quality = 95)
    {
        ob_start();
            
        imagejpeg($this->imageData,'',$quality);
        
        $jpgData = ob_get_contents();
        
        ob_end_clean();
        
        return $jpgData;
    }
}

?>
