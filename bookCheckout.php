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
$PageTitle = "Home Reading Book Borrowing";
include_once('header.php');

#COnnect to the book database for checking on existence and status:
include 'mysqliConnectHomeReadingBooksDB.php';

#if the user is not a teacher, end the script:
if(substr($_SESSION['class'], 1,2)!= "T"){
	die("You are not authorized to view this page!");
}
 
#Create short variables for the POST data for the borrowingClass, student and the book ID:
#echo "POST data follows:";
#print_r($_POST);

$borrowingClass = $_POST['borrowingClass'];
$studentClassNumber = $_POST['studentClassNumber'];
$studentToRecord = $_POST['borrowingClass'].$_POST['studentClassNumber'];
$bookISBN = $_POST['bookISBN'];
$gradeDB = "daveb_P".substr($borrowingClass, 0, 1);

#Check if manualInput is set through post, in order to see if we need to check which copy of a mulitple copy book series we're checking out:
if (isset($_POST[manualCheckoutInput])){

	$bookSchoolCode = $_POST[bookSchoolCode];

	#get the bookKey from the homeReadingBookInventory table:

	$dbq = "SELECT  `bookKey`
	FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
	WHERE  `bookSchoolCode` ='$bookSchoolCode' && `bookISBN` = '$bookISBN' LIMIT 1";

	$dbr = mysqli_query($dbc, $dbq)or die("Selection of single copy bookKey from bookSchoolCode failed. bookCheckout.php line 46");

	$keyFetch = $dbr->fetch_array(MYSQLI_NUM);
	$bookKey = $keyFetch[0];

}elseif(!isset($_POST['manualCheckoutInput'])){
	
	#paste code here!
	#Check if there are multiple copies of this book:
	
	$dbq = "SELECT COUNT( * )
	FROM `daveb_homeReadingBookRecords`. `homeReadingBooksInventory`
	WHERE  `bookISBN` ='$bookISBN'";
	
	$dbr = mysqli_query($dbc, $dbq)or die("Count of bookISBN failed. bookCheckout.php line 76");
	
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
	
		$dbr = mysqli_query($dbc, $dbq)or die("Selection of single copy bookKey from bookISBN failed. bookCheckout.php");
	
		$keyFetch = $dbr->fetch_array(MYSQLI_NUM);
		$bookKey = $keyFetch[0];
		echo $bookKey;
	
	}elseif ($countFetch[0] > 1){
	
		$numberOfCopies = $countFetch[0];
	
		echo "$numberOfCopies copies of this book exist!";
	
		#make radio button inputs for the multiple copies:
		#First, get the school codes for this book:
	
		$dbq = "SELECT  `bookSchoolCode`
		FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
		WHERE  `bookISBN` = '$bookISBN' ORDER BY `bookSchoolCode` ASC LIMIT $numberOfCopies ";
	
		$dbr = mysqli_query($dbc, $dbq)or die("Selection of bookSchoolCode for multi copies failed. bookReturn.php line 93");
	
		while($bookSchoolCodeFetch = $dbr->fetch_array(MYSQLI_NUM)){
	
			$bookSchoolCodeArray[] = $bookSchoolCodeFetch[0];
	
		}
	
		#Let the user choose which copy of the book we are using:
	
		#we're going to have to get the different bookKeys and bookSchoolCodes for each copy, then put them into a radio button choice:
	
		?>
			<h1><?php echo $numberOfCopies?> copies of this book exist. Please input the school book code on your copy!</h1>
				<fieldset>
					<form action= "bookCheckout.php" 
					method = "post">
				
					<p>School Book Code:
			<p>
					<?php foreach($bookSchoolCodeArray as $value){
						?><input type="radio" name="bookSchoolCode" value=<?php echo $value;?> /> <?php echo $value;?><br /><?php
						}
					?>
				</p>
			</p>
			
			<p><input type = "submit"
								class = "button orange"
								name = "submit"
								value = "Check Out" /></p>
								
				<input type = "hidden"
							name = "manualCheckoutInput"
							value = "TRUE" />
							
				<input type = "hidden"
							name = "borrowingClass"
							value = "<?php echo $borrowingClass;?>"/>
							
				<input type = "hidden"
							name = "studentClassNumber"
							value = "<?php echo $studentClassNumber; ?>" />
							
				<input type = "hidden"
							name = "bookISBN"
							value = "<?php echo $bookISBN; ?>" />
	
				</form>
		</fieldset>
		<?php
		
		include "footer.html";
		die;
	}#end of the Single / multiple copy check and bookKey get conditional
	
}



#Check that the book exists in the database - both in it's own table and in the Inventory table:
# First, in it's own table:

$dbq = "SELECT COUNT(`numberOfOuts`) FROM  `daveb_homeReadingBookRecords`.`$bookKey`";
$dbr = mysqli_query($dbc, $dbq); #or die("There was a problem with the book barcode. It probably doesn't exist in our database, so please check on this. In the meantime, let the student choose a differnet book.");

if (!$dbr) {
	
	echo "This book has not been properly acquisitioned: no table for the book exists on the database. Please check on this. In the meantime, let the student choose a different book.";
	include 'studentBorrowingForm.php';
	die;
}

#Check the book's status according to the homeReadingBooksInventory table:

$dbq = "SELECT  `bookStatus`
FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
WHERE bookKey =$bookKey LIMIT 1";
$dbr = mysqli_query($dbc, $dbq)or die("Book Status column could not be checked. bookCheckout.php line 53");

$statusCheck = $dbr->fetch_array(MYSQLI_NUM);
#If the book is not recorded as being checked out, end the script:
if ($statusCheck[0]=='Out'){
	echo "This book was not returned from its last checkout. Please return it first.";
	include 'bookReturnForm.php';	
	die;
}elseif ($statusCheck[0]=='In'){
	
	echo "Book was returned after last use. ";
}

#Get book title from database:

$dbq = "SELECT  `bookTitle`
FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
WHERE bookKey =$bookKey LIMIT 1";
$dbr = mysqli_query($dbc, $dbq)or die("Book Title column could not be checked. bookCheckout.php");

$bookTitleArray = $dbr->fetch_array(MYSQLI_NUM);
$bookTitle = mysqli_real_escape_string($dbc, $bookTitleArray[0]);
echo $bookTitle;

#include the connection file to the Home Reading book DB - this maybe needs to become a function and the calling page uses this as an includes:

#Check if the student has borrowed books before, if not make a table for them:
include 'mysqliConnectStudentHomeReading.php';
$dbq = "CREATE TABLE IF NOT EXISTS `daveb_studentHomeReading`.`$studentToRecord` (
																									`numberOfTimes` int(3) NOT NULL AUTO_INCREMENT,
																									`bookKey` int(10) NOT NULL,
																									`bookISBN` VARCHAR (13) NOT NULL,
																									`bookTitle` VARCHAR (40) NOT NULL,
																									`dateOut` date NOT NULL,
																									`returned` date DEFAULT NULL,
																									PRIMARY KEY (  `numberOfTimes` )
																						) ENGINE = MYISAM" ;
$dbr = mysqli_query($dbc, $dbq) or die("There is no table for this student in the Home Reading Database, and none could be created. BookCheckout.php line 190");

echo $studentToRecord."has a Home Reading Record Table. ";

#Count the number of entries in the student's borrowing record. 0 means that they have never borrowed before, and so can still take a book
$dbq = "SELECT COUNT( * ) FROM `daveb_studentHomeReading`.`$studentToRecord`";
$dbr = mysqli_query($dbc, $dbq) or die ("Couldn't connect to the student Home reading record table to check if they have ever borrowed books. bookCheckout.php line 94");
$checkIfEver =  $dbr->fetch_array(MYSQLI_NUM);
echo $studentToRecord." has borrowed books ". $checkIfEver[0]." times. ";
# If the number of entries is greater than 0, run a check to see if the student has returned their previous book. If 
if ($checkIfEver[0] > 0){
	
	$dbq = "SELECT `returned`
	FROM `daveb_studentHomeReading`.`$studentToRecord`
	ORDER BY numberOfTimes DESC
	LIMIT 1";
	
	$dbr = mysqli_query($dbc, $dbq) or die ("Couldn't connect to the student Home reading record table to check they can borrow books. bookCheckout.php");
	
	$row = $dbr->fetch_array(MYSQLI_NUM);
	
	if (is_null($row[0])){
		echo "According to our records, this student is ". $studentToRecord." and has not returned their last book, so cannot borrow another.";
		die;
	}
	
}

#get the isbn if we're dealing with a multicopy set of isbn's, because the ISBN was not passed through $_POST:
if($bookISBN==0){
	
	$dbq = "SELECT  `bookISBN`
	FROM  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory`
	WHERE  `bookSchoolCode` ='$bookSchoolCode'
	LIMIT 1";

	$dbr = mysqli_query($dbc, $dbq)or die("Coudln't get an ISBN number for this multicopy book. BookCheckout.php");

	$ISBNFetch = $dbr->fetch_array(MYSQLI_NUM);
	$bookISBN = $ISBNFetch[0];
	echo "New book ISBN = ".$bookISBN;
}

#If the above checks are good, and the student is eligible to borrow a book, run the book checkout script, inserting the book Id and time checked out into the student's home reading record table:
$bookCheckOut = "INSERT INTO `daveb_studentHomeReading`.`$studentToRecord` (`bookKey`, `bookISBN`, `bookTitle`, `dateOut`) 
																													VALUES ('$bookKey', '$bookISBN', '$bookTitle', now()) ";

$bookCheckOutRun = mysqli_query($dbc, $bookCheckOut) or die("The book was not checked out, bookCheckOut.php");

#Insert the date into the student's master table:
if (substr($borrowingClass, 0, 1)== '2') {

	include 'mysqliConnectP2.php';
	$allTable = "2all";

}elseif (substr($borrowingClass, 0, 1)== '4'){

	include 'mysqliConnectP4.php';
	$allTable = "4all";

}elseif (substr($borrowingClass, 0, 1)== '5'){

	include 'mysqliConnectP5.php';
	$allTable = "5all";

}

$dbq = "UPDATE `$gradeDB`.`$allTable` SET `lastHRBorrowing` = NOW( ) WHERE  `$allTable`.`class` = '$borrowingClass' && `$allTable`.`classNumber` = $studentClassNumber";
$dbr = mysqli_query($dbc, $dbq) or die("Couldn't update the students master table column lastHRBorrowing");

#Then insert the book title into the student's master table:
$dbq = "UPDATE `$gradeDB`.`$allTable` SET `lastHRBookTitle` = '$bookTitle' WHERE  `$allTable`.`class` = '$borrowingClass' && `$allTable`.`classNumber` = $studentClassNumber";
$dbr = mysqli_query($dbc, $dbq) or die("Couldn't update the students master table column lastHRBookTitle");

#Then insert the book ISBN into the student's master table:
$dbq = "UPDATE `$gradeDB`.`$allTable` SET `lastHRBookISBN` = '$bookISBN' WHERE  `$allTable`.`class` = '$borrowingClass' && `$allTable`.`classNumber` = $studentClassNumber";
$dbr = mysqli_query($dbc, $dbq) or die("Couldn't update the students master table column lastHRBookISBN");

#And change the master record for returned? from null or yes to no:
$dbq = "UPDATE `$gradeDB`.`$allTable` SET `lastHRReturned` = 'No' WHERE  `$allTable`.`class` = '$borrowingClass' && `$allTable`.`classNumber` = $studentClassNumber";
$dbr = mysqli_query($dbc, $dbq) or die("Couldn't update the students master table column returned");

#Update the book table for this book:
include 'mysqliConnectHomeReadingBooksDB.php';

$dbq = "INSERT INTO  `daveb_homeReadingBookRecords`.`$bookKey` (
`numberOfOuts` ,
`studentId` ,
`lastOut` ,
`lastIn`
)
VALUES (
NULL , '$studentToRecord', NOW( ) , NULL
)";

$dbr = mysqli_query($dbc, $dbq) or die("The book record table has not been updated. bookCheckout.php line 171");

#Update the book record in the master record table for all Home reading books:

$dbq = "UPDATE `daveb_homeReadingBookRecords`.`homeReadingBooksInventory` SET numberOfTimesBorrowed=numberOfTimesBorrowed+1, lastBorrowing=now()
WHERE bookKey=$bookKey";

$dbr = mysqli_query($dbc, $dbq) or die("The master home reading books record table has not been updated. bookCheckout.php line 278");

#Update the book status column in the homeReadingBooksInventory table

$dbq = "UPDATE  `daveb_homeReadingBookRecords`.`homeReadingBooksInventory` SET  `bookStatus` =  'Out' WHERE  `homeReadingBooksInventory`.`bookKey` =$bookKey";
$dbr = mysqli_query($dbc, $dbq)or die("Book Status column not updated. bookCheckout.php");

#display the student's reading record as a table for the teacher to see if needed:

$dbq = "SELECT `numberOfTimes` AS `Number of Borrowings`, `bookKey` AS `Key`, `bookISBN` as `Cover & ISBN`, `bookTitle` AS `Title`, `dateOut` AS `Date Borrowed`, `returned` AS `Date Returned` FROM  `daveb_studentHomeReading`.`$studentToRecord` ORDER BY  `numberOfTimes` DESC ";
$dbr = mysqli_query($dbc, $dbq) or die("Couldn't check whether the book has already been entered into the database for the second time in the script. bookCheckout.php");

if($dbr) {# if the query ran ok
	
	include 'studentBorrowingForm.php';

	#get headers for table
	$headers = mysqli_num_fields($dbr);

	#output headers:
	?><table><?php echo "<h1>Student Borrowing Record: $studentToRecord</h1>";
				?><tr><?php 	
					for($i=0; $i<$headers; $i++){
								
						$field = mysqli_fetch_field($dbr);
						echo "<th><a href = '#'>{$field->name}</a></th>";
					}
					echo "</tr>\n";
					#output row data:	
					while($row = mysqli_fetch_row($dbr)){
					    
						echo "<tr>";
					
					    // $row is array... foreach( .. ) puts every element
					    // of $row to $cell variable
					    foreach($row as $cell){
					      
					    	$cellLength = strlen($cell);
					    	$cellStart = substr($cell, 0, 1);
					    	
					    	if($cellLength == 13 && $cellStart == 9){
					    		
					    		?><td><img src="http://davidbrownhk.com/library/homeReadingBookPics/<?php echo $cell?>.jpg" height="200" border=3><?php echo "ISBN: ".$cell; ?></td> <?php
					    		
					    	}else	
					    		
					       	echo "<td>$cell </td>";
					    	 
					    }
					   
					    echo "</tr>\n";
					}
		?></table><?php					
					mysqli_free_result($dbr); 
					 
				}#end if result condition

include 'studentBorrowingForm.php';

?>