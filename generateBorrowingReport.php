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

#Check if the hidden submit is set in order to find which grade and class we are dealing with:
if(isset($_POST['hiddenSubmit'])){
	
	$gradeAndClass = $_POST['gradeAndClass'];
	$gradeCheck = substr($gradeAndClass, 0, 1);
	
	#Use the number to connect to the appropriate database, and set the correct allTable:
	if($gradeCheck == 2 ){
		
		$allTable = "2all";
		include 'mysqliConnectP2.php';
		
	}elseif ($gradeCheck == 4){
		
		$allTable = "4all";
		include 'mysqliConnectP4.php';
		
	}elseif ($gradeCheck == 5){
		
		$allTable = "5all";
		include 'mysqliConnectP5.php';
	}
	
	generateNonReturnedBooks($dbc, $allTable, $gradeAndClass);
	
}#end of the actual display conditional

#The form for choosing the class is below:
?>
<h1>Choose the class to view</h1>

<fieldset>
<form action= "generateBorrowingReport.php"
method = "post">

<table border="3">
	<tr>
	
	<td><p>P2 Classes:<br/>
				<input type="radio" name="gradeAndClass" value="2A" /> P2A</input><br />
				<input type="radio" name="gradeAndClass" value="2B" /> P2B</input><br />
				<input type="radio" name="gradeAndClass" value="2C" /> P2C</input><br />
				<input type="radio" name="gradeAndClass" value="2D" /> P2D</input><br />
			</p>
	</td>
	
	<td><p>P4 Classes:<br/>
				<input type="radio" name="gradeAndClass" value="4A" /> P4A</input><br />
				<input type="radio" name="gradeAndClass" value="4B" /> P4B</input><br />
				<input type="radio" name="gradeAndClass" value="4C" /> P4C</input><br />
				<input type="radio" name="gradeAndClass" value="4D" /> P4D</input><br />
			</p>
	</td>
	
	<td><p>P5 Classes:<br/>
			<input type="radio" name="gradeAndClass" value="5A" /> P5A</input><br />
			<input type="radio" name="gradeAndClass" value="5B" /> P5B</input><br />
			<input type="radio" name="gradeAndClass" value="5C" /> P5C</input><br />
			<input type="radio" name="gradeAndClass" value="5D" /> P5D</input><br />
		</p>
	</td>
	</tr>
</table>
	
<p><input type = "submit"
class = "button orange"
name = "submit"
value = "Make Report" /></p>
	
<input type = "hidden"
name = "hiddenSubmit"
value = "TRUE" />

</form>
</fieldset>
<?php 

include_once 'footer.html';
?>