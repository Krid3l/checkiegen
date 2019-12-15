<?php

session_start();

define("SQ_DIM", 32);			// Dimensions, en pixels, des côtés des cases
define("MAX_CHECK_DIM", 32);	// Dims. max, en cases, des côtés du damier
define("MAX_COLORS", 6);		// Nombre maximal autorisé de couleurs
define("BORDER_WIDTH", 1);		// Épaisseur des contours des cases
// Chemins des fichiers output
define("IMAGE_NAME", "latest.png");
define("LATEST_NAME", "latest.csv");
define("SAVED_NAME", "saved.csv");

// Affiche un message, d'erreur ($exit = true) ou non, à l'utilisateur
function sendMsg(string $msg, string $color, bool $exit = true)
{
	$_SESSION["feedbackMsg"] = "<span style='color: " . $color . ";'>" . $msg . "</span>";
	if ($exit) { exit($msg); }
}

// Convertit une couleur du format hexadécimal au format RGB
function convertColor(string $hexColor) : array
{
	$rgbColor = [];

	// Vérifier que la couleur soit bien au format
	// hexadécimal sans prendre la casse en compte
	if (!preg_match('/^#[a-fA-F0-9]{6}$/i', $hexColor)) {
		sendMsg("Une des couleurs entrées n'est pas une couleur hexadécimale valide.");
	}

	// Conversion de la couleur hexa en valeurs RVB
	list($col_r, $col_g, $col_b) = sscanf($hexColor, "#%02x%02x%02x");

	$rgbColor[0] = $col_r;
	$rgbColor[1] = $col_g;
	$rgbColor[2] = $col_b;

	return $rgbColor;
}

// Génnère une image .png représentant le tableau
function renderCheckerImg(int $width, int $height, array $hexColors)
{
	// Création d'une image vide avec les dimensions
	// fournies en paramètres - un offset de 1 pixel
	// est fourni pour avoir des contours uniformes
	$image = imagecreatetruecolor(($width * SQ_DIM) + 1, ($height * SQ_DIM) + 1);

	for ($i = 0; $i < $height; $i++) {
		for ($j = 0; $j < $width; $j++) {
			// Définition des points géométriques
			$x1 = (SQ_DIM * $j) + BORDER_WIDTH;
			$y1 = (SQ_DIM * $i) + BORDER_WIDTH;
			$x2 = (SQ_DIM * ($j + 1)) - BORDER_WIDTH;
			$y2 = (SQ_DIM * ($i + 1)) - BORDER_WIDTH;
			// Conversion de la couleur en tableau dont les cellules sont R,G,B
			$anRgbColor = convertColor($hexColors[$i][$j]);
			// Génère un idtf. de couleur donné en param à imagefilledrectangle
			$colorId = imagecolorallocate($image,
				$anRgbColor[0],
				$anRgbColor[1],
				$anRgbColor[2]);
			// Traçage de la case
			imagefilledrectangle($image, $x1, $y1, $x2, $y2, $colorId);
		}
	}

	// Enregistrement de l'image
	imagepng($image, IMAGE_NAME);
	// Libération de la mémoire allouée pour l'image
	imagedestroy($image);
}

// Génère le tableau avec des éléments HTML dont le style CSS est injecté
// directement dans les balises
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

// Vérifie les paramètres fournis et, si les contrôles sont négatifs, génère le
// tableau sous forme d'image .png puis sous forme d'élements HTML 
function generate(int $width, int $height, array $hexColors)
{
	if ($width > MAX_CHECK_DIM || $height > MAX_CHECK_DIM) {
		sendMsg("Chaque dimension de l'échiquier ne peut dépasser " . MAX_CHECK_DIM . " cases.", "red");
	}
	else if ($width < 1 || $height < 1) {
		sendMsg("Veuillez entrer des dimensions positives.", "red");
	}

	if (count($hexColors) > MAX_COLORS) {
		sendMsg(MAX_COLORS . " couleurs différentes maximum.", "red");
	}
	else if ($width < 1 || $height < 1) {
		sendMsg("Veuillez entrer un nombre de couleurs positif.", "red");
	}

	$hexCheckboard = array(array());
	// Génération de la grille des couleurs
	for ($i = 0; $i < $height; $i++) {
		for ($j = 0; $j < $width; $j++) {
			// Définition d'une couleur aléatoire parmi
			// celles se trouvant dans le tableau $hexColors
			$randHexColor = $hexColors[array_rand($hexColors, 1)];
			$hexCheckboard[$i][$j] = $randHexColor;
		}
	}

	// Rendu en image .png sauvegardé sur le disque
	renderCheckerImg($width, $height, $hexCheckboard);

	// Rendu en html/css
	renderCheckerHtm($width, $height, $hexCheckboard);

	$_SESSION["latest_hex_grid"] = $hexCheckboard;
	$_SESSION["latest_width"] = $width;
	$_SESSION["latest_height"] = $height;
	$_SESSION["latest_colornb"] = count($hexColors);

	// Sauvegarde du dernier damier généré au format .csv
	save(false);
}

// Sauvegarde le damier en mettant les couleurs en hexa dans un fichier .csv
function save(bool $manualSave = true)
{
	$grid = $_SESSION["latest_hex_grid"];
	$width = $_SESSION["latest_width"];
	$height = $_SESSION["latest_height"];
	// Est-ce qu'il s'agit d'une sauvegarde demandée par l'utilisateur ou de la
	// sauvegarde automatique du dernier damier généré ?
	$manualSave ? $destFile = SAVED_NAME : $destFile = LATEST_NAME;
	$fileHandle_w = fopen($destFile, "w");
	for ($i = 0; $i < $height; $i++) {
		for ($j = 0; $j < $width; $j++) {
			fwrite($fileHandle_w, $grid[$i][$j]);
			// Écrire une virgule n'importe où sauf à la fin de la ligne
			if ($j < $width - 1) {
				fwrite($fileHandle_w, ",");
			}
		}
		fwrite($fileHandle_w, "\n");
	}
	fclose($fileHandle_w);

	if ($manualSave) { sendMsg("Damier sauvegardé avec succès.", "green"); }
}

// Charge le damier à partir du fichier .csv
function restore(bool $manualRestore = true)
{
	$fileHandle_r = fopen(SAVED_NAME, "r");
	$restoredChk = array(array());
	// Suppression de l'élément 0 crée lors du double appel à array(), donnant
	// un tableau dans lequel on peut ensuite empiler les données du .csv
	array_pop($restoredChk);
	// Nb de cellules max * 7 caractères ("#XXXXXX") + nb de virgules max
	$maxLineLen = ((MAX_CHECK_DIM * 7) + (MAX_CHECK_DIM - 1));
    while (($data = fgetcsv($fileHandle_r, $maxLineLen, ",")) !== FALSE) {
        $restoredChk[] = $data;
    }
    fclose($fileHandle_r);

	if ($manualRestore) {
		// Déclenché par l'utilisateur - On charge alors le damier pour qu'il
		// soit à nouveau affiché en haut de la page
		$_SESSION["latest_hex_grid"] = $restoredChk;
		sendMsg("Damier chargé avec succès.", "green", false);
	}
	else {
		// Déclenché automatiquement afin d'afficher le damier sauvegardé en
		// dessous du dernier damier généré
		renderCheckerHtm(count($restoredChk[0]), count($restoredChk), $restoredChk);
	}
}

function main()
{
	$possibleActions = ["generate", "save", "restore"];
	if (!empty($_POST)) {
		// Le changement de header doit être demandé avant tout 'echo'
		header("Location: index.php");
		// Vérifie si une action valide est bien fournie
		$postKeys = array_keys($_POST);
		if (in_array(end($postKeys), $possibleActions)) {
			// TODO : Penser à gêrer les exceptions d'accès aux fichiers
			switch (end($postKeys)) {
				case "generate":
					$width = $_POST["largeur"];
					$height = $_POST["hauteur"];
					for ($i = 0; $i < $_POST["nbcouleurs"]; $i++) {
						$hexColors[] = '#' . substr(md5(mt_rand()), 0, 6);
					}
					//$hexColors = array("#32a852", "#eb4034", "#4287f5", "#fcba03");
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
			exit("Mauvaise action fournie.");
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