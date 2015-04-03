<?php
/*
*	Converted according to PHP version 5.3.x from 5.2.x using mysqli
*/
class mydb {
	private $link;
	private $host;
	private $user;
	private $pw;
	private $db;

	function __construct ($dbCon ) {
		$this->host = $dbCon['dbHost'];
		$this->user = $dbCon['dbUid'] ;
		$this->pw = $dbCon['dbPw'] ;
		$this->db = $dbCon['db'] ;
	}

	function __destruct() {
		$this->db_close();
	}

	function connect()	{
		try {
			$this->link = @mysqli_connect( "$this->host", "$this->user", "$this->pw");//, "$this->db");
			if (!$this->link){
				throw new Exception("Could not connect to the MySQL server.");
			} else {
				$this->select();			
			}
		} catch( Exception $con_error ) {
			// echo($con_error->getMessage());
			
			try {
				$this->link = @mysqli_connect( "$this->host", "$this->user", "$this->pw");
				if (!$this->link){
					echo "<script>";
					echo "newwindow=window.open('../common/dbConnectionAlert.html','Alert','height=240,width=360');";
					echo "if (window.focus) {newwindow.focus()}";
					echo "</script>";
					exit;
					//throw new Exception("Could not connect to the MySQL server.");
				}else {
					$this->select();			
				}
					
			} catch( Exception $con_error_a ) {
				echo($con_error_a->getMessage());
			}
		}
	}

	function select()	{
		try {
			if (!mysqli_select_db($this->link,$this->db))
				throw new Exception("Could not select the MySQL database.");
		} catch( Exception $e ) {
			echo($e->getMessage());
		}
	}

	function query($query)	{
			$this->connect();
			$this->rs = @mysqli_query($this->link,$query);
			return $this->rs;
	}


	function fetch_array($query)	{
		$this->connect();//echo $query."<br>";
		$this->rs = @mysqli_query($this->link,$query);
		$tempArray = array();
		$i=0;
		while( $data = @mysqli_fetch_array( $this->rs ) ) {
			$tempArray[$i] =  $data ;
			$i++;
		}			
		return $tempArray ;
	}

	function db_close() {
		if ($this->rs) {
			@mysql_free_result( $this->rs );
		}		
		if ($this->link){
			@mysql_close($this->link);
		}
	}

	function insert_id() {
		return mysqli_insert_id($this->link);
	}

	function affected_rows() {
		return mysqli_affected_rows($this->link);
	}

}

function webToDB ( $origin ) {
	$chValue = str_replace ( "\'" , "'", $origin ) ;
	$chValue = str_replace ( "'" , "\'", $chValue ) ;
	$chValue = str_replace ( '"' , "\'", $chValue ) ;
	$chValue = trim($chValue);
	return $chValue ;
}

// This does not include the change of double quotations to single quotations.
function webToDB2 ( $origin ) {
	$chValue = str_replace ( "\'" , "'", $origin ) ;
	$chValue = str_replace ( "'" , "\'", $chValue ) ;
	$chValue = trim($chValue);
	return $chValue ;
}

// Sanitize user input from a form or some text box before display user's input back to html.
function sanitizeInput($input, $flag=ENT_QUOTES) {
	/*
	*  What htmlspecialchars() does:
		'&' (ampersand) becomes '&amp;'
		'"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
		"'" (single quote) becomes '&#039;' (or &apos;) only when ENT_QUOTES is set.
		'<' (less than) becomes '&lt;'
		'>' (greater than) becomes '&gt;'
	*/
	$result = htmlspecialchars($input, $flag);
	// Add here for any other santizer if needed

	return $result;
}

// Un-Sanitize user input with possible sanitized input
//   *SHOULD NOT BE USED FOR RENDERING TEXT ON WEBPAGES - security issue
//   *SHOULD BE used for only place where is not a webpage and where ONLY html is not allowed. (i.e. email subject)
function unsanitizeInput($input, $flag=ENT_QUOTES) {
	/*
	*  What htmlspecialchars_decode() does:
		Reverse what have done as below...
		'&' (ampersand) becomes '&amp;'
		'"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
		"'" (single quote) becomes '&#039;' (or &apos;) only when ENT_QUOTES is set.
		'<' (less than) becomes '&lt;'
		'>' (greater than) becomes '&gt;'
	*/
	$result = htmlspecialchars_decode($input, $flag);
	// Add here for any other santizer if needed

	return $result;
}
?>
