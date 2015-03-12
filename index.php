<?php
#The user is redirected here from login.php
session_start();

#if there is no session set, redirect to loginIndex.php

if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT'] ) )) {
	
	#need the functions to create an absolute URL:
	require_once ('loginFunctions.php');
	$url = absoluteUrl();
	header("Location: $url");
	exit();#exit the script
}

#Set the page title and include the html header:
$PageTitle = "Hello {$_SESSION['firstName']}!";

include_once ('header.php');
include('masterVariables.php');

# Code goes here! 
?> 
<fieldset>
	<form action = "http://davidbrownhk.com/library/login.php">
		
		<p><input type = "submit"
							class = "button orange"
							name = "submit"
							value = "Login page" /></p>
		
	</form>
</fieldset>

<?php
include_once 'footer.html';
?>
