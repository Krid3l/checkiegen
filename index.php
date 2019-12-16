<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkerboard generator</title>
    <meta charset="UTF-8">
</head>
<body>
	<?php
		require_once("checkiegen.php");
		// Fields are initialized with default values
		// if no checkerboard was generated yet
		isset($_SESSION["latest_width"]) ? $width = $_SESSION["latest_width"] : $width = 8;
		isset($_SESSION["latest_height"]) ? $height = $_SESSION["latest_height"] : $height = 8;
		isset($_SESSION["latest_colorcount"]) ? $colorcount = $_SESSION["latest_colorcount"] : $colorcount = 4;
	?>
	<form action="checkiegen.php" method="post" style="font-family: monospace;">
		<label for="width">Width :</label>
		<input type="text" name="width" id="width" maxlength="2" size="2" value="<?php echo $width ?>">
		<label for="height">Height :</label>
		<input type="text" name="height" id="height" maxlength="2" size="2" value="<?php echo $height ?>">
			<br>
		<label for="colorcount">Number of colors:</label>
		<input type="text" name="colorcount" id="colorcount" maxlength="1" size="1" value="<?php echo $colorcount ?>">
			<br>
		<label for=""></label>
		<input type="submit" name="generate" value="Générer">
		<input type="submit" name="save" value="Sauvegarder">
		<input type="submit" name="restore" value="Restaurer">
	</form>
	<?php
		if (isset($_SESSION["feedbackMsg"])) {
			echo $_SESSION["feedbackMsg"];
			unset($_SESSION["feedbackMsg"]);
		}
		if (isset($_SESSION["latest_hex_grid"])) {
			?> <br><div style="background-color: lightcyan"><span>Last generated checkerboard:</span> <?php
			renderCheckerHtm($_SESSION["latest_width"], $_SESSION["latest_height"], $_SESSION["latest_hex_grid"]);
		}
		?> </div> <?php
		if (file_exists("saved.csv")) {
			?> <br><div style="background-color: lemonchiffon"> <span>Saved checkerboard:</span> <?php
			restore(false);
		}
	?>
	</div>
</body>