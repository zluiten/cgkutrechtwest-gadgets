<?php
include_once("icalender.class.php");

//==============================
function decodeText($sText)
{
	return str_replace(array('"',',',':',';',"\\",'<br />','<br>'), array('""','\,','":"','\;','\\','\n','\n'), $sText);
}

function extend_dateinfo($varname,$input)
{
	$date=$input[$varname];
	$input[$varname.'_Y']=substr($date,0,4);
	$input[$varname.'_M']=substr($date,4,2);
	$input[$varname.'_D']=substr($date,6,2);
	$input[$varname.'_h']=substr($date,9,2);
	$input[$varname.'_m']=substr($date,11,2);
	$input[$varname.'_s']=substr($date,13,2);
	return $input;
}

function extend_with_rss($rss_info,$input)
{
	$start_ts=$input['start_ts'];
	date_default_timezone_set('Europe/Amsterdam');
	for($i=0; $i<count($rss_info->items); $i++) {
		$start_recording=strtotime($rss_info->items[$i][title]);
		if (abs($start_ts-$start_recording)<60*30*5) {
			// diff < 30 min --> match
			$input['link']=$rss_info->items[$i][link];
		}
		//$input["rsstitl_$i"]=$rss_info->items[$i][title];
		//$input["startrec_$i"]=$start_recording;
		//$input["diff_$i"]=abs($start_ts-$start_recording);
	}
	return $input;
}

$mysql_host = "localhost";
$mysql_user = "www";
$mysql_pwd  = "password";
$mysql_db   = "thedatabase";

//Connect to the server and log on
$db = mysqli_connect($mysql_host, $mysql_user, $mysql_pwd);
mysqli_select_db($db, $mysql_db);
if (!$db) {
	echo "Activation of $mysql_db failed";
	mysqli_error($db);
}

$sIcsFile = "cgk-utrechtwest.ics";
$sJsonFile = "cgk-utrechtwest.json";

$query="SELECT a.id as UID,
	 FORMAT_DATE('%Y%m%d%H%M%S',a.moment) as start,
	 FORMAT_DATE('%Y%M%d%H%U%S',a.moment+s.duur*60000) as einde,
	 s.omschrijving as omschrijving,
	 'Hendrika van Tussenbroekplantsoen 1, Utrecht 3533 Utrecht, Utrecht, Netherlands' as location,
	 'CGK Utrecht-West' as organiser,
	 0 as prio
	FROM activiteiten as a
	left join soorten as s on a.soortid=s.id";

$query="SELECT a.id as UID,
         DATE_FORMAT(a.moment,'%Y%m%dT%k%i%S') as start,
         UNIX_TIMESTAMP(a.moment) as start_ts,
         DATE_FORMAT(TIMESTAMPADD(MINUTE,s.duur,a.moment),'%Y%m%dT%k%i%S') as einde,
         UNIX_TIMESTAMP(TIMESTAMPADD(MINUTE,s.duur,a.moment)) as einde_ts,
         a.inleider as naam,
         IFNULL(a.thema,'') as thema,
         IFNULL(a.tekst,'') as tekst,
         IFNULL(a.liturgie,'') as liturgie,
         s.omschrijving as type,
         concat(a.onderwerp,a.details) as omschrijving,
         'Hendrika van tussenbroek plantsoen 1a' as location,
         'CGK Utrecht-West' as organiser
        FROM activiteiten as a
        LEFT JOIN soorten as s on a.soortid=s.id
	where DATE_SUB(CURDATE(),INTERVAL 3 MONTH)<a.moment
	   AND a.moment<DATE_ADD(CURDATE(),INTERVAL 3 MONTH)
	ORDER BY start DESC,einde
	";

$r=mysqli_query($query,$db);
$alarm_on=false;
$debug=false;

if ($r) {
	$oCal = new iCalender();

	$oCal->addTimeZone();

	$j_arr=array();

	$url='http://www.kerkomroep.nl/pages/rsskerk.php?mp=10452';
	require('magpierss-0.72/rss_fetch.inc');
	$rss = fetch_rss($url);

	while ($r && $info=mysqli_fetch_assoc($r)) {

		if($alarm_on) {
			$oCal->setAlarm($aText["alarm"]);
		}
		$oCal->addEvent($info['UID'], $info['start'].'Z', $info['einde'].'Z', $info['naam'],$info['type'].':'.$info['omschrijving'], $info['location'], $info['organiser'],"0");
		$info=extend_dateinfo('start',$info);
		$info=extend_dateinfo('einde',$info);
		$info=extend_with_rss($rss,$info);
		$j_arr[]=$info;

	// addEvent($sUid, $sStart, $sEnd, $sSummary, $sText, $sLocation, $sOrganiser, $sPriority = "0", $sClass = "PUBLIC", $sCreated = null, $sLastMod = null)
$lastapid=0;
	}
	if (count($j_arr)>0) {
		$fh=fopen($sJsonFile,'w');
		fwrite($fh, json_encode($j_arr));
		fclose($fh);
	}

	if ($debug) { echo "Found: ". mysqli_num_rows($r) ." rows.<br />\n"; }
	$oCal->writeCalendar($sIcsFile);
//	chmod($sIcsFile,0777);
	if ($debug) { echo "ICS file '".$sIcsFile."' has been created.<br />\n"; }
} else {
	echo "Retrieval of data failed<br/>\n";
	echo "qry: $query<br>\n";
	echo mysqli_error($db);
}

?>
