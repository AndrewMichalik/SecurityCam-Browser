<?php

// Constants
$stadjust = -3*60*60;		// Server time adjustment for proper hour display

// Support functions
function LinkNext($dir, $img)
{
	// Sort function based on last 18 timestamp characters (and .jpg)
	function name_sort($a, $b) {
		return (strcmp(substr($a, -18), substr($b, -18)));
	}

	$datetime = "m.d.H";
	$hdir = opendir($dir);
	$pics = array();
	$links = "<img src='$dir$img' border=0>";
	$prev = "";
	$next = "";

	// Build arrays of available files
	while (false !== ($fname = readdir($hdir))) {
        if (ereg('\.jpe?g$',$fname)) {
			$sdatetime = date($datetime, filemtime($dir.$fname) + $stadjust);
			$pics[$fname] = $sdatetime;
        }
	}
    closedir($hdir);

	// Sort the the pics by trailing filename timestamp
	uksort($pics, "name_sort");

	// Get the next file name from the array
	reset($pics);
	while (!is_null($key = key($pics))) {
		next($pics);
		if ($key == $img) break;
	}
	$next = key($pics);

	// Any pictures next?
	if (!is_null($next)) {
		// Build string to link to the next picture
		$links = "<a href='viewer.php?dir=$dir&img=$next' style='text-decoration:none' title='Next :: $next'>" . $links . "</a>";		
	}

	// Return the list of pictures and the link string
	return ($links);
}

?>

<html>
<head>
<title>Viewer :: <?=$_GET[dir] . $_GET[img]?></title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;"> 
<link rel="stylesheet" type="text/css" href="/styles/default.css">
</head>
<body>

	<?=LinkNext($_GET[dir], $_GET[img]);?>
	<br>&nbsp;<br>

</body>
</html>
