<?php

/* TODO

- escaping input
- cleaning up files
- file security
- add timestamp in database?
- store only 10 in database - "FIFO que"

LINKS
http://blog.gleitzman.com/post/39978828612/speaking-with-computer-generated-voices
http://hts.sp.nitech.ac.jp/?Voice%20Demos

*/

require_once 'inc/config.php';

// database connection
try {

	$db = new PDO('mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . '',DB_USER,DB_PASS);

	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

	$db->exec("SET NAMES 'utf8'");

} 	catch (Exception $error) {
			echo "Could not connect to database: " . $error;
		exit;
}

// main program logic
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $texttosay = trim($_POST["t"]);

    if ($texttosay != "") {

    	if (strlen($texttosay) <= 255) {

	    $tempfilename = uniqid();

			$file = fopen($tempfilename,"x");

			fwrite($file, $texttosay);
			fclose($file);

			$outwav = $tempfilename . ".wav";
			$outmp3 = $tempfilename . ".mp3";

			$command = "text2wave -o " . $outwav . " " . $tempfilename;
			exec($command);
			exec("lame " . $outwav . " " . $outmp3);

			try {
				$db_query = $db->prepare("INSERT INTO messages(message) VALUES(?)");

				$db_query->bindParam(1,$texttosay);

				$db_query->execute();

			}	catch (Exception $error) {
					echo "The query could not be completed: " . $error;
			}

	    } else {
	    	$error_message = "Error: This text string is too long. GLaDOS will only accept 255 characters or less";
	    }

    } else {
    		$error_message = "Error: Please enter some text to say.";
    }

} // end main program logic

// read the database
try {
	$db_query = $db->prepare("SELECT message FROM messages ORDER BY id DESC LIMIT 10");
	$db_query->execute();
}
catch (Exception $error) {
	echo "The query could not be completed: " . $error;
}

$stored_messages = $db_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  	<meta charset="utf-8" />
	<title>GLaDOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="bs/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="bs/css/bootstrap-theme.min.css" rel="stylesheet" media="screen">
    <link href="css/my-styles.css" rel="stylesheet" media="screen">
</head>
<body>

<div class="container">
	<h1>GLaDOS</h1>

	<p>
	(Genetic Lifeform and Disk Operating System) is a fictional artificially intelligent computer system and the main antagonist in the game Portal as well as the first half of its sequel, Portal 2. She was created by Erik Wolpaw and Kim Swift and is voiced by Ellen McLain. She is responsible for testing and maintenance in Aperture Science research facility in both video games. While she initially appears to simply be a voice to guide and aid the player, her words and actions become increasingly malicious until she makes her intentions clear. The game reveals that she is corrupted and used a neurotoxin to kill the scientists in the lab before the events of Portal. She is destroyed at the end of the first title by the player-character Chell but is revealed to have survived in the credits song "Still Alive".
	</p>

	<div class="theform">

		<form role="form" method="POST" action="">
		  	<div class="form-group">
		    	<!-- <label for="t">Text (max 255 characters)</label> -->
		    	<input type="text" name="t" class="form-control" id="texttosay" placeholder="Enter Text To Say (max 255 characters)">
		  	</div>

			<div class="errors">
			<?php // display error message if any
				if (isset($error_message)) {
					echo '<p class="alert alert-warning">' . $error_message . '</p>';
				}
			?>
		</div>
		  	<button type="submit" class="btn btn-primary">Make GLaDOS Say It</button>
		</form>

	</div>

	<?php if(isset($_POST["t"]) && !isset($error_message)) { ?>

		<audio controls autoplay style="display:none;">
			<source src="<?php echo $outmp3; ?>" type="audio/mpeg">
		</audio>

	<?php } ?>

	<div class="messages">
	<ul>

		<h3>Last 10 messages:</h3>

		<?php
			foreach($stored_messages as $message) { ?>
			<li>
				<p><?php echo '"' . $message["message"] . '"'; ?></p>
		    </li>
		<?php } ?>

	</ul>
	</div>

</div>

<footer>
	<script src="http://code.jquery.com/jquery.js"></script>
	<script src="bs/js/bootstrap.min.js"></script>
</footer>

</body>
</html>

<?php
	// Cleaning Up
	unlink($tempfilename);
	unlink($outwav);
	//unlink($outmp3);

	// Close database connection
	if(isset($db)) {
		mysql_close($db);
	}

	//build command to delete mp3
	//$delcmd = "./delscript.sh " . $outmp3 . " &";
	//exec($delcmd);
?>