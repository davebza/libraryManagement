<?php
#do a session check, and bounce the person if they aren't logged in yet:
session_start();

if (!isset($_SESSION['agent']) OR ($_SESSION['agent'] != md5($_SERVER['HTTP_USER_AGENT'] ) )){

	#need the functions to create an absolute URL:
	require_once ('loginFunctions.php');
	$url = absoluteUrl();
	header("Location: $url");
	exit();#exit the script
}

#include master variables, set the pagetitle variable and include the header file
include_once 'masterVariables.php';
include 'mysqliConnectHomeReadingBooksDB.php';

$PageTitle = "Home Reading Book Borrowing";
include('header.php');

if(substr($_SESSION['class'], 1,2)!= "T"){
	die("You are not authorized to view this page!");
}

#Start of the return section of the page:


#Check if manualInput is set through post, in order to see if we need to check which copy of a mulitple copy book series we're returning:
if (isset($_POST[manualInput])){
	
	$bookSchoolCode = $_POST[bookSchoolCode];
	$bookISBN = $_POST[bookISBN];
	
	#get the bookKey from the homeReadingBookInventory table, this will be used to return it further on down the script:
	
	$dbq = "SELECT  `bookKey`
	FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
	WHERE  `bookSchoolCode` ='$bookSchoolCode' && `bookISBN` = '$bookISBN'
	LIMIT 1";
	
	$dbr = mysqli_query($dbc, $dbq)or die("Selection of single copy bookKey from bookSchoolCode failed. bookReturn.php line 41");
	
	$keyFetch = $dbr->fetch_array(MYSQLI_NUM);
	$bookKey = $keyFetch[0];
	echo $bookKey. " ";
	
}elseif(!isset($_POST[manualInput])){
	
	#paste code here!
	
	$bookISBN = $_POST['bookISBN'];
	
	#Check if there are multiple copies of this book:
	
	$dbq = "SELECT COUNT( * )
	FROM `daveb_homeReadingBookRecords`. `homeReadingBooksInventory`
	WHERE  `bookISBN` ='$bookISBN'";
	
	$dbr = mysqli_query($dbc, $dbq)or die("Count of bookISBN failed. bookReturn.php");
	
	$countFetch = $dbr->fetch_array(MYSQLI_NUM);
	$count = $countFetch[0];
	
	#if there is a single copy, return it:
	
	if ($countFetch[0]=='1'){
		echo "a single copy of this book exists. Proceeding get the bookKey set up";
	
		#get the bookKey from the homeReadingBookInventory table:
	
		$dbq = "SELECT  `bookKey`
		FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
		WHERE  `bookISBN` ='$bookISBN'
		LIMIT 1";
	
		$dbr = mysqli_query($dbc, $dbq)or die("Selection of single copy bookKey from bookISBN failed. bookReturn.php line 43");
	
		$keyFetch = $dbr->fetch_array(MYSQLI_NUM);
		$bookKey = $keyFetch[0];
		echo $bookKey;
	
	}elseif ($countFetch[0] > 1){
	
	$numberOfCopies = $countFetch[0];
	
	echo "$numberOfCopies copies of this book exist!";
	#Let the user choose which copy of the book we are using:
	
	#we're going to have to get the different bookKeys and bookSchoolCodes for each copy, then put them into a radio button choice. The code for this goes in the manualInput loop:
	
	#First, get the school codes for this book:
	
	$dbq = "SELECT  `bookSchoolCode`
	FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
	WHERE  `bookISBN` = '$bookISBN' ORDER BY `bookSchoolCode` ASC LIMIT $numberOfCopies ";
	
	$dbr = mysqli_query($dbc, $dbq)or die("Selection of bookSchoolCode for multi copies failed. bookReturn.php line 93");
	
			while($bookSchoolCodeFetch = $dbr->fetch_array(MYSQLI_NUM)){
			$bookSchoolCodeArray[] = $bookSchoolCodeFetch[0];
	}
	
	/*echo "Here's the array: ";
	print_r($bookSchoolCodeArray);
	
	foreach($bookSchoolCodeArray as $value){
	echo $value;
	}*/
	
	?>
		<fieldset>
			<form action= "bookReturn.php"
			method = "post">
				
				<h1><?php echo $numberOfCopies?> copies of this book exist. Please input the school book code on your copy!</h1>
				
				<p>
					<?php foreach($bookSchoolCodeArray as $value){
						?><input type="radio" name="bookSchoolCode" value=<?php echo $value;?> /> <?php echo $value;?><br /><?php
						}
					?>
				</p>
				
				<p><input type = "submit"
								class = "button orange"
								name = "submit"
								value = "Return" /></p>
								
				<input type = "hidden"
							name = "manualInput"
							value = "TRUE" />
							
				<input type = "hidden"
								name = "bookISBN"
								value = "<?php echo $bookISBN; ?>" />
				
			</form>
		</fieldset>
		
		<?php 
		
		include "footer.html";
		die;
	
	}


}#end of the Single / multiple copy check and bookKey get conditional

#Check the book's status according to the homeReadingBooksInventory table:

$dbq = "SELECT  `bookStatus` 
FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory` 
WHERE bookKey =$bookKey LIMIT 1";
$dbr = mysqli_query($dbc, $dbq)or die("Book Status column could not be checked. bookReturn.php line 33");

$statusCheck = $dbr->fetch_array(MYSQLI_NUM);
#If the book is not recorded as being checked out, end the script:
if ($statusCheck[0]=='In'){
	echo "This book was not checked out and so cannot be returned";
	include 'bookReturnForm.php';
	include 'footer.html';
	die;
}

$dbq = "SELECT `studentId`, `lastIn`
FROM `daveb_homeReadingBookRecords`.`$bookKey`
ORDER BY numberOfOuts DESC
LIMIT 1";

$dbr = mysqli_query($dbc, $dbq) or die ("Couldn't connect to the book table in the database to check if it's actually out or not. This probably means this book has not been added to the database. bookReturn.php line 139");

$row = $dbr->fetch_array(MYSQLI_NUM);

if (!is_null($row[1]>0)) {
	#first return the book in the book table
	echo "Book was checked out by student ".$row[0].". Proceeding to update the book record. ";
	$dbq = "UPDATE  `daveb_homeReadingBookRecords`.`$bookKey` SET  `lastIn` = NOW( ) ORDER BY  `numberOfOuts` DESC LIMIT 1";
	$dbr = $dbr = mysqli_query($dbc, $dbq) or die ("Couldn't return the book in the book table. bookReturn.php");
	echo "The book is now returned in the book table. ";
	#then update the book status column in the homeReadingBooksInventory table:
	
	#Update the book status column in the homeReadingBooksInventory table
	$dbq = "UPDATE  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory` SET  `bookStatus` =  'In' WHERE  `homeReadingBooksInventory`.`bookKey` =$bookKey";
	$dbr = mysqli_query($dbc, $dbq)or die("Book Status column not updated. bookReturn line 153");
	
	#Update the student's home reading record:
	echo "Proceeding to update the student's Home Reading Record. ";
	include 'mysqliConnectStudentHomeReading.php';
	$dbq = "UPDATE `daveb_studentHomeReading`.`$row[0]` SET `returned` = NOW( ) ORDER BY `numberOfTimes` DESC LIMIT 1";
	$dbr = mysqli_query($dbc, $dbq) or die ("Couldn't return the book in the student's table. bookReturn.php");
	echo "Book is now returned in the student table. ";

	#update the lastHRReturned column in thew student's Alltable:
	#echo $row[0];
	$gradeDB = "daveb_P".substr($row[0], 0,1);
	$borrowingClass = substr($row[0], 0, 2);
	#echo $borrowingClass;
	$studentClassNumber = substr($row[0], 2);
	#echo $studentClassNumber;
	
		if ($gradeDB == 'daveb_P2') {
	
			include 'mysqliConnectP2.php';
			$allTable = "2all";
	
		}elseif ($gradeDB== 'daveb_P4'){
	
			include 'mysqliConnectP4.php';
			$allTable = "4all";
	
		}elseif ($gradeDB== 'daveb_P5'){
	
			include 'mysqliConnectP5.php';
			$allTable = "5all";
	
		}
		
		$dbq = "UPDATE `$gradeDB`.`$allTable` SET `lastHRReturned` = 'Yes' WHERE  `$allTable`.`class` = '$borrowingClass' && `$allTable`.`classNumber` = $studentClassNumber";
		$dbr = mysqli_query($dbc, $dbq) or die("Couldn't update the students master table column: returned");
	
} else {
	
	echo "This book was not checked out, so cannot be returned.";

}

include 'bookReturnForm.php';

include 'footer.html';
?>

