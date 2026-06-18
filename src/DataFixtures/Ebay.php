<?php
namespace App\DataFixtures;

use Psr\Log\LoggerInterface;

class Ebay{
	//private string $url_base = "https://api.ebay.com";
	private string $url_base = "https://la-mmi-ac.univ-lemans.fr";
	private string $url_oauth = "/identity/v1/oauth2/token";
	private string $uri_finding = "/buy/browse/v1/item_summary/search";
	private string $uri_item = "/buy/browse/v1/item";
	// https://developer.ebay.com/signin?tab=register
    private string $appid = "";
	private string $certid = "" ;
	private string $devid = "" ;
    private ?string $oauthToken;
	private string $auth_token = "" ;
	private string $categoryId ;
	private string $cache_getitem_reponse ;
	private int $cache_getitem_last_itemid ;
	private LoggerInterface $logger;
	private string $globalId = "EBAY-FR" ;

    public function __construct(LoggerInterface $logger){
		$this->logger = $logger;
		$this->url_oauth = $this->url_base . $this->url_oauth ;
		$this->uri_finding = $this->url_base . $this->uri_finding ;
		$this->uri_item = $this->url_base . $this->uri_item ;
        $this->oauthToken = $this->getOauthToken();
		$this->setCategory() ;
    }
	
    /**
     * Set Category
     *
     * @param string $category
     *            
     * @return Ebay
     */
    public function setCategory(string $category = "CDs"): Ebay
	{
		$this->categoryId = $this->getParentCategoryIdByName($category) ;
		
		return $this;
	}
	
	/**
     * Get id of the parent category
     *
     * @param string $category
     *            
     * @return id
     */
    protected function getParentCategoryIdByName(string $category = "CDs"): int
	{
		// https://pages.ebay.fr/categorychanges/
		if($category == "Books" && $this->globalId == "EBAY-US") {
			$categoryId = 265 ; // Books EBAY-US !
		}
		if($category == "Livres" && $this->globalId == "EBAY-FR") {
			$categoryId = 267 ; // Livres EBAY-FR
		}
		if($category == "CDs" && $this->globalId == "EBAY-US") {
			$categoryId = 11233 ; // Books EBAY-US !
		}
		if($category == "CDs" && $this->globalId == "EBAY-FR") {
			$categoryId = 11233 ; // Livres EBAY-FR
		}
		if($category == "Vinyles" && $this->globalId == "EBAY-FR") {
			$categoryId = 176985 ; // Livres EBAY-FR
		}
		if($category == "Cassettes audio" && $this->globalId == "EBAY-FR") {
			$categoryId = 176983 ; // Livres EBAY-FR
		}
		
		return $categoryId;
	}
	
	/**
     * Search a category in list of categories
     *
     * @param array $categories
     * @param string|int $category
     *            
     * @return true|false
     */
    public function categoryInCategories($category, array $categories): bool
	{
		$categoryId = $this->categoryId ;
		if(isset($category)) {
			if(is_int($category)) {
				$categoryId = $category ;
			}
			if(is_string($category)) {
				$categoryId = $this->getParentCategoryIdByName($category) ;
			}
		}
		$return = false ;
		foreach($categories as $category) {
			if($categoryId === (int)$category["categoryId"]) {
				$return = true ;
			}
		}
		
		return $return;
	}
    
    /**
    * Get token
    *
    * @return string of the token
    */
    private function getOauthToken(): ?string 
	{
		$post_data = array("grant_type" => "client_credentials", "scope" => "https://api.ebay.com/oauth/api_scope");	
		$post_fields = http_build_query($post_data) ;
				   
        $hash = base64_encode($this->appid . ':' . $this->certid);
        $response = $this->curl($this->url_oauth, "POST", array('Authorization: Basic ' . $hash), $post_fields);

		if($response == null) {
			$this->oauthToken = null ;
			return null ;
		}
		else {
			$this->oauthToken = json_decode($response)->access_token ;
			return json_decode($response)->access_token ;
		}
    }
    
    /**
    * Find items of products
    * Allows you to search for eBay products based on keywords and id category.  
    *
    * @param string $keywords
    * @param int $entries_per_page
	*
    * @return array $result
    */
    public function searchItemSummaries(string $keywords = 'Harry Potter', int $entries_per_page = 3): array
	{
		// https://developer.ebay.com/api-docs/buy/browse/resources/item_summary/methods/search
		$post_data = array("q" => $keywords, "limit" => $entries_per_page, "category_ids" => $this->categoryId);	
		$get_fields = "?" . http_build_query($post_data) ;
		$headers = array(
			'X-EBAY-C-MARKETPLACE-ID: ' . $this->globalId,
		);
		
		$response = $this->curl($this->uri_finding, "GET", $headers, $get_fields) ;

		if($response == null) {
			return array() ;
		}
		else {
			$data = json_decode($response, true);
			
			if(isset($data['errors'])) {
				throw new \Exception("[Ebay][error]:" . implode(":", $data['errors'][0]));
			}
			if(count($data["itemSummaries"]) > 0) {
				return $data["itemSummaries"];
			}
		}
		return array() ;
    }
	
    /**
    * Get Item
    * Obtains item specific
    * 
    * @param string $name
    * @param string or int $itemId (specific element id)
	*
    * @return string $result (value of specific element)
    */
    public function getItem(string $name = 'Auteur', string $itemId = '114866016241'): ?string
	{
		// https://developer.ebay.com/api-docs/buy/browse/resources/item/methods/getItem
		$result = "" ;
		$itemId = "v1|" . $itemId . "|0" ;
		$headers = array(
			'X-EBAY-C-MARKETPLACE-ID: ' . $this->globalId,
		);
		
		if(isset($this->cache_getitem_last_itemid)) {
			if($this->cache_getitem_last_itemid == $itemId){
				$response = $this->cache_getitem_reponse ; 
			}
			else {
				$response = $this->curl($this->uri_item, "GET", $headers, '/' . urlencode($itemId)) ;
			}
		}
		else {
			$response = $this->curl($this->uri_item, "GET", $headers, '/' . urlencode($itemId)) ;
		}
		
		if($response == null) {
			return null ;
		}
		else {
			$data = json_decode($response, true);
			if(isset($data['errors'])) {
				throw new \Exception("[Ebay][error]:" . implode(":", $data['errors'][0]));
			}
			if(isset($data["localizedAspects"]) && (count($data["localizedAspects"]) > 0)) {
				foreach($data["localizedAspects"] as $localizedAspect) {
					if($localizedAspect["name"] == $name) {
						$result = $localizedAspect["value"] ;
					}
				}
			} 
			if ($result == "") {	
				foreach($data as $key => $value) {
					if(strtolower($key) == strtolower($name)) {
						$result = $value ;
					}
				}
			}
		}
		return $result ;
    }

    /**
    * cURL
    * Standard cURL function to run GET & POST requests
    * 
    * @param string $url
    * @param string $method
    * @param array $headers
    * @param string $postvals
	*
    * @exception string
    * @return string $response
    */
    private function curl(string $url, string $method = 'GET', array $headers = null, string $get_port_vals = null, $verbose = false): ?string
	{
        $ch = curl_init();
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false) ;
        if ($method == 'GET'){
			if($this->oauthToken != null) $headers[] = 'Authorization:Bearer ' . $this->oauthToken ;
			if($get_port_vals != null) $url = $url . $get_port_vals ;
			$options = array(
				CURLOPT_URL => $url,
			    CURLOPT_HTTPGET => true,
                CURLOPT_VERBOSE => $verbose,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION=> true,
                CURLOPT_TIMEOUT => 20
            );
        } else {
            $options = array(
				CURLOPT_URL => $url,
			    CURLOPT_POST => true,
                CURLOPT_HEADER => false,
                CURLINFO_HEADER_OUT => true,
                CURLOPT_VERBOSE => $verbose,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION=> true,
                CURLOPT_POSTFIELDS => $get_port_vals,
                CURLOPT_TIMEOUT => 20
            );

        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
		$erreur = "" ;
		if (curl_errno($ch)) {
			$erreur = curl_error($ch);
		}
        curl_close($ch);

		if ($erreur != "") {
			$this->logger->error($erreur) ;
			return null;
		}	
		else {
			return $response;
		}
    }
}
?>