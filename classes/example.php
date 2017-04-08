<html>
<head>
<meta charset="UTF-8"> <!-- Make sure you are using UTF-8 for special character compatibility -->
<title>malAPI Demo</title>
</head>
<body>
<?php
/*
 * @author ebildude123
 * @license GNU Lesser General Public License <https://www.gnu.org/licenses/lgpl.html>
 */
 
require "mal.class.php";
$malAPI = new malAPI("ebildude123");
$getData = $malAPI->getAnimeList();
$userInfo = $getData["UserInfo"];
$animeInfo = $getData["Anime"];

echo "<h1>Anime List: " . $userInfo["Username"] . "</h1>\n";
echo "<font size='5'><u>Completed (" . $userInfo["Completed"] . ")</u></font> <br>\n";
foreach ($animeInfo["Completed"] as $animeName => $animeData) {
	echo "<i>" . $animeName . "</i> (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . " <br>\n";
}

echo "<p>&nbsp;</p>\n";

echo "<font size='5'><u>Watching (" . $userInfo["Watching"] . ")</u></font> <br>\n";
foreach ($animeInfo["Watching"] as $animeName => $animeData) {
	echo "<i>" . $animeName . "</i> (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . " <br>\n";
}

echo "<p>&nbsp;</p>\n";

echo "<font size='5'><u>Dropped (" . $userInfo["Dropped"] . ")</u></font> <br>\n";
foreach ($animeInfo["Dropped"] as $animeName => $animeData) {
	echo "<i>" . $animeName . "</i> (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . " <br>\n";
}

echo "<p>&nbsp;</p>\n";

echo "<font size='5'><u>Plan To Watch (" . $userInfo["Planned"] . ")</u></font> <br>\n";
foreach ($animeInfo["Planned"] as $animeName => $animeData) {
	echo "<i>" . $animeName . "</i> (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . " <br>\n";
}

echo "<p>&nbsp;</p>\n";

echo "<font size='5'><u>On Hold (" . $userInfo["OnHold"] . ")</u></font> <br>\n";
foreach ($animeInfo["OnHold"] as $animeName => $animeData) {
	echo "<i>" . $animeName . "</i> (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . " <br>\n";
}
?>
</body>
</html>