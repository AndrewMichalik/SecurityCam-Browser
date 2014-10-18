<?php

// Constants
$cam_cm1 = "Front";
$cam_cm2 = "Rear";
$cam_cm3 = "Basement";
$dir_cm1 = "cam_cm1/";
$dir_cm2 = "cam_cm2/";
$dir_cm3 = "cam_cm3/";
$stadjust = -7*60*60;		// Server time adjustment for proper hour display

// Set the defaults, min and max
$pagerow = !isset($_REQUEST[pagerow]) ? 2 : min(max($_REQUEST[pagerow], 1), 8);
$pagecol = !isset($_REQUEST[pagecol]) ? 3 : min(max($_REQUEST[pagecol], 1), 8);
$scale   = !isset($_REQUEST[scale]) ? 2 : min(max($_REQUEST[scale], 1), 4);

// Support functions
function ShowIndex($camera, $dir, $stadjust)
{
	$datetime = "m.d.H";
	$hdir = opendir($dir);
	$pics = array();
	$counts = array();
	$links = $camera.": ";

	// Build arrays of available dates and count
	while (false !== ($fname = readdir($hdir))) {
        if (ereg('\.jpe?g$',$fname)) {
			$sdatetime = date($datetime, filemtime($dir.$fname) + $stadjust);
			$counts[$sdatetime]++;
			$pics[$fname] = $sdatetime;
        }
	}
    closedir($hdir);

	// Any pictures?
	$linkcount = count($counts);
	if ($linkcount == 0) $links .= "None";
	
	// Sort the count array by timestamp
	krsort($counts);

	// Add "delete all for camera" links
	$delete .= !$linkcount ? "None" : "Delete all <a href='?delete=$camera' style='text-decoration:none'>$linkcount hour(s) of $camera images</a><br>";		

	// Build string to link to pictures for that day
	foreach ($counts as $datetime => $count) {
		$links .= "<a href='?camera=$camera&sdatetime=$datetime' style='text-decoration:none'>$datetime($count)</a> ";		
		$delete .= "<a href='?sdatetime=$datetime&delete=$camera' style='text-decoration:none'>$datetime($count)</a><br>";		
	}
	
	// Return the list of pictures and the link string
	return array($pics, $links, $delete);
}

function ShowThumbs($dir, $pics, $camera, $sdatetime, $stadjust, $position, $scale, $pagerow, $pagecol) {
	//  Filter the pics by timestamp
	$pics = array_filter($pics, array(new array_datetime($sdatetime), 'checkdatetime'));
	return (ShowTable($dir, $pics, $camera, $sdatetime, $stadjust, $position, $scale, $pagerow, $pagecol));
}

function ShowTable($dir, $pics, $camera, $sdatetime, $stadjust, $position, $scale, $pagerow, $pagecol) {
	// Sort function based on last 18 timestamp characters (and .jpg)
	function name_sort($a, $b) {
		return (strcmp(substr($a, -18), substr($b, -18)));
	}

	// Anything to do?
	$pic_count = count($pics);
	if ($pic_count <= $position) return ("Sorry, no images available.");
	
	// Sort the the pics by trailing filename timestamp
	uksort($pics, "name_sort");

	// Skip any previous elements
	$pics = array_slice ($pics, $position);
	
	// Initialize the output string
	$images = "<br><table border=1 cellpadding=1 cellspacing=0>";

	// Build table
	foreach ($pics as $fname => $datetime) {
		$filetime_adj = filemtime($dir . $fname) + $stadjust;
		if ($position % $pagecol == 0) {
			if ($position != 0) $images .= "</tr>";
			$images .= "<tr>";
		}
		$position++;
		$images .= "<td><a href=viewer.php?dir=$dir&img=$fname title=\"$fname\" style='text-decoration:none'>";
		$images .= "<img src=thumbgen.php?dir=$dir&img=$fname&scale=$scale alt=\"$fname\" border=0></a>";
		$images .= "<br><font size=-1>".date("m.d H:i:s", $filetime_adj)."</font></td>";
		
		// Done with this page?
		if ($position % ($pagecol * $pagerow) == 0) break;
	}
	$images .= "</tr>";
	$images .= "</table>";

	// More pages?
	$previousnext = "";
	if ($position > $pagecol*$pagerow) {
		$newposition = ceil(($position / ($pagecol*$pagerow)) - 2) * ($pagecol*$pagerow);
		$previousnext .= "<a href='?camera=$camera&sdatetime=$datetime&position=$newposition' style='text-decoration:none'>&lt; Prev&nbsp;&nbsp;&nbsp;&nbsp;</a> ";		
	} else $previousnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	if ($pic_count > $position) {
		$newposition = min($pagecol*$pagerow, $pic_count - $position);
		$previousnext .= "<a href='?camera=$camera&sdatetime=$datetime&position=$position' style='text-decoration:none'>Next $newposition &gt;&nbsp;&nbsp;&nbsp;&nbsp;</a> ";		
	}
	if ($position + $pagecol*$pagerow < $pic_count) {
		$newposition = $pic_count - ((($pic_count - 1) % ($pagecol*$pagerow)) + 1);
		$previousnext .= "<a href='?camera=$camera&sdatetime=$datetime&position=$newposition' style='text-decoration:none'>Last &gt;&gt;</a>";		
	}
	$images = "<br>$sdatetime($pic_count) $previousnext" . $images;
	return ($images);
}

function DeletePics($dir, $pics, $camera, $sdatetime, $deleteconfirm) {
	$files_delete = "";
	$files_period = strlen($sdatetime) ? $sdatetime : "all time";
	
	// Filtered by date (or just by camera)?
	if ($sdatetime != "") $pics = array_filter($pics, array(new array_datetime($sdatetime), 'checkdatetime'));
	
	// Are you sure?
	if ($deleteconfirm != "yes") {
		$count = count($pics);
		$files_delete = "<a href='./' style='text-decoration:none'><b>Cancel</b></a> or Delete <a href='?sdatetime=$sdatetime&delete=$camera&deleteconfirm=yes' style='text-decoration:none'>$count $camera camera files from $files_period:</a><br>";		
		foreach ($pics as $fname => $datetime) {
			$files_delete .= $fname . "<br>";
		}
		return ($files_delete);
	}

	// Delete confirmed, proceed
	if ($deleteconfirm == "yes") {
		$files_delete = "Deleting, <a href='./' style='text-decoration:none'>click to continue...</a><br>";		
		foreach ($pics as $fname => $datetime) {
			$files_delete .= $dir . $fname . "<br>";
			unlink ($dir . $fname);
		}
		$files_delete .= "Delete completed. <a href='./' style='text-decoration:none'>Click to continue...</a><br>";
		return ($files_delete);
	}
}

class array_datetime {
	function array_datetime($datetime) { $this->datetime = $datetime; }
	function checkdatetime($string) {
		return ($string == $this->datetime);
	}
}

?>

<html>
<head>
<title>SecurityCam Library :: <?=$_REQUEST[sdatetime]?></title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;"> 
<link rel="stylesheet" type="text/css" href="/styles/default.css">
</head>
<body>

	<?php
	// Generate cm1 camera links; check for delete request ir thumbnail review
	list($pics, $links_cm1, $links_cm1_delete) = ShowIndex($cam_cm1, $dir_cm1, $stadjust);
	if ($_REQUEST[delete] == $cam_cm1) $files_delete_cm1 = DeletePics($dir_cm1, $pics, $_REQUEST[delete], $_REQUEST[sdatetime], $_REQUEST[deleteconfirm]);
	if ($_REQUEST[camera] == $cam_cm1) $images_cm1 = ShowThumbs($dir_cm1, $pics, $_REQUEST[camera], $_REQUEST[sdatetime], $stadjust, $_REQUEST[position], $scale, $pagerow, $pagecol);

	// Generate cm2 camera links; check for delete request ir thumbnail review
	list($pics, $links_cm2, $links_cm2_delete) = ShowIndex($cam_cm2, $dir_cm2, $stadjust);
	if ($_REQUEST[delete] == $cam_cm2) $files_delete_cm2 = DeletePics($dir_cm2, $pics, $_REQUEST[delete], $_REQUEST[sdatetime], $_REQUEST[deleteconfirm]);
	if ($_REQUEST[camera] == $cam_cm2) $images_cm2 = ShowThumbs($dir_cm2, $pics, $_REQUEST[camera], $_REQUEST[sdatetime], $stadjust, $_REQUEST[position], $scale, $pagerow, $pagecol);

	// Generate cm3 camera links; check for delete request ir thumbnail review
	list($pics, $links_cm3, $links_cm3_delete) = ShowIndex($cam_cm3, $dir_cm3, $stadjust);
	if ($_REQUEST[delete] == $cam_cm3) $files_delete_cm3 = DeletePics($dir_cm3, $pics, $_REQUEST[delete], $_REQUEST[sdatetime], $_REQUEST[deleteconfirm]);
	if ($_REQUEST[camera] == $cam_cm3) $images_cm3 = ShowThumbs($dir_cm3, $pics, $_REQUEST[camera], $_REQUEST[sdatetime], $stadjust, $_REQUEST[position], $scale, $pagerow, $pagecol);
	?>

	<?php
	// Handle delete requests
	if (strlen($files_delete_cm1)) {
		echo ("<table border=1 bordercolor='black'><tr><th><?=$cam_cm1?> Camera Cleanup</th></tr>");
		echo ("<tr><td>");
		echo ($files_delete_cm1);
		die ("</td></tr></table></body></html>");
	}
	if (strlen($files_delete_cm2)) {
		echo ("<table border=1 bordercolor='black'><tr><th><?=$cam_cm2?> Camera Cleanup</th></tr>");
		echo ("<tr><td>");
		echo ($files_delete_cm2);
		die ("</td></tr></table></body></html>");
	}
	if (strlen($files_delete_cm3)) {
		echo ("<table border=1 bordercolor='black'><tr><th><?=$cam_cm3?> Camera Cleanup</th></tr>");
		echo ("<tr><td>");
		echo ($files_delete_cm3);
		die ("</td></tr></table></body></html>");
	}
	?>

	<table border=0 cellpadding=8 cellspacing=0>
	<tr><td>
	<table border=1 bordercolor='black' width='100%'>
		<tr><th>
		<p><a href='.' style='text-decoration:none'>SecurityCam Library :: <?=$_REQUEST[sdatetime]?></a></p>
		</th></tr>
	</table>
	</td></tr>

	<tr><td>
	<?=$links_cm1?><br>
	<?=$images_cm1?><br>
	<?=$links_cm2?><br>
	<?=$images_cm2?><br>
	<?=$links_cm3?><br>
	<?=$images_cm3?><br>
	</td></tr>

	<tr><td>
	<table border=1 bordercolor='black' width='100%'>
		<tr><th>
		<p>Image Maintenance</p>
		</th></tr>
	</table>
	</td></tr>

	<tr><td>
	<b><?=$cam_cm1?> Camera Cleanup:</b>
	<br>
	<?=$links_cm1_delete?>
	<br>
	<b><?=$cam_cm2?> Camera Cleanup:</b><br>
	<?=$links_cm2_delete?>
	<br>
	<b><?=$cam_cm3?> Camera Cleanup:</b><br>
	<?=$links_cm3_delete?>
	</td></tr>
	</table>
	<br>&nbsp;<br>
</body>
</html>
