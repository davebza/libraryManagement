<?php
?>
<h1>To return a book, scan the ISBN and click:</h1>

<fieldset>
<form action= "bookReturn.php" 
			method = "post">
		
		<p>Bar Code:
			<input type = "text"
		   		name = "bookISBN" autofocus autocomplete="off"
		   		id    = "bookISBN"
		  		size = "20"
		  		maxlength = "20"
		   		value = "" />
		</p>	
			
	<p><input type = "submit"
							class = "button orange"
							name = "submit"
							value = "Return" /></p>
							
			<input type = "hidden"
						name = "hiddenReturn"
						value = "TRUE" />
						
</form>
</fieldset>
<?php 
?>