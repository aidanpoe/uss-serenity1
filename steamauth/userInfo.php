<?php
if (empty($_SESSION['steam_uptodate']) or empty($_SESSION['steam_personaname'])) {
	require 'SteamConfig.php';
	
	// Use cURL instead of file_get_contents for better performance and error handling
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".$steamauth['apikey']."&steamids=".$_SESSION['steamid']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3 second connection timeout
	$url = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	// Only process if we got a successful response
	if ($http_code === 200 && $url !== false) {
		$content = json_decode($url, true);
		$_SESSION['steam_steamid'] = $content['response']['players'][0]['steamid'];
		$_SESSION['steam_communityvisibilitystate'] = $content['response']['players'][0]['communityvisibilitystate'];
		$_SESSION['steam_profilestate'] = $content['response']['players'][0]['profilestate'];
		$_SESSION['steam_personaname'] = $content['response']['players'][0]['personaname'];
		$_SESSION['steam_lastlogoff'] = $content['response']['players'][0]['lastlogoff'];
		$_SESSION['steam_profileurl'] = $content['response']['players'][0]['profileurl'];
		$_SESSION['steam_avatar'] = $content['response']['players'][0]['avatar'];
		$_SESSION['steam_avatarmedium'] = $content['response']['players'][0]['avatarmedium'];
		$_SESSION['steam_avatarfull'] = $content['response']['players'][0]['avatarfull'];
		$_SESSION['steam_personastate'] = $content['response']['players'][0]['personastate'];
		if (isset($content['response']['players'][0]['realname'])) { 
			   $_SESSION['steam_realname'] = $content['response']['players'][0]['realname'];
		   } else {
			   $_SESSION['steam_realname'] = "Real name not given";
		}
		$_SESSION['steam_primaryclanid'] = $content['response']['players'][0]['primaryclanid'];
		$_SESSION['steam_timecreated'] = $content['response']['players'][0]['timecreated'];
		$_SESSION['steam_uptodate'] = time();
	}
}

$steamprofile['steamid'] = $_SESSION['steam_steamid'];
$steamprofile['communityvisibilitystate'] = $_SESSION['steam_communityvisibilitystate'];
$steamprofile['profilestate'] = $_SESSION['steam_profilestate'];
$steamprofile['personaname'] = $_SESSION['steam_personaname'];
$steamprofile['lastlogoff'] = $_SESSION['steam_lastlogoff'];
$steamprofile['profileurl'] = $_SESSION['steam_profileurl'];
$steamprofile['avatar'] = $_SESSION['steam_avatar'];
$steamprofile['avatarmedium'] = $_SESSION['steam_avatarmedium'];
$steamprofile['avatarfull'] = $_SESSION['steam_avatarfull'];
$steamprofile['personastate'] = $_SESSION['steam_personastate'];
$steamprofile['realname'] = $_SESSION['steam_realname'];
$steamprofile['primaryclanid'] = $_SESSION['steam_primaryclanid'];
$steamprofile['timecreated'] = $_SESSION['steam_timecreated'];
$steamprofile['uptodate'] = $_SESSION['steam_uptodate'];

// Version 4.0
?>
