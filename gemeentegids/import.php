<?php
$dbhost = 'localhost';
$dbname = 'tab3';
$dbuser = 'zluiten';
$dbpass = 'wwursxp83';

$db = mysql_connect($dbhost, $dbuser, $dbpass) or die('Can\'t connect to database host');
mysql_select_db($dbname, $db) or die('Unable to select database');

mysql_query('TRUNCATE TABLE tab3_contact') or die(mysql_error());
mysql_query('TRUNCATE TABLE tab3_address') or die(mysql_error());
mysql_query('TRUNCATE TABLE tab3_properties') or die(mysql_error());
mysql_query('TRUNCATE TABLE tab3_groups') or die(mysql_error());

$result = mysql_query('SELECT * FROM tab3_contact2', $db);
$maanden = array('jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec');
while($srcrow = mysql_fetch_array($result)) {
	if($srcrow['Geslacht'] == 'M') $srcrow['Geslacht'] = 'male';
	if($srcrow['Geslacht'] == 'V') $srcrow['Geslacht'] = 'female';
	if(!empty($srcrow['Geboortedatum'])) {
		$verjaardag = explode('-', $srcrow['Geboortedatum']);
		$maand = array_search($verjaardag[1], $maanden) + 1;
		if($verjaardag[2] > 10) $verjaardag[2] = '19'.$verjaardag[2];
		if($verjaardag[2] <= 10) $verjaardag[2] = '20'.$verjaardag[2];
		$srcrow['Geboortedatum'] = $verjaardag[2].'-'.$maand.'-'.$verjaardag[0];
	}
	if($srcrow['Soort lid'] == 'D') $srcrow['Soort lid'] = 1;
	if($srcrow['Soort lid'] == 'B') $srcrow['Soort lid'] = 2;
	if($srcrow['Soort lid'] == 'G') $srcrow['Soort lid'] = 7;
	if($srcrow['Wijk'] == 1) $srcrow['Wijk'] = 3;
	if($srcrow['Wijk'] == 2) $srcrow['Wijk'] = 4;
	if($srcrow['Wijk'] == 3) $srcrow['Wijk'] = 5;
	if($srcrow['Wijk'] == 4) $srcrow['Wijk'] = 6;

	foreach ($srcrow as $k => &$v) {
		$v = mysql_real_escape_string(trim($v));
	}
	mysql_query("INSERT INTO tab3_contact (firstname, middlename, lastname, sex, geboortedatum, nickname) VALUES ('".$srcrow['Voorletter']."', '".$srcrow['Tussenvoegsel']."', '".$srcrow['Achternaam']."', '".$srcrow['Geslacht']."', '".$srcrow['Geboortedatum']."', '".$srcrow['Roepnaam']."')") or die("1 ".mysql_error());
	$contactid = mysql_insert_id($db);
	if($srcrow['Voorletter'] == "admin" || $srcrow['Voorletter'] == "inzage")
		mysql_query("UPDATE tab3_contact SET hidden=1 WHERE id = '".$contactid."'") or die(mysql_error());
	mysql_query("INSERT INTO tab3_address (id, line1, line2, zip, city, country) VALUES ('".$contactid."', '".$srcrow['Woonstede']."', '".$srcrow['Straat']." ".$srcrow['Huisnummer']."', '".$srcrow['Postcode']."', '".$srcrow['Woonplaats']."', 'nl')") or die("2 ".mysql_error());
	if(!empty($srcrow['Telefoon']))
		mysql_query("INSERT INTO tab3_properties (id, value, type, visibility) VALUES ('".$contactid."', '".$srcrow['Telefoon']."', 'phone', 'visible')") or die("3 ".mysql_error());
	if(!empty($srcrow['E-mail']))
		mysql_query("INSERT INTO tab3_properties (id, value, type, visibility) VALUES ('".$contactid."', '".$srcrow['E-mail']."', 'email', 'visible')") or die("4 ".mysql_error());
	if(!empty($srcrow['Soort lid']))
		mysql_query("INSERT INTO tab3_groups (id, groupid) VALUES ('".$contactid."', '".$srcrow['Soort lid']."')") or die("5 ".mysql_error());
	if(!empty($srcrow['Wijk']))
		mysql_query("INSERT INTO tab3_groups (id, groupid) VALUES ('".$contactid."', '".$srcrow['Wijk']."')") or die("6 ".mysql_error());
	echo("SUCCESFULLY INSERTED ".$srcrow['Voorletter']." ".$srcrow['Tussenvoegsel']." ".$srcrow['Achternaam']." ".$verjaardag[2]." <br />");
}

?>