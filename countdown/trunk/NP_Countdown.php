<? 
/*
  History:
    v2.0 - initial release
    v2.1 - add supportsFeature
    v2.2 - add "since" function
*/ 
class NP_Countdown extends NucleusPlugin { 
function getEventList() { return array(); } 
function getName() { return 'Countdown'; } 
function getAuthor() { return 'Trent Adams | Edmond Hui (admun)'; } 
function getURL() { return 'http://www.trentadams.com/'; } 
function getVersion() { return '2.2'; } 
function getDescription() { 
return 'This plugin can be used to display days until a specified day using the parameters in the plugin.  Use is <%plugin(Countdown,My
2nd Anniversary!,3,23,2004)%> to show x days until (or since if the day is in the past) My 2nd Anniversary on March 23rd, 2004.'; 
} 

function supportsFeature($what) {
   switch($what) {
     case 'SqlTablePrefix':
       return 1;
     default:
       return 0;
   }
}

function doSkinVar($skinType) { 
global $manager, $blog, $CONF; 
$params = func_get_args(); 
$b =& $blog; 
if ($params[1]){ 
$theword = $params[1]; 
} 
else { $theword = "need countdown day,";} 

if ($params[2]){ 
$themonth = $params[2]; 
} 
else { $theword = "need month,";} 

if ($params[3]){ 
$theday = $params[3]; 
} 
else { $theword = "need day,";} 

if ($params[4]){ 
$theyear = $params[4]; 
} 
else { $theword = "need year,";} 

$time = ((mktime (0,0,0,$themonth,$theday,$theyear) - time(void))/86400); 

$when = "until";
if ($time < 0){
$when = "since";
}

echo '<b>', sprintf("%.0f",abs($time)), '</b> days ', $when, ' ', $theword;
} 

} 

?>
