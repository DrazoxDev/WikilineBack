<?php
namespace App\DataFixtures;

use Psr\Log\LoggerInterface;

class AppleITune{
	//private $api_endpoint = "https://itunes.apple.com" ;
	private string $api_endpoint = "https://la-mmi-ac.univ-lemans.fr/apple" ;
    private string $clientId = "";
	private string $clientSecret = "" ;
	private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger){
		$this->logger = $logger;
    }
	
    /**
     * Search albums by artist
     *
     * @param string $artist
     *            
     * @return array $albums
     */
    public function searchAlbumsByArtist(string $artist): array
	{
		// https://itunes.apple.com/search?term=U2&media=music&entity=album
		// Pb, il faut passer par un proxy
		$url = $this->api_endpoint . "/search?term=" . urlencode($artist) . "&media=music&entity=album";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) ;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		
		curl_close($ch);
		
		$results = json_decode($response) ;
		
		$albums = array() ;
		if(isset($results->results)) { // { "resultCount":0, "results": []}
			foreach($results->results as $result) {
				if($result->wrapperType === "collection" && $result->collectionType === "Album") {
					$album = new \stdClass();
					$album->id = $result->collectionId ;
					$album->name = $result->collectionName ;
					$albums[] = $album ;
				}
			}
		}

		return $albums;
	}
	
	/**
     * Search tracks by album
     *
     * @param string $idAlbum
     *            
     * @return array $tracks
     */
	public function searchTracksByAlbum(string $idAlbum): array
	{	
		// https://itunes.apple.com/lookup?id=1440857781&entity=song
		$url = $this->api_endpoint . "/lookup?id=" . $idAlbum . "&entity=song" ;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) ;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);
		
		$results = json_decode($response) ;
				
		$tracks = array() ;
		if(isset($results->results)) { // { "resultCount":0, "results": []}
			foreach($results->results as $result) {
				if($result->wrapperType === "track") {
					$track = new \stdClass();
					$track->name = $result->trackName ;
					$track->preview_url = $result->previewUrl ;
					$tracks[] = $track ;
				}
			}
		}

		return $tracks;
	}
}
?>