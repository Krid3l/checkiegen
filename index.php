<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Générateur de damier</title>
    <meta charset="UTF-8">
</head>
<body>
	<?php
		require_once("checkiegen.php");
		// Initialisation des champs avec des valeurs par défaut si aucun
		// tableau n'a été généré pour l'instant
		isset($_SESSION["latest_width"]) ? $largeur = $_SESSION["latest_width"] : $largeur = 8;
		isset($_SESSION["latest_height"]) ? $hauteur = $_SESSION["latest_height"] : $hauteur = 8;
		isset($_SESSION["latest_colornb"]) ? $nbcouleurs = $_SESSION["latest_colornb"] : $nbcouleurs = 4;
	?>
	<form action="checkiegen.php" method="post" style="font-family: monospace;">
		<label for="largeur">Largeur :</label>
		<input type="text" name="largeur" id="largeur" maxlength="2" size="2" value="<?php echo $largeur ?>">
		<label for="hauteur">Hauteur :</label>
		<input type="text" name="hauteur" id="hauteur" maxlength="2" size="2" value="<?php echo $hauteur ?>">
			<br>
		<label for="nbcouleurs">Nombre de couleurs :</label>
		<input type="text" name="nbcouleurs" id="nbcouleurs" maxlength="1" size="1" value="<?php echo $nbcouleurs ?>">
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
			?> <br><div style="background-color: lightcyan"><span>Dernier damier généré :</span> <?php
			renderCheckerHtm($_SESSION["latest_width"], $_SESSION["latest_height"], $_SESSION["latest_hex_grid"]);
		}
		?> </div> <?php
		if (file_exists("saved.csv")) {
			?> <br><div style="background-color: lemonchiffon"> <span>Damier sauvegardé :</span> <?php
			restore(false);
		}
	?>
	</div>
</body>