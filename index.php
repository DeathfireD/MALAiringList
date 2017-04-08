<?php
//settings
$cache_ext  = '.html'; //file extension
$cache_time     = 3600;  //Cache file expires afere these seconds (1 hour = 3600 sec)
$cache_folder   = 'cache/'; //folder to store Cache files
$ignore_pages   = array('', '');

$dynamic_url    = 'http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING']; // requested dynamic page (full url)
$cache_file     = $cache_folder.md5($dynamic_url).$cache_ext; // construct a cache file
$ignore = (in_array($dynamic_url,$ignore_pages))?true:false; //check if url is in ignore list

if (!$ignore && file_exists($cache_file) && time() - $cache_time < filemtime($cache_file)) { //check Cache exist and it's not expired.
    ob_start('ob_gzhandler'); //Turn on output buffering, "ob_gzhandler" for the compressed page with gzip.
    readfile($cache_file); //read Cache file
    echo '<!-- cached page - '.date('l jS \of F Y h:i:s A', filemtime($cache_file)).', Page : '.$dynamic_url.' -->';
    ob_end_flush(); //Flush and turn off output buffering
    exit(); //no need to proceed further, exit the flow.
}
//Turn on output buffering with gzip compression.
ob_start('ob_gzhandler'); 
######## Your Website Content Starts Below #########
?>

<html>
<head>
<meta charset="UTF-8"> <!-- Make sure you are using UTF-8 for special character compatibility -->
<title>malEP List</title>

<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<?php
// This script will pull all Anime shows on your watching list that are currently airing and display them in a nice list as links.
// The links go to search results on tokyotosho
//
//TODO
// - Caching the page to an html file and loading that first.


require "classes/mal.class.php";
$malAPI = new malAPI("DeathfireD");
$getData = $malAPI->getAnimeList();
$userInfo = $getData["UserInfo"];
$animeInfo = $getData["Anime"];

///////////////////////////////
// CONFIG
///////////////////////////////

//These Settings Apply to shows airing
$displayCurrentlyAiring = true;

//These settings only Apply to shows you've started
$displayShowsStarted = true; //Display shows you've started watching
$epCount = 4; //Only display started shows where you've watched at least this many episodes


echo "<h1>Anime List: " . $userInfo["Username"] . "</h1>\n";
echo "<p>&nbsp;</p>\n";
echo "<font size='5'>Watching (" . $userInfo["Watching"] . ")</font> <br>\n";

foreach ($animeInfo["Watching"] as $animeName => $animeData) {

	if ($displayCurrentlyAiring){
		if ($animeData["SeriesStatus"] < 2) {
			//echo "<a class='airing' href='https://www.tokyotosho.info/search.php?terms=" . $animeName . "&type=1'>" . $animeName . " (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . "</a> <br>\n";
			@$show .= "<tr>
						<td class='td1'>
							<a class='airing' href='https://www.tokyotosho.info/search.php?terms=" . $animeName . "&type=1' target='_blank'>" . $animeName . "</a>
						</td>
						<td class='td1' width='70'>
							". $animeData["EpisodesWatched"] . " / " . $animeData["Episodes"] . "
						</td>
						<td class='td1' width='70'>
							Airing
						</td>
					</tr>";
		}
	}

	if ($displayShowsStarted) {
		if ($animeData["EpisodesWatched"] > $epCount && $animeData["SeriesStatus"] > 1) {
			//echo "<a class='shouldWatch' href='https://www.tokyotosho.info/search.php?terms=" . $animeName . "&type=1'>" . $animeName . " (" . $animeData["EpisodesWatched"] . "/" . $animeData["Episodes"] . "), Score: " . $animeData["Score"] . "</a> <br>\n";
			@$show .= "<tr>
						<td class='td1'>
							<a class='shouldWatch' href='https://www.tokyotosho.info/search.php?terms=" . $animeName . "&type=1' target='_blank'>" . $animeName . "</a>
						</td>
						<td class='td1' width='70'>
							". $animeData["EpisodesWatched"] . " / " . $animeData["Episodes"] . "
						</td>
						<td class='td1' width='70'>
							Finished
						</td>
					</tr>";
		}
	}

}
?>

<table border="0" cellpadding="0" cellspacing="0" width="40%">
	<tbody>
		<tr>
			<td class="table_header">
				<strong>Anime Title</strong>
			</td>

			<td class="table_header" width="70" align="center" nowrap="">
				<strong>Progress</strong>
			</td>
			
			<td class="table_header" width="70" align="center" nowrap="">
				<strong>Status</strong>
			</td>
		</tr>
		<?php echo $show; ?>
	</tbody>
</table>

<?php
echo "<font size='5'><u>Plan To Watch (" . $userInfo["Planned"] . ")</u></font> <br>\n";

foreach ($animeInfo["Planned"] as $animeName => $animeData) {

	if ($displayCurrentlyAiring){
		if ($animeData["SeriesStatus"] < 2) {
			@$plan2WatchShows .= "<tr>
						<td class='td1'>
							<a class='airing' href='https://www.tokyotosho.info/search.php?terms=" . $animeName . "&type=1' target='_blank'>" . $animeName . "</a>
						</td>
						<td class='td1' width='70'>
							". $animeData["EpisodesWatched"] . " / " . $animeData["Episodes"] . "
						</td>
						<td class='td1' width='70'>
							Airing
						</td>
					</tr>";
		}
	}

}

?>
<table border="0" cellpadding="0" cellspacing="0" width="40%">
	<tbody>
		<tr>
			<td class="table_header">
				<strong>Anime Title</strong>
			</td>

			<td class="table_header" width="70" align="center" nowrap="">
				<strong>Progress</strong>
			</td>
			
			<td class="table_header" width="70" align="center" nowrap="">
				<strong>Status</strong>
			</td>
		</tr>
		<?php echo $plan2WatchShows; ?>
	</tbody>
</table>

</body>
</html>

<?php
######## Your Website Content Ends here #########

if (!is_dir($cache_folder)) { //create a new folder if we need to
    mkdir($cache_folder);
}
if(!$ignore){
    $fp = fopen($cache_file, 'w');  //open file for writing
    fwrite($fp, ob_get_contents()); //write contents of the output buffer in Cache file
    fclose($fp); //Close file pointer
}
ob_end_flush(); //Flush and turn off output buffering

?>