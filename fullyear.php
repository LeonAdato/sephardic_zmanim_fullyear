<?php
//this the username for http://www.geonames.org/, used to locate time zone based on lat/long information
//you MUST update zmansettings.ini with your geonames account or the script won't work
$ini_array = parse_ini_file("zmansettings.ini");
$tzusername= $ini_array['tzusername'];

/*
FUTURE TODO
=========


INFORMAITON BLOCK
========================
FILENAME: fullyear_zmanim.php
NAME: Full year zmanim
AUTHOR: Leon Adato
VERSION HISTORY
	0.0.1 - 0.0.10 - development
	0.1.0 - first pre-prod version
	0.1.1 - switched to internal php function for sunrise/sunset instead of API call
	0.1.2 - added more complete styling; zip code and address input
	0.1.3 - formatting updates so times fit on one line.
	0.1.4 - added debug option; fixed category duplicates for havdalah, candles, holiday
	0.1.5 - added ini for account information
DESCRIPTION	
Get Sephardic Zmanim for a complete year
Pulls information from external sites via API
uses statically assigned items (lat/long, zman calculations)
Formats output as HTML page
    
USAGE
==========
this page is served from a web server or at the commandline
along with the URL/URI, variables can include:

hebyear=yyyy
	the date you want zmanim for. if you couple this with shabbat=1/-s, this date must be a friday
lat=##.###
	latitude. Must also include longitude and tzid. Mutually exclusive from zip, city, or geoname.
long=##.###
	longitude. Must also include latitude and tzid. Mutually exclusive from zip, city, or geoname.
zip=#####
	zip code. Mutually exclusive from lat and long. Mutually exclusive from lat/long, city, or geoname.
geoname=######
	location specified by GeoNames.org numeric ID (See cities5000.zip from https://download.geonames.org/export/dump/.). Mutually exclusive from zip, city, or lat/long.
city=(city name)
	location specified by one of the Hebcal.com legacy city identifiers (https://github.com/hebcal/dotcom/blob/master/hebcal.com/dist/cities2.txt). Mutually exclusive from zip, geoname, or lat/long.


EXTERNAL SOURCE(S)
======================
https://www.hebcal.com/home/developer-apis
http://www.geonames.org/ (using this API requires a login)
*/

//set variables
$tzurl = $tzid = $get_tzname = $tzoff = $offset = "";
$zipcode = $zipurl = $zipid = $get_zipinfo = "";
$zmanurl = $zmanresponse = $get_zmanim = $getzmanim = $get_shabbats = "";
$hebyear = $nexthebyear = $latitude = $longitude = "";
$address = $addurl = $addurlencoded = $get_addinfo = "";
$candles = $candletext = $frisunset = $satmincha = $satsunset = $satarvit = $sattzet = $latemotzei = "";
$frimincha = $friminchakorb = $friminchaashrei = $friminchatext = "";
$SukkotDate = $PesachDate = "";
$startdate = $enddate = $friday = $saturday = $category = "";
$UTC = $newTZ = $UTCfrisunset = $UTCfrisunrise = "";
$datearray = $shabbatarray = $zipresponse = $addresponse = $tzresponse = array();
$debug = 0; 

//get location, year, and other common variables like you do with the weekly times
//get incoming variables
if(isset($_GET['hebyear'])) {$hebyear=stripcslashes($_GET['hebyear']);}
if(isset($_GET['zipcode'])) {$zipcode=stripcslashes($_GET['zipcode']); }
if(isset($_GET['address'])) {$address=stripcslashes($_GET['address']); }
if(isset($_GET['lat'])) {$latitude=stripcslashes($_GET['lat']); }
if(isset($_GET['long'])) {$longitude=stripcslashes($_GET['long']); }
if(isset($_GET['debug'])) {$debug=stripcslashes($_GET['debug']); }

//sanitize some initial inputs
if ($hebyear){
	if (preg_match('/^[0-9]{4}$/', $hebyear)) {
	} else {
    	echo("<H2>not a valid Hebrew year</h2>\n");
    	exit(1);
	}
} else {
	//get year
	$zmanurl = "https://www.hebcal.com/converter?cfg=json";
	$get_zmanim = callAPI('GET', $zmanurl, false);
	$zmanresponse = json_decode($get_zmanim, true);
	$hebyear = $zmanresponse['hy'];
}
if ($zipcode){
	if (preg_match('/^[0-9]{5}$/', $zipcode)) {
	} else {
    	echo("<H2>not a valid 5 digit zip code</h2>\n");
    	exit(1);
	}
}
if ($address) {
	$address = htmlspecialchars($address);
   $address = stripslashes($address);
   $address = trim($address);
}
if ($latitude){
	if ($latitude >= -90 && $latitude <=-90) {
	} else {
    	echo("<H2>Not a valid latitude coordinate</h2>\n");
    	exit(1);
	}
}
if ($longitude){
	if ($longitude >= -180 && $longitude <=-180) {
	} else {
    	echo("<H2>Not a valid longitude coordinate</h2>\n");
    	exit(1);
	}
}
if ($debug ==1 || $debug == 0) {
} else {
    echo("<H2>Debug must be 0 or 1</h2>\n");
    exit(1);
}


//set location
if ($zipcode != "") {
	$zipurl = "http://api.geonames.org/postalCodeSearchJSON?postalcode=$zipcode&country=US&username=$tzusername";
	$get_zipinfo = callAPI('GET', $zipurl, false);
	$zipresponse = json_decode($get_zipinfo, true);
	$latitude = $zipresponse['postalCodes']['0']['lat'];
	$longitude = $zipresponse['postalCodes']['0']['lng'];
	$tzurl = "http://api.geonames.org/timezoneJSON?lat=$latitude&lng=$longitude&username=$tzusername";
	$get_tzname = callAPI('GET', $tzurl, false);
	$tzresponse = json_decode($get_tzname, true);
	$tzid = $tzresponse['timezoneId'];
	$geostring = "geo=pos&latitude=$latitude&longitude=$longitude&tzid=$tzid";
	$locstring = "Lat: $latitude, Long $longitude, Timezone $tzid";
} elseif ($address != "") {
	$addurlencoded = urlencode($address);
	$addurl = "http://api.geonames.org/geoCodeAddressJSON?q=\"$addurlencoded\"&username=$tzusername";
	$get_addinfo = callAPI('GET', $addurl, false);
	$addresponse = json_decode($get_addinfo, true);
	$latitude = $addresponse['address']['lat'];
	$longitude = $addresponse['address']['lng'];
	$tzurl = "http://api.geonames.org/timezoneJSON?lat=$latitude&lng=$longitude&username=$tzusername";
	$get_tzname = callAPI('GET', $tzurl, false);
	$tzresponse = json_decode($get_tzname, true);
	$tzid = $tzresponse['timezoneId'];
	$geostring = "geo=pos&latitude=$latitude&longitude=$longitude&tzid=$tzid";
	$locstring = "Lat: $latitude, Long $longitude, Timezone $tzid";
} elseif ($latitude  != "" && $longitude != "") {
	$tzurl = "http://api.geonames.org/timezoneJSON?lat=$latitude&lng=$longitude&username=$tzusername";
	$get_tzname = callAPI('GET', $tzurl, false);
	$tzresponse = json_decode($get_tzname, true);
	$tzid = $tzresponse['timezoneId'];
	$geostring = "geo=pos&latitude=$latitude&longitude=$longitude&tzid=$tzid";
	$locstring = "Lat: $latitude, Long $longitude, Timezone $tzid";
} else {
	# kollel $latitude = "41.4902062";
	# kollel $longitude = "-81.517477";
	$latitude = "41.4939407";
	$longitude = "-81.516709";
	$tzid = "America/New_York";
	$geostring = "geo=pos&latitude=$latitude&longitude=$longitude&tzid=$tzid";
	$locstring = "BKCS Building";
}

//get this year RH, Sukkot, Pesach, 
//set RH to startdate
$zmanurl = "https://www.hebcal.com/converter?cfg=json&hy=$hebyear&hm=Tishrei&hd=01&h2g=1";
$get_zmanim = callAPI('GET', $zmanurl, false);
$zmanresponse = json_decode($get_zmanim, true);
$startdate = date('Y-m-d', mktime(0,0,0,$zmanresponse['gm'],$zmanresponse['gd'],$zmanresponse['gy']));

//get date of 15 Tishrei (Sukkot)
$zmanurl = "https://www.hebcal.com/converter?cfg=json&hy=$hebyear&hm=Tishrei&hd=15&h2g=1";
$get_zmanim = callAPI('GET', $zmanurl, false);
$zmanresponse = json_decode($get_zmanim, true);
$SukkotDate = date('Y-m-d', mktime(0,0,0,$zmanresponse['gm'],$zmanresponse['gd'],$zmanresponse['gy']));

//get date of 15 Nissan (Passover)
$zmanurl = "https://www.hebcal.com/converter?cfg=json&hy=$hebyear&hm=Nisan&hd=15&h2g=1";
$get_zmanim = callAPI('GET', $zmanurl, false);
$zmanresponse = json_decode($get_zmanim, true);
$PesachDate = date('Y-m-d', mktime(0,0,0,$zmanresponse['gm'],$zmanresponse['gd'],$zmanresponse['gy']));

//get next year RH, set it to enddate
$nexthebyear = ++$hebyear; 
$zmanurl = "https://www.hebcal.com/converter?cfg=json&hy=$nexthebyear&hm=Tishrei&hd=01&h2g=1";
$get_zmanim = callAPI('GET', $zmanurl, false);
$zmanresponse = json_decode($get_zmanim, true);
$enddate = date('Y-m-d', mktime(0,0,0,$zmanresponse['gm'],$zmanresponse['gd'],$zmanresponse['gy']));


// Get list of shabbatot
$shabbatsurl = "https://www.hebcal.com/hebcal?v=1&cfg=json&maj=on&min=off&nx=off&mf=off&ss=off&s=on&c=off&i=off&leyning=off&$geostring&start=$startdate&end=$enddate";
$get_shabbats = callAPI('GET', $shabbatsurl, false);
$shabbatresponse = json_decode($get_shabbats, true);
$gotit = 3;

foreach($shabbatresponse['items'] as $shabbatitem) {
	$shabbatdate = date('Y-m-d', strtotime($shabbatitem['date']));
	if (date('w', strtotime($shabbatdate))!=6) {continue;}
	$saturday = $shabbatdate;
	$friday= date('Y-m-d', strtotime( $saturday . " -1 days"));
	$category = $shabbatitem['category'];
	if($category == "parashat" || $category == "holiday") {
		$englishparashat = $shabbatitem['title'];
		$hebrewparashat = $shabbatitem['hebrew'];
		} #end if parashat or holiday
	if($category == "candles") {
		$englishparashat = $shabbatitem['memo'];
		$hebrewparashat = $shabbatitem['hebrew'];
		} #end if candles
	
// check associative array if $saturday is already there and has a category of "parashat"
	if (array_key_exists($shabbatdate, $datearray) && $datearray[$shabbatdate]['category'] == "parashat") {
		//echo "entry $shabbatdate exists and category is parashat <br>";
		continue;
	} elseif(array_key_exists($shabbatdate, $datearray) && $datearray[$shabbatdate]['category'] == "holiday")  {
		continue;
	} else {
		//set timezone offset
		$tz = new DateTimeZone($tzid);
		$datetime = date_create($friday, new DateTimeZone("GMT"));
		$tzoff = timezone_offset_get($tz, $datetime );
		$offset = $tzoff/3600;

		$timeinfo = getzmanim($friday, $latitude, $longitude, $geostring, $tzid, $SukkotDate, $PesachDate, $offset);
		$candletext = $timeinfo[0];
		$frisunset = $timeinfo[1];
		$frimincha = $timeinfo[2];
		$satmincha = $timeinfo[3];
		$satsunset = $timeinfo[4];
		$satarvit = $timeinfo[5];
		$sattzet = $timeinfo[6];
		$latemotzei = $timeinfo[7];

		$datearray[$shabbatdate] = array(
			"friday" => $friday,
			"saturday" => $saturday,
			"englishparashat" => $englishparashat,
			"hebrewparashat" => $hebrewparashat,
			"category" => $category,
			"candletext" => $candletext,
			"frisunset" => $frisunset,
			"frimincha" => $frimincha,
			"satmincha" => $satmincha,
			"satsunset" => $satsunset,
			"satarvit" => $satarvit,
			"sattzet" => $sattzet,
			"latemotzei" => $latemotzei
		);
	}	
}

if($debug == 1) {
	echo "shabbatsurl: $shabbatsurl <br>";
	echo "offset is $offset<br>";
	echo "timezone is $tzid<br>";

}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Sephardic Congregation of Cleveland Zmanim</title>
</head>
<body>
<img src=\"header.png\" width=\"1100\">
<table border=1><tr><td>Saturday</td><td>Parsha</td><td>Hebrew</td><td>Candles<br>early / zman</td><td>Fri<br>Shkia</td><td>Fri Mincha<br>korb / ashrei</td><td>Sat<br>Mincha</td><td>Sat<br>Shkia</td><td>Sat<br>Arvit</td><td>Motzei<br>45min / 72min</td></tr>";

foreach($datearray as $shabbatdate => $shabbatvalue) {
	$zmanstring ="<tr><td>" . $shabbatdate . "</td><td>" . $shabbatvalue['englishparashat'] . "</td><td style=\"text-align:right\">" . $shabbatvalue['hebrewparashat'] . "</td><td>" . $shabbatvalue['candletext'] . "</td><td>" . $shabbatvalue['frisunset'] . "</td><td>" . $shabbatvalue['frimincha'] . "</td><td>" . $shabbatvalue['satmincha'] . "</td><td>" . $shabbatvalue['satsunset'] . "</td><td>" . $shabbatvalue['satarvit'] . "</td><td>" . $shabbatvalue['sattzet'] . "/" . $shabbatvalue['latemotzei'] . "</td></tr>";
	print_r($zmanstring);
	}


echo "</table>
<P>NOTE: Times are calculated automatically based on the location informatin provided. Because zip codes can cover a large area; and because of variations in things like the source of sunrise/sunset, height of elevation, rounding seconds to minutes, etc. times may be off by as much as 2 minutes. Please plan accordingly.</P>
</body>
</html>";

function getzmanim($friday, $latitude, $longitude, $geostring, $tzid ,$SukkotDate, $PesachDate, $offset){
	$frisunrise = date_sunrise(strtotime($friday),SUNFUNCS_RET_STRING,$latitude,$longitude,90.83,$offset);
	$frisunrise = date('g:ia', strtotime($frisunrise));
	$frisunset = date_sunset(strtotime($friday),SUNFUNCS_RET_STRING,$latitude,$longitude,90.83,$offset);
	$frisunset = date('g:ia', strtotime($frisunset));
	$saturday= date('Y-m-d', strtotime( $friday . " +1 days"));
	$satsunrise = date_sunrise(strtotime($saturday),SUNFUNCS_RET_STRING,$latitude,$longitude,90.83,$offset);
	$satsunrise = date('g:ia', strtotime($satsunrise));
	$satsunset = date_sunset(strtotime($saturday),SUNFUNCS_RET_STRING,$latitude,$longitude,90.83,$offset);
	$satsunset = date('g:ia', strtotime($satsunset));

	//FIXED TIMES
	$friyr = date('Y',strtotime($friday));
	$frimo = date('m',strtotime($friday));
	$frid = date('d',strtotime($friday));

	// OLD: is this Friday after Sukkot and before Pesach? If so, $isearly==0
	//if( $friday > $SukkotDate && $friday < $PesachDate) {
	// is shkia later than 7:35? Then mincha is early. otherwise it's at zman ()
	if(strtotime($frisunset) <= strtotime("7:35pm")) {
		$isearly=0;
	} else {
		$isearly=1;
	}

	//SIMPLE CALCULATIONS
	// Shabbat candles = fri shkia - 18
		$candles = date('g:ia', strtotime( $frisunset . " -18 minutes"));
	// tzet hakochavim = shkia + 45
	// early Motzi Shabbat is the same as tzet
		$fritzet = date('g:ia', strtotime( $frisunset . " +45 minutes"));
		$sattzet = date('g:ia', strtotime( $satsunset . " +45 minutes"));
	// Late Motzi Shabbat Shkia+72 
		$latemotzei = date('g:ia', strtotime( $satsunset . " +72 minutes"));
	// Saturday Mincha = Shkia-40 minutes 
		$satmincha = date('g:ia', strtotime( $satsunset . " -40 minutes"));
	// Saturday Arvit = Shkia+50 minutes
		$satarvit = date('g:ia', strtotime( $satsunset . " +50 minutes"));
	// Alot Hashachar ("alot") = netz-((shkia-netz)/10)
		$frialot = date('g:ia', strtotime($frisunrise)-((strtotime($frisunset) - strtotime($frisunrise))/10));
		$satalot = date('g:ia', strtotime($satsunrise)-((strtotime($satsunset) - strtotime($satsunrise))/10));
	// Sha'a (halachic hour) = (tzait - Alot) / 12 
		$frishaa = (strtotime($fritzet)-strtotime($frialot))/12;
		$satshaa = (strtotime($sattzet)-strtotime($satalot))/12;

	//COMPOUND CALCULATIONS
	// Mincha Gedola = 6.5 sha’a after ‘alot 
		$friminchged = date('g:ia', strtotime($frialot)+(((strtotime($fritzet)-strtotime($frialot))/12)*6.5));
		$satminchged = date('g:ia', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*6.5));
	// Mincha ketana = 9.5 sha’a after ‘alot 
		$friminchkat = date('g:ia', strtotime($frialot)+(((strtotime($fritzet)-strtotime($frialot))/12)*9.5));
		$satminchkat = date('g:ia', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*9.5));
	// Sof zman kria shema (latest time for shema in the morning = Alot + (sha'a * 3)
		$satshema = date('g:ia', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*3));
	// Plag Hamincha ("plag") = mincha ketana+((tzet - mincha ketana) / 2)
		$friplag = date('g:ia', strtotime($friminchkat)+(((strtotime($fritzet))-strtotime($friminchkat))/2));
		$satplag = date('g:ia', strtotime($satminchkat)+(((strtotime($sattzet))-strtotime($satminchkat))/2));
	// "winter" mincha = Shkia-20 
		if ($isearly == 0) { 
			$candletext = "$candles";
			$frimincha = date('g:ia', strtotime( $frisunset . " -20 minutes"));
			$friminchakorb = date('g:ia', strtotime( $frisunset . " -23 minutes"));
			$friminchaashrei = date('g:ia', strtotime( $frisunset . " -18 minutes"));
			$friminchatext = $friminchakorb . "/" . $friminchaashrei;
		} else {
			$candletext = $friplag. "/" . $candles;
			$frimincha = date('g:ia', strtotime( $friplag . " -20 minutes"));
			$friminchatext = date('g:ia', strtotime( $friplag . " -20 minutes"));
		}


//$stuff = "$candles, $frisunset, $friminchatext, $satmincha, $satsunset, $satarvit, $sattzet, $latemotzei<br>";
return [$candletext, $frisunset, $friminchatext, $satmincha, $satsunset, $satarvit, $sattzet, $latemotzei];

}

function callAPI($method, $url, $data){
   $curl = curl_init();
   switch ($method){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "PUT":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
         break;
      default:
         if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
   // OPTIONS:
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'APIKEY: 111111111111111111111',
      'Content-Type: application/json',
   ));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   // EXECUTE:
   $result = curl_exec($curl);
   if(!$result){die("Connection Failure");}
   curl_close($curl);
   return $result;
}

?>