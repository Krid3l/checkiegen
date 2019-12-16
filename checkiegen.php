<?php

session_start();

define("SQ_DIM", 32);			// Squares' sides' length in pixel
define("MAX_CHECK_DIM", 32);	// Max squares in a checkerboard for both axes
define("MAX_COLORS", 6);		// Max color count allowed
define("BORDER_WIDTH", 1);		// Squares' border width
// Output filepaths
define("IMAGE_NAME", "latest.png");
define("LATEST_NAME", "latest.csv");
define("SAVED_NAME", "saved.csv");

// Displays a message to the user; can be an error ($exit == true) or not
function sendMsg(string $msg, string $color, bool $exit = true)
{
	$_SESSION["feedbackMsg"] = "<span style='color: " . $color . ";'>" . $msg . "</span>";
	if ($exit) { exit($msg); }
}

// Converts a color from hexa to RGB
function convertColor(string $hexColor) : array
{
	$rgbColor = [];

	// Check that the color is indeed an hexa string, case-agnostic
	if (!preg_match('/^#[a-fA-F0-9]{6}$/i', $hexColor)) {
		sendMsg("One of the provided colors isn't a valid hexadecimal color string.");
	}

	// Actual conversion
	list($col_r, $col_g, $col_b) = sscanf($hexColor, "#%02x%02x%02x");

	$rgbColor[0] = $col_r;
	$rgbColor[1] = $col_g;
	$rgbColor[2] = $col_b;

	return $rgbColor;
}

// Generates a .png image render of the checkboard
function renderCheckerImg(int $width, int $height, array $hexColors)
{
	// Creates an empty image with the provided dimensions, a one-pixel offset
	// is applied to have almost-uniform borders
	$image = imagecreatetruecolor(($width * SQ_DIM) + 1, ($height * SQ_DIM) + 1);

	for ($i = 0; $i < $height; $i++) {
		for ($j = 0; $j < $width; $j++) {
			// Geometrical points
			$x1 = (SQ_DIM * $j) + BORDER_WIDTH;
			$y1 = (SQ_DIM * $i) + BORDER_WIDTH;
			$x2 = (SQ_DIM * ($j + 1)) - BORDER_WIDTH;
			$y2 = (SQ_DIM * ($i + 1)) - BORDER_WIDTH;
			// Color converted to an array with cells containing the RGB values
			$anRgbColor = convertColor($hexColors[$i][$j]);
			// Generates a color id given to imagefilledrectangle afterwards
			$colorId = imagecolorallocate($image,
				$anRgbColor[0],
				$anRgbColor[1],
				$anRgbColor[2]);
			// Actual square drawing
			imagefilledrectangle($image, $x1, $y1, $x2, $y2, $colorId);
		}
	}

	imagepng($image, IMAGE_NAME); // Image render save
	imagedestroy($image); // Free the memory allowed for the image
}

// Generates an HTML checkerboard with the squares' properties provided by CSS
function renderCheckerHtm(int $width, int $height, array $colors)
{
	echo "<div>";
	for ($i = 0; $i < $height; $i++) {
		echo "<div style='display: flex; flex-direction: row;'>";
		for ($j = 0; $j < $width; $j++) {
			echo "<div style='" .
				 "box-sizing: border-box;" .
				 "border: solid black " . BORDER_WIDTH . "px;" .
				 "background-color: " . $colors[$i][$j] . ";" .
				 "width: " . SQ_DIM . "px;" .
				 "height: " . SQ_DIM . "px;'></div>";
		}
		echo "</div>";
	}
	echo "</div>";
}

// Checks the provided parameters then generates the board as image and as HTML 
function generate(int $width, int $height, array $hexColors)
{
	$hexCheckboard = array(array());
	// Color grid generation
	for ($i = 0; $i < $height; $i++) {
		for ($j = 0; $j < $width; $j++) {
			// Picks a random color among the ones provided
			$randHexColor = $hexColors[array_rand($hexColors, 1)];
			$hexCheckboard[$i][$j] = $randHexColor;
		}
	}

	// Renders a locally-saved image
	renderCheckerImg($width, $height, $hexCheckboard);

	// Renders an echo'd HTML/CSS version
	renderCheckerHtm($width, $height, $hexCheckboard);

	$_SESSION["latest_hex_grid"] = $hexCheckboard;
	$_SESSION["latest_width"] = $width;
	$_SESSION["latest_height"] = $height;
	$_SESSION["latest_colornb"] = count($hexColors);

	// Saves the last generated checkerboard
	save(false);
}

// Saves a checkerboard by writing the board as hex colors to a .csv file
function save(bool $manualSave = true)
{
	$grid = $_SESSION["latest_hex_grid"];
	$width = $_SESSION["latest_width"];
	$height = $_SESSION["latest_height"];
	// Is the save triggered by the user or is it automatic?
	$manualSave ? $destFile = SAVED_NAME : $destFile = LATEST_NAME;
	$fileHandle_w = fopen($destFile, "w");
	for ($i = 0; $i < $height; $i++) {
		for ($j = 0; $j < $width; $j++) {
			fwrite($fileHandle_w, $grid[$i][$j]);
			// Do not include a comma after the last square of a row
			if ($j < $width - 1) {
				fwrite($fileHandle_w, ",");
			}
		}
		fwrite($fileHandle_w, "\n");
	}
	fclose($fileHandle_w);

	if ($manualSave) { sendMsg("Board saved successfully.", "green"); }
}

// Loads the board from a .csv file
function restore(bool $manualRestore = true)
{
	$fileHandle_r = fopen(SAVED_NAME, "r");
	$restoredChk = array(array());
	// Removes the 0th element so we can push values in the array afterwards
	array_pop($restoredChk);
	// Max number of squares * 7 chars ("#XXXXXX") + max number of commas
	$maxLineLen = ((MAX_CHECK_DIM * 7) + (MAX_CHECK_DIM - 1));
    while (($data = fgetcsv($fileHandle_r, $maxLineLen, ",")) !== FALSE) {
        $restoredChk[] = $data;
    }
    fclose($fileHandle_r);

	if ($manualRestore) {
		// Triggered by the user - loads the manually-saved board and replaces
		// and displays it
		$_SESSION["latest_hex_grid"] = $restoredChk;
		sendMsg("Board loaded successfully.", "green", false);
	}
	else {
		// Automatically triggered - displays the saved board under the latest
		// generated one
		renderCheckerHtm(count($restoredChk[0]), count($restoredChk), $restoredChk);
	}
}

function main()
{
	$possibleActions = ["generate", "save", "restore"];
	if (!empty($_POST)) {
		$width = $_POST["width"];
		$height = $_POST["height"];

		if ($width > MAX_CHECK_DIM || $height > MAX_CHECK_DIM) {
			sendMsg("Each side of the board cannot be more than " . MAX_CHECK_DIM . " squares across.", "red");
		}
		else if ($width < 1 || $height < 1) {
			sendMsg("Please input dimensions greater than zero.", "red");
		}

		if ($_POST["colorcount"] > MAX_COLORS) {
			sendMsg("Up to " . MAX_COLORS . " colors allowed.", "red");
		}
		else if ($width < 1 || $height < 1) {
			sendMsg("Please input positive dimensions.", "red");
		}
		
		// Header change has to be called before any echo statement
		header("Location: index.php");
		// Checks if the provided action string is among the authorized ones
		$postKeys = array_keys($_POST);
		if (in_array(end($postKeys), $possibleActions)) {
			// TODO : Add file r/w exception handling
			switch (end($postKeys)) {
				case "generate":
					for ($i = 0; $i < $_POST["colorcount"]; $i++) {
						$hexColors[] = '#' . substr(md5(mt_rand()), 0, 6);
					}
					generate($width, $height, $hexColors);
					break;
				case "save":
					save();
					break;
				case "restore":
					restore();
					break;
				default:
					break;
			}
		}
		else {
			exit("Bad action provided.");
		}
	}
	else {
		preg_match("/\/([^\/]*)$/", $_SERVER["REQUEST_URI"], $pagename);
		if ($pagename[0] == "/checkiegen.php") {
			echo '<video width="490" height="360" controls autoplay loop>' .
				'<source src="mystery.webm">' .
			'</video>'; 
		}
	}	
}

main();