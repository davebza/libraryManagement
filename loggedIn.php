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
switch (substr($_SESSION['class'], 1, 2)) {
	
	case 'T':
		
		?>
		<h1>Choose the class to view</h1>

<fieldset>
<form action= "loggedIn.php" 
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
							value = "View records" /></p>
							
			<input type = "hidden"
						name = "hiddenSubmit"
						value = "TRUE" />
						
</form>
</fieldset>
		
		<?php
		
		if(isset($_POST['hiddenSubmit'])){
			
			$gradeAndClass = $_POST['gradeAndClass'];
			if(substr($gradeAndClass, 0, 1)== 2){
				include 'mysqliConnectP2.php';
				$studentTable = '2all';
		}elseif(substr($gradeAndClass, 0, 1)== 4){
				include 'mysqliConnectP4.php';
				$studentTable = '4all';
		}elseif(substr($gradeAndClass, 0, 1)== 5){
				include 'mysqliConnectP5.php';
				$studentTable = '5all';
		}
		
		echo "<h1>Class Overview</h1>";
		
		makeTableOutput($dbc, $studentTable, $gradeAndClass, 'yellow');
		makeTableOutput($dbc, $studentTable, $gradeAndClass, 'blue');
		makeTableOutput($dbc, $studentTable, $gradeAndClass, 'red');
		}#end of table making section
		
		?>
		<fieldset>
			<form action = "generateBorrowingReport.php"
					  method = "post">
					  
					  <h1>Do you want to make a report of the unreturned books?</h1>
					  
					 <p><input type = "submit"
							class = "button orange"
							name = "submit"
							value = "Make report" /></p> 
			
			
			</form>
		</fieldset>
		<?php 
		break;
		
	case 'A'||'B'||'C'||'D':
		
		#Print a customized message:
		echo "<h1> Welcome, {$_SESSION['firstName']}.</h1>
		
		
		<p><h2>Here is your information: </h2></p>
		
		<p><ul>
		<li> Class: {$_SESSION['class']} Number: {$_SESSION['classNumber']}</li>
		<li> Name: {$_SESSION['firstName']} {$_SESSION['lastName']}</li>
		<li> Group: {$_SESSION['grp']}</li>
		</ul>
		</p>";
		
		
		#first, check which grade and class:
		if (substr($classInfo, 0, 1) == '4'){
			include 'mysqliConnectP4.php';
			$dBase = "daveb_P4";
			$table = "4all";
		
		}elseif (substr($classInfo, 0, 1) == '5'){
		
			include 'mysqliConnectP5.php';
			$dBase = "daveb_P5";
			$table = "5all";
		
		}
		
		$dbq = "SELECT `numberOfTimes` AS `Number of Borrowings`, `bookKey` AS `Book Code`, `dateOut` AS `Date Borrowed`, `returned` AS `Date Returned` FROM  `daveb_studentHomeReading`.`$classInfo`";
		$dbr = mysqli_query($dbc, $dbq) or die("Couldn't check whether the book has already been entered into the database for the second time in the script. loggedIn.php");
		
		if($dbr) {# if the query ran ok
		
			#get headers for table
			$headers = mysqli_num_fields($dbr);
		
			#output headers:
			?><table><?php echo "<h1>Book Borrowing Record: $classInfo</h1>";
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
									        echo "<td>$cell</td>";
									    }
									    echo "</tr>\n";
									}
						?></table><?php					
									mysqli_free_result($dbr);
									
								}#end if result condition
		
		echo "<p>You can now choose your worksheet, practice your sightwords or look at the worksheets you have already finished, or logout.</p>
		
		<p><a href=\"logout.php\" class = \"button orange\">Logout</a></p>";
		
		break;
		
		
	default:
		echo "There seems to be a problem, loggedIn.php can't decide what class you are in, or if you are a teacher.";
		break;
}# end of switch grp
?>

<?php
include_once 'footer.html';
?>