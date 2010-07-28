<?php
    // load master language
    require('en.lang.php');
    $master = $country;
    
    // load language to check!
    require('de.lang.php');
    
    // pretty print
    function dump($a)
    { foreach($a as $k => $v) echo "'".$k."'=>\"".$v."\",\n"; return count($a); }
    
    $n=0;
    echo "========== missing ========\n";
    $n += dump(array_diff_key($master,$country));
    echo "========== removed ========\n";
    $n += dump(array_diff_key($country,$master));
    
    if($n == 0) // no missing, no removed
    {
        echo "========== SORTED ========\n";
        $c = array_flip($country);
        
        if(count($c) != count($country))
        {
            echo 'FLIP FAILED!:'.count($c).'/'. count($country).": Duplicates:\n";
            dump(array_diff_key($country,array_flip($c)));
            exit -1;
        }
        
        ksort($c);
        $cc = array_flip($c);
        
        dump($cc);
        echo "========== SUCCESS! ========\n";        
    }
?>
