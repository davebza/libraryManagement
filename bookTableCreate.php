<?php
include 'masterVariables.php';
include 'mysqliConnectHomeReadingBooksDB.php';

for ($bookTableNumber = 32; $bookTableNumber < 752; $bookTableNumber ++){
	
	echo $bookTableNumber;
	$dbq = "CREATE TABLE IF NOT EXISTS `$bookTableNumber` LIKE `booktabletemplate`";
	echo $dbq;
	$dbr = mysqli_query($dbc, $dbq) or die("Table creation for the books did not go as planned");
	
	if($dbr){
		
		echo "book table ".$bookTableNumber. "was created sucessfully < br>/n";
	}
	
}

