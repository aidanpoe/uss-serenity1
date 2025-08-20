<?php
//Version 4.0 - Security Enhanced
// Load secure configuration
require_once '../includes/secure_config.php';

$steamauth['apikey'] = STEAM_API_KEY; // Now uses environment variable or secure config
$steamauth['domainname'] = STEAM_DOMAIN; // The main URL of your website displayed in the login page
$steamauth['logoutpage'] = "../index.php"; // Page to redirect to after a successfull logout
$steamauth['loginpage'] = "../index.php"; // Page to redirect to after a successfull login

// System stuff
if (empty($steamauth['apikey'])) {die("<div style='display: block; width: 100%; background-color: red; text-align: center;'>SteamAuth:<br>Please supply an API-Key!<br>Find this in steamauth/SteamConfig.php, Find the '<b>\$steamauth['apikey']</b>' Array. </div>");}
if (empty($steamauth['domainname'])) {$steamauth['domainname'] = $_SERVER['SERVER_NAME'];}
if (empty($steamauth['logoutpage'])) {$steamauth['logoutpage'] = $_SERVER['PHP_SELF'];}
if (empty($steamauth['loginpage'])) {$steamauth['loginpage'] = $_SERVER['PHP_SELF'];}
?>
