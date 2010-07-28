<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link FileHelper}
* @package utilities
* @author Thomas Katzlberger
*/

/**
* Utility class to support file operations. 
    Scan a directory.
* @package utilities
*/
class FileHelper {

    /**
    * scans a directory for files with a certain extensions
    *
    * @param string $dir the directory to scan
    * @param string $ext the extension of the files wanted
    * @return array list of files matching the extention, without path
    * @static
    */
function scanDirectory($dir,$ext)
    {
        $dh  = opendir($dir);
        
        if($dh===false) //open error PHP warning should do
            return array();
        
        $files = array();
        while (false !== ($filename = readdir($dh)))
        {
            if(StringHelper::strEndsWith($filename,$ext)) // Single file Plugin '.plugin.php'
                $files[] = $filename;
            else // Plugin in its own directory
            if (is_dir($dir . '/' . $filename) && file_exists($dir . '/' . $filename . '/' . $filename . $ext))
                $files[] = $filename . '/' . $filename . $ext;
        }
        
        return $files;
    }
    
   /**
    * removes first extension (x.tar.gz => x.tar)
    *
    * @param string $filename the filename to modify
    * @static
    */
function removeExtension(&$filename)
    {
        $filename = substr($filename, 0, strrpos($filename, "."));
    }
    
   /**
    * strip all extensions (x.tar.gz => x.tar)
    *
    * @param array $filenamesArray the filename to modify
    * @static
    */
function removeExtensions(&$filenamesArray)
    {
        $func = create_function('&$f','$f = substr($f, 0, strrpos($f, "."));');
        array_walk($filenamesArray,$func);
    }
    
   /**
    * Fetches (with curl) a binary or normal file (via http or https) from a remote site as string using HTTPBasicAuth.
    * EXAMPLE: FileHelper::fetchDataBasicAuthentication('http://localhost/bli/bla/blum.xml','username','password');
    * @param string $url of the data to fetch
    * @param string $usr optional username (do not use HTTPBasicAuth if $usr==NULL); default: NULL
    * @param string $pass optional password; default: ''
    * @return string $filename OR FALSE if HTTP_RESPONSE >= 400. If larger than the PHP memory limit the request should crash - this is actually a security feature?
    * @static
    */
function fetchDataBasicAuthenticationTmpFile($url,$postData=null,$usr=NULL,$pass='')
    {
        $fn = tempnam("/tmp", "tmpFH");
        $data = FileHelper::fetchDataBasicAuthentication($url,$postData,$usr,$pass);
        
        if($data == FALSE)
            return FALSE;
        
        file_put_contents($fn,$data);
        return $fn;
    }
    
   /**
    * Fetches (with curl) a binary or normal file (via http or https) from a remote site as string using HTTPBasicAuth.
    * EXAMPLE: FileHelper::fetchDataBasicAuthentication('http://localhost/bli/bla/blum.xml','username','password');
    * @param string $url of the data to fetch
    * @param string $postData array('name' => 'Foo', 'file' => '@/home/user/test.png') || NULL if not needed
    * @param string $usr optional username (do not use HTTPBasicAuth if $usr==NULL); default: NULL
    * @param string $pass optional password; default: ''
    * @return string $data content of the URL OR FALSE if HTTP_RESPONSE >= 400. If larger than the PHP memory limit the request should crash - this is actually a security feature?
    * @static
    */
function fetchDataBasicAuthentication($url,$postData=null,$usr=NULL,$pass='')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        
        if($usr!==NULL)
            curl_setopt($curl, CURLOPT_USERPWD,"$usr:$pass");
        
        if($postData != null)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);  // we do not have SSL-CA data to verify certs in curl
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY); // allow ANY authentication method for BasicAuth
        
        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');  // pretend to be InternetExplorer!!
        //curl_setopt($curl, CURLOPT_HTTPHEADER, array( 'Accept-Language: en' )); // 'Accept-Encoding: gzip', 
        
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);  // do not return the header in $data
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // return response as string
        $data = curl_exec($curl); // Fetch the content
        
        // Get response code
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        $code = intval($code);
        if($code >= 400)
            return FALSE;
        
        return $data;
    }
}
?>
