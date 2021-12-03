<?php
//this the username for http://www.geonames.org/, used to locate time zone based on lat/long information
$tzusername="";

/*
FUTURE TODO
=========
	create form front-end
	take data input from form
	sanitization, improved output
	declare all variables, other cleanup
	sanitize and validate inputs


INFORMAITON BLOCK
========================
FILENAME: fullyear_zmanim.php
NAME: Full year zmanim
AUTHOR: Leon Adato
VERSION HISTORY
	0.0.1 - 0.0.10 - development
	0.1.0 - first pre-prod version

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

//initial variables
date_default_timezone_set('America/New_York');

//set variables
$candles = $candletext = $frisunset = $frimincha = $satmincha = $satsunset = $satarvit = $sattzet = $latemotzei = $SukkotDate = $PesachDate = "";
$hebyear = $nexthebyear = $startdate = $enddate = $friday = $saturday = "";
$zipcode = $city = $geoname = $latitude = $longitude = "";

//get location, year, and other common variables like you do with the weekly times
//get incoming variables
if(isset($_GET['hebyear'])) {$hebyear=stripcslashes($_GET['hebyear']);}
if(isset($_GET['zipcode'])) {$zipcode=stripcslashes($_GET['zipcode']); }
if(isset($_GET['city'])) {$city=stripcslashes($_GET['city']); }
if(isset($_GET['geoname'])) {$geoname=stripcslashes($_GET['geoname']); }
if(isset($_GET['lat'])) {$latitude=stripcslashes($_GET['lat']); }
if(isset($_GET['long'])) {$longitude=stripcslashes($_GET['long']); }

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
if ($geoname){
	if (preg_match('/^[0-9]{7}$/', $geoname)) {
	} else {
    	echo("<H2>not a valid 7 digit Geoname code</h2>\n");
    	exit(1);
	}
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

//set location
if ($zipcode) {
	$geostring="zip=$zipcode";
	$locstring = "Zipcode $zipcode";
}elseif ($geoname) {
	$geostring="geo=geoname&geonameid=$geoname";
	$locstring = "Geoname ID $geoname";
} elseif ($city) {
	$geostring="geo=city&city=$city";
	$locstring = "City $city";
} elseif ($latitude && $longitude ) {
	$tzurl = "http://api.geonames.org/timezoneJSON?lat=$latitude&lng=$longitude&username=$tzusername";
	$get_tzname = callAPI('GET', $tzurl, false);
	$tzresponse = json_decode($get_tzname, true);
	$tzid = $tzresponse['timezoneId'];
	$geostring = "geo=pos&latitude=$latitude&longitude=$longitude&tzid=$tzid";
	$locstring = "Lat: $latitude, Long $longitude, Timezone $tzid";
} else {
	$geostring = "geo=pos&latitude=41.4902062&longitude=-81.517477&tzid=America/New_York";
	$locstring = "Cleveland Sephardic Minyan Kollel Building";
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
echo "shabbatsurl: $shabbatsurl <br>";
$get_shabbats = callAPI('GET', $shabbatsurl, false);
$shabbatresponse = json_decode($get_shabbats, true);

echo "<img src=\"header.png\" width=\"1100\"><br>";
echo "<table border=1><tr><td>Saturday</td><td>Parsha</td><td>Hebrew</td><td>Candles</td><td>Fri Shkia</td><td>Fri Mincha</td><td>Sat Mincha</td><td>Sat Shkia</td><td>Sat Arvit</td><td>Motzei 45/72</td></tr>";

foreach($shabbatresponse['items'] as $shabbatitem) {
	$shabbatdate = $shabbatitem['date'];
	if (date('w', strtotime($shabbatdate))!=6) {continue;}
	$saturday = $shabbatdate;
	$friday= date('Y-m-d', strtotime( $saturday . " -1 days"));
	$englishparashat = $shabbatitem['title'];
	$hebrewparashat = $shabbatitem['hebrew'];

	if ($englishparashat == "") {
		foreach($shabbatresponse['items'] as $shabbatitem) {
		if (date('Y-m-d', strtotime($shabbatitem['date'])) == $saturday) {
			if ($shabbatitem['category'] == "candles") {
				$englishparashat = $shabbatitem['memo'];
				}
			}
		}
	
	}

	$timeinfo = getzmanim($friday, $geostring, $SukkotDate, $PesachDate);
	$candletext = $timeinfo[0];
	$frisunset = $timeinfo[1];
	$frimincha = $timeinfo[2];
	$satmincha = $timeinfo[3];
	$satsunset = $timeinfo[4];
	$satarvit = $timeinfo[5];
	$sattzet = $timeinfo[6];
	$latemotzei = $timeinfo[7];
	
$zmanstring ="<tr><td>" . $shabbatdate . "</td><td>" . $englishparashat . "</td><td style=\"text-align:right\">" . $hebrewparashat . "</td><td>" . $candletext . "</td><td>" . $frisunset . "</td><td>" . $frimincha . "</td><td>" . $satmincha . "</td><td>" . $satsunset . "</td><td>" . $satarvit . "</td><td>" . $sattzet . "/" . $latemotzei . "</td></tr>";
print_r($zmanstring);
	}

echo "</table>";

function getzmanim($friday, $geostring, $SukkotDate, $PesachDate){
	$friurl = "https://www.hebcal.com/zmanim?cfg=json&$geostring&date=$friday";
	$get_fritimes = callAPI('GET', $friurl, false);
	$friresponse = json_decode($get_fritimes, true);
	
	$saturday= date('Y-m-d', strtotime( $friday . " +1 days"));
	$saturl = "https://www.hebcal.com/zmanim?cfg=json&$geostring&date=$saturday";
	$get_sattimes = callAPI('GET', $saturl, false);
	$satresponse = json_decode($get_sattimes, true);

	//FIXED TIMES
	$frisunrise = date('g:i a', strtotime($friresponse['times']['sunrise']));
	$frisunset = date('g:i a', strtotime($friresponse['times']['sunset']));
	$satsunrise = date('g:i a', strtotime($satresponse['times']['sunrise']));
	$satsunset = date('g:i a', strtotime($satresponse['times']['sunset']));
	$friyr = date('Y',strtotime($friday));
	$frimo = date('m',strtotime($friday));
	$frid = date('d',strtotime($friday));

// is this Friday after Sukkot and before Pesach? If so, $isearly==0
	if( $friday > $SukkotDate && $friday < $PesachDate) {
		$isearly=0;
	} else {
		$isearly=1;
	}

	//SIMPLE CALCULATIONS
	// Shabbat candles = fri shkia - 18
		$candles = date('g:i a', strtotime( $frisunset . " -18 minutes"));
	// tzet hakochavim = shkia + 45
	// early Motzi Shabbat is the same as tzet
		$fritzet = date('g:i a', strtotime( $frisunset . " +45 minutes"));
		$sattzet = date('g:i a', strtotime( $satsunset . " +45 minutes"));
	// Late Motzi Shabbat Shkia+72 
		$latemotzei = date('g:i a', strtotime( $satsunset . " +72 minutes"));
	// Saturday Mincha = Shkia-40 minutes 
		$satmincha = date('g:i a', strtotime( $satsunset . " -40 minutes"));
	// Saturday Arvit = Shkia+50 minutes
		$satarvit = date('g:i a', strtotime( $satsunset . " +50 minutes"));
	// Alot Hashachar ("alot") = netz-((shkia-netz)/10)
		$frialot = date('g:i a', strtotime($frisunrise)-((strtotime($frisunset) - strtotime($frisunrise))/10));
		$satalot = date('g:i a', strtotime($satsunrise)-((strtotime($satsunset) - strtotime($satsunrise))/10));
	// Sha'a (halachic hour) = (tzait - Alot) / 12 
		$frishaa = (strtotime($fritzet)-strtotime($frialot))/12;
		$satshaa = (strtotime($sattzet)-strtotime($satalot))/12;

	//COMPOUND CALCULATIONS
	// Mincha Gedola = 6.5 sha’a after ‘alot 
		$friminchged = date('g:i a', strtotime($frialot)+(((strtotime($fritzet)-strtotime($frialot))/12)*6.5));
		$satminchged = date('g:i a', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*6.5));
	// Mincha ketana = 9.5 sha’a after ‘alot 
		$friminchkat = date('g:i a', strtotime($frialot)+(((strtotime($fritzet)-strtotime($frialot))/12)*9.5));
		$satminchkat = date('g:i a', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*9.5));
	// Sof zman kria shema (latest time for shema in the morning = Alot + (sha'a * 3)
		$satshema = date('g:i a', strtotime($satalot)+(((strtotime($sattzet)-strtotime($satalot))/12)*3));
	// Plag Hamincha ("plag") = mincha ketana+((tzet - mincha ketana) / 2)
		$friplag = date('g:i a', strtotime($friminchkat)+(((strtotime($fritzet))-strtotime($friminchkat))/2));
		$satplag = date('g:i a', strtotime($satminchkat)+(((strtotime($sattzet))-strtotime($satminchkat))/2));
	// "winter" mincha = Shkia-20 
		if ($isearly == 0) { 
			$candletext = "$candles";
			$frimincha = date('g:i a', strtotime( $frisunset . " -20 minutes"));
		} else {
			$candletext = "$friplag / $candles";
			$frimincha = date('g:i a', strtotime( $friplag . " -20 minutes"));
		}


//$stuff = "$candles, $frisunset, $frimincha, $satmincha, $satsunset, $satarvit, $sattzet, $latemotzei<br>";
return [$candletext, $frisunset, $frimincha, $satmincha, $satsunset, $satarvit, $sattzet, $latemotzei];

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
