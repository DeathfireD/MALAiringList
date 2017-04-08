<?php
/*
 * @author ebildude123
 * @license GNU Lesser General Public License <https://www.gnu.org/licenses/lgpl.html>
 */
 
define("COMPLETED_STATUS", 2);
define("PLANNED_STATUS", 6);
define("WATCHING_STATUS", 1);
define("DROPPED_STATUS", 4);
define("ON_HOLD_STATUS", 3);

define("ENTRY_ALREADY_EXISTS", 5000); // anime entry already exists
define("SERVER_SQL_ERROR", 5001); // server returned mysql error, usually means one of the arguments you supplied are invalid (e.g. anime id)
 
class malAPI
{
	private $curUser;
	private $curPass;
	
	public function __construct($username, $password = "") {
		$this->changeUser($username, $password);
	}
	
	public function changeUser($username, $password = "") {
		$username = trim($username);
		$this->curUser = $username;
		$this->curPass = $password;
	}	
	
	public function getAnimeList() {
		$urlPath = "http://myanimelist.net/malappinfo.php?u=" . $this->curUser . "&status=all&type=anime";
		$getList = $this->loadPage($urlPath);
		$getList = trim($getList);
		if ($getList == "") {
			trigger_error("Error fetching data from myanimelist", E_USER_ERROR);
		}
		elseif (strstr($getList, "/_Incapsula_Resource")) {
			trigger_error("Incapsula security present; cannot proceed", E_USER_ERROR);
		}
		else {
			$loadXML = simplexml_load_string($getList);			
			$loadXML = $this->XML2Array($loadXML);
			$dataArr = array();
			
			if (isset($loadXML["error"])) {
				$dataArr["Error"] = $loadXML["error"];
			}
			else {
				$dataArr["UserInfo"] = array(
					"Username" => $loadXML["myinfo"]["user_name"],
					"Watching" => $loadXML["myinfo"]["user_watching"],
					"Completed" => $loadXML["myinfo"]["user_completed"],
					"OnHold" => $loadXML["myinfo"]["user_onhold"],
					"Dropped" => $loadXML["myinfo"]["user_dropped"],
					"Planned" => $loadXML["myinfo"]["user_plantowatch"],
					"DaysSpentWatching" => $loadXML["myinfo"]["user_days_spent_watching"],
				);
				$dataArr["Anime"] = array();
				
				// Dirty workaround/fix in try brackets
				try {
					$loadXML["anime"][] = array(
						"series_animedb_id" => $loadXML["anime"]["series_animedb_id"],
						"series_title" => $loadXML["anime"]["series_title"],
						"series_episodes" => $loadXML["anime"]["series_episodes"],
						"my_watched_episodes" => $loadXML["anime"]["my_watched_episodes"],
						"series_status" => $loadXML["anime"]["series_status"],
						"my_score" => $loadXML["anime"]["my_score"],
						"my_status" => $loadXML["anime"]["my_status"],
					);
					
					unset($loadXML["anime"]["series_animedb_id"]);
					unset($loadXML["anime"]["series_title"]);
					unset($loadXML["anime"]["series_synonyms"]);
					unset($loadXML["anime"]["series_type"]);
					unset($loadXML["anime"]["series_episodes"]);
					unset($loadXML["anime"]["series_status"]);
					unset($loadXML["anime"]["series_start"]);
					unset($loadXML["anime"]["series_end"]);
					unset($loadXML["anime"]["series_image"]);
					unset($loadXML["anime"]["my_id"]);
					unset($loadXML["anime"]["my_watched_episodes"]);
					unset($loadXML["anime"]["my_start_date"]);
					unset($loadXML["anime"]["my_finish_date"]);
					unset($loadXML["anime"]["my_score"]);
					unset($loadXML["anime"]["my_status"]);
					unset($loadXML["anime"]["my_rewatching"]);
					unset($loadXML["anime"]["my_rewatching_ep"]);
					unset($loadXML["anime"]["my_last_updated"]);
					unset($loadXML["anime"]["my_tags"]);
				}
				catch (Exception $e) {
					// no anime list entries
				}
				
				$dataArr["Anime"]["Completed"] = array();
				$dataArr["Anime"]["Planned"] = array();
				$dataArr["Anime"]["Watching"] = array();
				$dataArr["Anime"]["Dropped"] = array();
				$dataArr["Anime"]["OnHold"] = array();
				
				foreach ($loadXML["anime"] as $animeObj) {
					$dataArr["Anime"][$this->animeStatusToText($animeObj["my_status"])][$this->removeUtf8($animeObj["series_title"])] = array(
						"AnimeID" => $animeObj["series_animedb_id"],
						"Episodes" => $animeObj["series_episodes"],
						"EpisodesWatched" => $animeObj["my_watched_episodes"],
						"SeriesStatus" => $animeObj["series_status"],
						"Score" => ($animeObj["my_score"] == 0) ? "n/a" : $animeObj["my_score"],
					);
				}
				
				uksort($dataArr["Anime"]["Completed"], 'strcasecmp');
				uksort($dataArr["Anime"]["Planned"], 'strcasecmp');
				uksort($dataArr["Anime"]["Watching"], 'strcasecmp');
				uksort($dataArr["Anime"]["Dropped"], 'strcasecmp');
				uksort($dataArr["Anime"]["OnHold"], 'strcasecmp');				
			}
			
			return $dataArr;
		}
	}
	
	public function addToAnimeList($animeID, $episodes /* # watched */, $status, $score = 0, $dl_eps = "" /* downloaded episodes (int) */, $date_start = "" /* format: mmddyyyy */, $date_finish = "" /* format: mmddyyyy */)
	{
		// Notes: If you set status to COMPLETED_STATUS, you can pass any number you want for $episodes
	
		if (!in_array($status, array(COMPLETED_STATUS, PLANNED_STATUS, WATCHING_STATUS, DROPPED_STATUS, ON_HOLD_STATUS))) {
			trigger_error("Invalid status code", E_USER_WARNING);
			return false;
		}
		
		if (intval($animeID) <= 0) {
			trigger_error("Invalid anime ID", E_USER_WARNING);
			return false;
		}
		
		$postUrl = "http://myanimelist.net/api/animelist/add/" . $animeID . ".xml";
		$postData = '<?xml version="1.0" encoding="UTF-8"?>
<entry>
	<episode>' . $episodes . ' </episode>
	<status>' . $status . '</status>
	<score>' . $score . '</score>
	<downloaded_episodes>' . $dl_eps . '</downloaded_episodes>
	<storage_type></storage_type>
	<storage_value></storage_value>
	<times_rewatched></times_rewatched>
	<rewatch_value></rewatch_value>
	<date_start>' . $date_start . '</date_start>
	<date_finish>' . $date_finish . '</date_finish>
	<priority></priority>
	<enable_discussion></enable_discussion>
	<enable_rewatching></enable_rewatching>
	<comments></comments>
	<fansub_group></fansub_group>
	<tags></tags>
</entry>';
		
		$sendData = $this->loadPage($postUrl, true, true, http_build_query(array("data" => $postData)));
		if ($sendData === false) {
			return false;
		}
		elseif (strstr($sendData, 'MySQL Error. The staff has been notified')) {
			return SERVER_SQL_ERROR;
		}
		elseif (strstr($sendData, '<h1>Created</h1>')) {
			return true;
		}
		elseif (strstr($sendData, 'anime is already on your list')) {
			return ENTRY_ALREADY_EXISTS;
		}
		else {
			return false;
		}
	}
	
	public function searchForAnimeById($id) {
		$id = (int) trim($id);
		$getUrl = "http://myanimelist.net/anime.php?id=" . $id;
		$getData = $this->loadPage($getUrl);
		$getData = $this->removeUtf8($getData);
		if (strpos($getData, '<div class="badresult">') !== false) {
			return false;
		}		
		
		preg_match('/<div style="float: right; font-size: 13px;">Ranked #(\d+)<\/div>([^<]*)<\/h1>/', $getData, $results);
		$animeTitle = $results[2];
		$searchAnime = $this->searchForAnime($animeTitle);
		foreach ($searchAnime as $animeName => $animeArr) {
			if (($animeName != $animeTitle) && ($animeArr["AnimeID"] != $id)) {
				unset($searchAnime[$animeName]);
			}
		}
		$searchAnime[$animeName]["Ranking"] = $results[1];
		return $searchAnime;
	}
	
	public function searchForAnime($name) {
		$getUrl = "http://myanimelist.net/api/anime/search.xml?" . http_build_query(array("q" => $name));
		$getData = $this->loadPage($getUrl, true);

		if (strpos($getData, "No results") !== false || trim($getData) == null) {
			return array();
		}
		
		$getData = str_replace("&mdash;", "-", $getData);
		$loadXML = simplexml_load_string($getData);
		$sArr = $this->XML2Array($loadXML);
		$resArr = array();
		
		// Dirty workaround/fix in try brackets
		try {
			$sArr["entry"][] = array(
				"id" => $sArr["entry"]["id"],
				"title" => $sArr["entry"]["title"],
				"english" => $sArr["entry"]["english"],
				"episodes" => $sArr["entry"]["episodes"],
				"score" => $sArr["entry"]["score"],
				"type" => $sArr["entry"]["type"],
				"status" => $sArr["entry"]["status"],
				"start_date" => $sArr["entry"]["start_date"],
				"end_date" => $sArr["entry"]["end_date"],
				"synopsis" => $sArr["entry"]["synopsis"],
			);
				
			unset($sArr["entry"]["id"]);
			unset($sArr["entry"]["title"]);
			unset($sArr["entry"]["english"]);
			unset($sArr["entry"]["episodes"]);
			unset($sArr["entry"]["synonyms"]);
			unset($sArr["entry"]["score"]);
			unset($sArr["entry"]["type"]);
			unset($sArr["entry"]["status"]);
			unset($sArr["entry"]["start_date"]);
			unset($sArr["entry"]["end_date"]);
			unset($sArr["entry"]["synopsis"]);
			unset($sArr["entry"]["image"]);
		}
		catch (Exception $e) {}
		
		
		foreach ($sArr["entry"] as $entry) {
			$resArr[$this->removeUtf8($entry["title"])] = array(
				"AnimeID" => $entry["id"],
				"English" => (trim($entry["english"]) != null) ? $entry["english"] : "n/a",
				"Episodes" => $entry["episodes"],
				"AvgScore" => $entry["score"],
				"Type" => $entry["type"],
				"Status" => $entry["status"],
				"StartDate" => $this->fixDateFormat($entry["start_date"]),
				"EndDate" => $this->fixDateFormat($entry["end_date"]),
				"Summary" => strip_tags($entry["synopsis"])
			);
		}
		return $resArr;
	}
	
	public function getAnimeRecs($id) { //Get anime recommendations: requires the anime ID number passed
		$getStr = http_build_query(array(
			"id" => $id,
			"display" => "userrecs"
		));		
		$getUrl = "http://myanimelist.net/anime.php?" . $getStr;
		$recData = $this->loadPage($getUrl);
		$recData = substr($recData, strpos($recData, "<h2>Recommendations Submitted by Users</h2>"));
		$recData = $this->removeUtf8($recData, true);
		preg_match_all('/http:\/\/([^\s]+)/', $recData, $recRaw);
		$recRaw = $recRaw[0];
		foreach ($recRaw as $rK => $rE) {
			$sW = "http://myanimelist.net/anime/";
			if (substr($rE, 0, strlen($sW)) != $sW) {
				unset($recRaw[$rK]);
			}
			else {
				$rEFix = trim($rE, '"');
				$dbqLoc = strpos($rEFix, '"');
				if ($dbqLoc !== false) {
					$rEFix = substr($rEFix, 0, $dbqLoc);
				}
				$recRaw[$rK] = $rEFix;
			}
		}
		$recRaw = array_unique($recRaw);
		$recRaw = array_values($recRaw);
		
		$resArr = array();
		
		foreach ($recRaw as $animeUrl) {
			$animeArr = explode("/", $animeUrl);
			$animeID = $animeArr[4];
			$animeName = $this->removeUtf8(str_replace("_", " ", $animeArr[5]));
			$resArr[$animeName] = array(
				"AnimeID" => $animeID,
				"Url" => str_replace(" ", "_", $animeUrl)
			);
		}
		
		return $resArr;
	}
	
	public function getTopAnime($type = "tv" /* types: leave blank for all (""), tv, movie, ova, special, bypopularity */, $startLoc = 1 /* Position to start top list from */) {
		$startLoc -= 1;
		$getStr = http_build_query(array(
			"type" => $type,
			"limit" => $startLoc
		));
		$getUrl = "http://myanimelist.net/topanime.php?" . $getStr;
		$recData = $this->loadPage($getUrl);
		$recData = $this->startAtStr($recData, "<h1>Top Anime</h1>");
		$recData = $this->endAtStr($recData, '<div class="spaceit">');
		$recData = $this->removeUtf8($recData);
		
		preg_match_all('/http:\/\/myanimelist\.net\/anime\/([^"]*)/', $recData, $recRaw); // not a regex pro here, but this works very well :p
		$recRaw = $recRaw[0];
		$recRaw = array_unique($recRaw);
		$resArr = array();
		foreach ($recRaw as $animeUrl) {
			$animeArr = explode("/", $animeUrl);
			$animeID = $animeArr[4];
			$animeName = $this->removeUtf8(str_replace("_", " ", $animeArr[5]));
			$startLoc++;
			$resArr[$animeName] = array(
				"AnimeID" => $animeID,
				"Url" => str_replace(" ", "_", $animeUrl),
				"Position" => $startLoc
			);
		}
		
		return $resArr;
	}
	
	public function getFavoriteCharacters() {
		$getUrl = "http://myanimelist.net/profile/" . $this->curUser;
		$profileData = $this->loadPage($getUrl);
		$characters = array();
		preg_match_all('/<td class="borderClass" valign="top"><a href="\/character\/(\d+)\/([^"]*)">([^<]*)<\/a>' . "\n" . '			<div style="padding-top: 2px;"><a href="\/anime\/(\d+)" class="lightLink">([^<]*)<\/a><\/div><\/td>/', $profileData, $charArr);
		foreach ($charArr[3] as $charIndex => $char) {
			$characters[$char] = array(
				"CharacterID" => $charArr[1][$charIndex],
				"CharacterLink" => "http://myanimelist.net/character/" . $charArr[1][$charIndex] . "/" . $charArr[2][$charIndex],
				"AnimeID" => $charArr[4][$charIndex],
				"Anime" => $charArr[5][$charIndex]
			);
		}
		return $characters;
	}
	
	private function startAtStr($origStr, $strFind) {
		return substr($origStr, strpos($origStr, $strFind) + strlen($strFind));
	}
	
	private function endAtStr($origStr, $strFind) {
		return substr($origStr, 0, strrpos($origStr, $strFind));
	}
	
	private function fixDateFormat($yyyymmdd) {
		if ($yyyymmdd == "0000-00-00") {
			return "Unknown";
		}
		$spArr = explode("-", $yyyymmdd);
		if ($spArr[2] == "00") {
			return $spArr[0];
		}
		$monthStr = date("F", mktime(0, 0, 0, $spArr[1], 10));
		return $monthStr . " " . $spArr[2] . ", " . $spArr[0];
	}
	
	private function removeUtf8($str, $onlyutf8 = false) {
		$fxStr = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $str);
		if ($onlyutf8 === true) {
			return $fxStr;
		}
		$fxStr = preg_replace('/\s+/', ' ', $fxStr);
		return $fxStr;
	}
	
	private function animeStatusToText($status)
	{
		if ($status == COMPLETED_STATUS) {
			return "Completed";
		}
		elseif ($status == PLANNED_STATUS) {
			return "Planned";
		}
		elseif ($status == WATCHING_STATUS) {
			return "Watching";
		}
		elseif ($status == DROPPED_STATUS) {
			return "Dropped";
		}
		elseif ($status == ON_HOLD_STATUS) {
			return "OnHold";
		}
	}
	
	private function loadPage($url, $needAuth = false, $sendPost = false, $postData = "") {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_REFERER, "http://myanimelist.net/");
		curl_setopt($ch, CURLOPT_USERAGENT, 'malAPI class for PHP (github/ebildude123/malAPI)'); //Do not change this!
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		if ($needAuth === true) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->curUser . ":" . $this->curPass);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		if ($sendPost === true) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		}
		$data = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);		
		if ($needAuth === true && $info["http_code"] == 401) {
			trigger_error("Unable to authenticate; incorrect username or password", E_USER_WARNING);
			return false;
		}
		return $data;
	}
	
	private function XML2Array(SimpleXMLElement $parent)
	{
		$array = array();

		foreach ($parent as $name => $element) {
			($node = & $array[$name])
				&& (1 === count($node) ? $node = array($node) : 1)
				&& $node = & $node[];

			$node = $element->count() ? $this->XML2Array($element) : trim($element);
		}

		return $array;
	}
	
}
?>