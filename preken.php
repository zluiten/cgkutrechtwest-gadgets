<?php
/* Geeft overzicht van alle preken in onderliggende directory in 'rss' formaat wat in gadget eenvoudig geparsed kan worden...*/
date_default_timezone_set('Europe/Amsterdam');

if ($handle = opendir('preken')) {
    echo '<?xml version="1.0" ?>';
    echo '<rss version="2.0">';
    echo '<channel>';
    echo '<title>Chr. Geref. Kerk Utrecht-West (Mattheuskerk)</title>';
    echo '<link>http://www.cgk-utrechwest.nl/</link>';
    echo '<description>Archief uitzendingen van de Chr. Geref. Kerk Utrecht-West (Mattheuskerk)</description>';
    echo '<image>';
    echo '<url>http://www.kerkomroep.nl/kerk_foto/afb36559.jpg</url>';
    echo '<title>Chr. Geref. Kerk Utrecht-West (Mattheuskerk)</title>';
    echo '<link>http://www.cgk-utrechtwest.nl/</link>';
    echo '</image>';


    /* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) {
        if (is_dir($file)) { continue; }
	echo '<item>';
	$starttime=substr($file,5,2).'-'.substr($file,8,2).'-'.substr($file,0,4).' '.substr($file,11,2).':'.substr($file,14,2);
	echo '<title>'.$starttime.' of '.strftime('%c',mktime(substr($file,11,2),substr($file,14,2),0,substr($file,5,2),substr($file,8,2),substr($file,0,4)));
	echo '</title>';
	echo '<link>http://media.cgk-utrechwest.nl/preken/'.$file.'?src=rss</link>';
        echo '<description>Uitzending van Chr. Geref. Kerk Utrecht-West (Mattheuskerk) op '.$starttime.'</description>';
        echo '<guid>http://media.cgk-utrechwest.nl/preken/'.$file.'?src=rss</guid>';
        echo '<enclosure url="http://media.cgk-utrechwest.nl/preken/'.$file.'?src=rss" type="audio/mpeg" />';
        echo '<pubDate>'.strftime('%c',mktime(substr($file,11,2),substr($file,14,2),0,substr($file,5,2),substr($file,8,2),substr($file,0,4))).'</pubDate></item>';

    }

    closedir($handle);
}
?>
