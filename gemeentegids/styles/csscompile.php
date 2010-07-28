<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

// Installer script only - prevent running from server
if(isset($_SERVER['HTTP_HOST']))
{
    echo 'Hello World!';
    exit(0);
}

/**
    * scans a directory for files with a certain extensions
    *
    * @param string $dir the directory to scan
    * @param string $ext the extension of the files wanted
    * @return array list of files matchin extention, without path
    * @static
    */
function scanDirectory($dir,$ext)
    {
        $dh  = opendir($dir);
        
        if($dh===false) //open error PHP warning should do
            return array();
        
        while (false !== ($filename = readdir($dh))) 
            if(substr($filename,strlen($filename)-strlen($ext)) === $ext)
                $files[] = $filename;
            elseif (is_dir($dir . '/' . $filename) && file_exists($dir . '/' . $filename . '/' . $filename . $ext))
                $files[] = $filename . '/' . $filename . $ext;
                
        return $files;
    }

function makeCSS($file)
    {
        if(substr($file,-8)=='.pre.css')
            return '';
            
        echo "FILE: ".$file."\n";
        $csscontent = file_get_contents($file);
        
        if($csscontent===FALSE)
        {
            echo "      not found.\n";
            return '';
        }
        
        $comp ='';
        
        for($i=0;$i<strlen($csscontent);)
        {
            $x = strpos($csscontent,'@import url(',$i);
            
            if($x===FALSE)
            {
                $comp.=substr($csscontent,$i);
                break;
            }
            
            $comp.=substr($csscontent,$i,$x-$i);
            
            $x+=12;
            $y = strpos($csscontent,')',$x);
            $import = substr($csscontent,$x,$y-$x);
            $comp.= makeCSS($import);
            $i=$y+2; // skip );
        }
        
        return $comp;
    }
    
// ===== MAIN =====
    $files = scanDirectory('.','css');
    
    //var_dump($files);
    foreach($files as $f)
    {
        $dir = substr($f,0,-4);
        file_put_contents($dir.'/'.$dir .'.pre.css',makeCSS($f));
    }
?>
