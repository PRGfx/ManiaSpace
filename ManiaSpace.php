<?php
namespace ExtendedWebservices;

/**
 * @author zocka
 * @version 0.8
 */
class ManiaSpace{

  private $url = "http://maniaspace.maniastudio.com/";

	/**
	 * This class will give you the ability to return basic information from the ManiaSpace manialink (tmtp:///:maniaspace).
	 * You should consider caching the data locally instead of requesting it everytime a user reloads the site. This will decrease
	 * loading times, the traffic for the NADEO service and you might be able to get the data from someone else if you don't
	 * have cUrl enabled.
	 *
	 * NOTICE:
	 * This class requires cUrl to be activated!
	 *
	 * @throws Exception if cUrl is not enabled
	 */
	public function __construct(){
		if(!function_exists("curl_init"))
			throw new Exception("cUrl has to be enabled!");
	}

	/**
	 * Returns the xml data behind an url, called with the TMF useragent "GameBox".
	 *
	 * @param string $url the url you want to call
	 * @return String content of the called page
	 */
	private function getData($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "GameBox");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		return curl_exec($ch);
	}

	/**
	 * Returns the track ids of every track associated with the given user.
	 *
	 * @param string $account Account of the uploader. If left empty, it will return the 15 latest tracks on ManiaSpace
	 * @return Array with the found track ids
	 */
	public function getTrackIDs($account = "latest"){
		$trackIDs = array();
		if(strtolower($account)!="latest"){
			$pex = true;
			for($i = 1; $pex; $i++){
				$u = $this->url.'?user='.$account.'&tp='.$i;
				$MLData = $this->getData($u);
				$tracks = $this->findTrackIDs($MLData);
				$trackIDs = array_merge($trackIDs, $tracks);
				$pex = $this->nextPageExists($MLData);
			}
		}else{
			$MLData = $this->getData($this->url);
			$trackIDs = $this->findTrackIDs($MLData);
		}
		return $trackIDs;
	}

	/**
	 * Parses the manialink for the track ids.
	 */
	private function findTrackIDs($subject){
		$found = array();
		preg_match_all('/map=([0-9]*)/', $subject, $tracks);
		foreach($tracks[1] as $k=>$v)
		{
			$found[]=$v;
		}
		return $found;
	}

	/**
	 * Returns the basic track data of every track associated with the given user.
	 *
	 * @param string $account Account of the uploader. If left empty, it will return the 15 latest tracks on ManiaSpace
	 * @return Array with the found track data. Each hit will give you an array with id, name, imageurl,
	 * 	link to the track on ManiaSpace and link to the maniacode to download the track.
	 */
	public function getTracks($account = "latest"){
		$tracks = array();
		if(strtolower($account)!="latest"){
			$pex = true;
			for($i = 1; $pex; $i++){
				$u = $this->url.'?user='.$account.'&tp='.$i;
				$MLData = $this->getData($u);
				$tracks0 = $this->findTracks($MLData);
				$tracks = array_merge($tracks, $tracks0);
				$pex = $this->nextPageExists($MLData);
			}
		}else{
			$MLData = $this->getData($this->url);
			$tracks = $this->findTracks($MLData);
		}
		return $tracks;
	}

	/**
	 * Parses the manialink for the basic track data
	 *
	 * @return Array with id, name, imageurl, link to the track on ManiaSpace and link to the maniacode to download the track.
	 */
	private function findTracks($subject){
		$found = array();

		preg_match_all('/<quad(.*)details.php(.*)map=([0-9]*)(.*)<frame(.*)quad(.*)image=\"(.*)\"(.*)label(.*)text="(.*)"(.*)<\/frame>/Us', $subject, $tracks, PREG_SET_ORDER);

		foreach ($tracks as $key => $value) {
			$id = explode('"', $value[4]);
			$res["id"] = $id[0];
			$res["image"] = $value[7];
			if(substr($res["image"], 0, 2) == "./")
				$res["image"] = $this->url . substr($res["image"], 2);
			$res["name"] = $value[10];
			$res["donwload"] = "maniaspace:track?map=".$res["id"];
			$res["link"] = "maniaspace?map=".$res["id"];
			$found[]=$res;
		}

		return $found;
	}

	/**
	 * Oarses the manialink to look if there is a next page of track results.
	 *
	 * @return boolean true if another page exists, boolean false otherwise
	 */
	private function nextPageExists($subject){
		if(strpos($subject, "ArrowDown")!==false)
			return true;
		return false;
	}

	/**
	 * Returns the details of a track on ManiaSpace by it's id.
	 *
	 * @param string $id the trackid to search for
	 * @return Array with tons of information (name, author, imageurl, download link, ManiaSpace link, download count,
	 * environment, mood, type, laps, mod, comment, times, first 7 replays)
	 */
	public function getTrackDetails($id){
		$MLData = $this->getData($this->url.'details.php?map='.$id);
		preg_match('/TextTitle2\" text=\"(.*)\"/U', $MLData, $title);
		$title = explode(' ', $title[1]);
		// author
		$res["author"] = $title[count($title)-1];
		// map name
		unset($title[count($title)-1]);
		unset($title[count($title)-1]);
		unset($title[count($title)-1]);
		unset($title[count($title)-1]);
		$res["name"] = implode(" ", $title);
		// download counter
		preg_match('/Downloads: \$fff\$o(.*)\"/U', $MLData, $dl);
		$res["downloads"] = $dl[1];
		$res["download"] = "maniaspace:track?map=".$id;
		$res["link"] = "maniaspace?map=".$id;
		// info
		preg_match('/999Environment: (.*)\"/U', $MLData, $envi);
		$res["info"]["environment"] = substr($envi[1], 6);
		preg_match('/999Mood: (.*)\"/U', $MLData, $mood);
		$res["info"]["mood"] = substr($mood[1], 6);
		preg_match('/999Type: (.*)\"/U', $MLData, $type);
		$res["info"]["type"] = substr($type[1], 6);
		preg_match('/999Laps: (.*)\"/U', $MLData, $laps);
		$res["info"]["laps"] = substr($laps[1], 6);
		preg_match('/999Mod: (.*)\"/U', $MLData, $mod);
		$res["info"]["mod"] = substr($mod[1], 6);
		preg_match('/999Comment: (.*)\"/U', $MLData, $comment);
		$res["info"]["comment"] = substr($comment[1], 6);
		// image
		preg_match('/image=\"(\.\/download\/(.*))\"/U', $MLData, $image);
		$res["image"] = $image[1];
		if(substr($res["image"], 0, 2) == "./")
			$res["image"] = $this->url . substr($res["image"], 2);
		// times
		preg_match_all('/style=\"MedalsBig\"(.*)text=\"(.*)\"/Us', $MLData, $times);
		$res["times"]["author"] = $times[2][0];
		$res["times"]["gold"] = $times[2][1];
		$res["times"]["silver"] = $times[2][2];
		$res["times"]["bronze"] = $times[2][3];
		// replays
		preg_match_all('/manialink=\"maniaspace:replay(.*)text=\"([0-9])\"(.*)text=\"(.*)\"(.*)text="(.*)\"(.*)text=\"(.*)\"/Us', $MLData, $replays, PREG_SET_ORDER);
		foreach($replays as $i => $replay){
			$res["replays"][$i]["rank"] = $replay[2];
			$res["replays"][$i]["nickname"] = $replay[4];
			$res["replays"][$i]["playerlogin"] = $replay[6];
			$res["replays"][$i]["time"] = $replay[8];
		}

		return $res;
	}
}
?>
