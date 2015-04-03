<?php 
require_once("./../utility/inc/config_telephone.inc");
include_once("./../utility/scripts/simplehtmldom/simple_html_dom.php");
//require_once("./../../utility/current/inc/config_telephone.inc");
//include_once("./../../utility/current/scripts/simplehtmldom/simple_html_dom.php");

/* ===================================================================
This scanner search over Google Places considering an specific search string, in this version the searches supported are:
By Telephone Number
http://10.66.12.193:8100/google_search.php?telephone=7063562877
By City and State and business name
http://10.66.12.193:8100/google_search.php?city=Houston&state=texas&name=sushi
By Address and business name
http://10.66.12.193:8100/google_search.php?address=2625 Louisiana St, Houston,TX&name=sushi
By Zipcode and business name
http://10.66.12.193:8100/google_search.php?zipcode=77001&name=sushi
By Latitude and Longitude and business name
http://10.66.12.193:8100/google_search.php?name=Lockport Animal Hospital&latitude=41.5971298&longitude=-88.0346222

Last Change: Carlos Medina -  03-04-2015 Add key to address search to get lat, lng 
=================================================================== */

$search_method = 'telephone';
//Call to broker
$broker_result = curl("http://fg-dev2-test1:3777/startrequest/google_keyword_scan");

if (isset($_REQUEST['name'])) {
	$business_name = $_REQUEST['name'];
} else {
	$business_name = '';
}

if (isset($_REQUEST['city'])) {
	$city = 	$_REQUEST['city'];
} else {
	$city = '';
}

if (isset($_REQUEST['state'])) {
	$state = 	$_REQUEST['state'];
} else {
	$state = '';
}
if (isset($_REQUEST['country'])) {
	$country = 	$_REQUEST['country'];
} else {
	$country = 'US';
}

if (isset($_REQUEST['radius'])) {
	$radius = 	$_REQUEST['radius'];
} else {
	$radius = '5000';
}

if (isset($_REQUEST['address'])) {
	$address = 	$_REQUEST['address'];
} else {
	$address = '';
}

if (isset($_REQUEST['zipcode'])) {
	$zipcode = 	$_REQUEST['zipcode'];
} else {
	$zipcode = '';
}
//Telephone to search in 10 digits format
if (isset($_REQUEST['telephone'])) {
	$telephone_input = formatPhone($_REQUEST['telephone']);
} else {
	$telephone_input = '';
}

if (isset($_REQUEST['textsearch'])) {
	$textsearch = 	$_REQUEST['textsearch'];
	$search_method = 'textsearch';
	//echo $textsearch;
} else {
	$textsearch = '';
}

// Latitude to search 
if (isset($_REQUEST['latitude'])) {
	$lat = $_REQUEST['latitude'];
} else {
	$lat = '';
}
// Longitude to search
if (isset($_REQUEST['longitude'])) {
	$lng = $_REQUEST['longitude'];
} else {
	$lng = '';
}

// Limiter to prevent the bot response get stuck. If bot found this much stop at that point. The limit is 100.
if (isset($_REQUEST['result_found_limit'])) {
	$result_found_limit = 	$_REQUEST['result_found_limit'];
} else {
	$result_found_limit = '100';
}

if ($result_found_limit>100){
	$result_found_limit=100;
}


// log file output
	$log_path = './log/';
	if (!file_exists($log_path)) mkdir($log_path);
	$output_filename = $log_path . "google_search-" . Date("Ymd_His") . '.txt';
	$fp_log = fopen($output_filename, 'w');

	$index_key=rand(0,7);
	$api_key_google=$api_key[$index_key];

	$line = "# of Key to get lat/lng in case is needed: " . $index_key . "\r\n";
	fwrite($fp_log, $line);
	$line = "Key  to get lat/lng in case is needed: " . $api_key_google . "\r\n";
	fwrite($fp_log, $line);

	$line = "Request: " . implode($_REQUEST) . "\r\n";
	fwrite($fp_log, $line);

if (strlen($address)==0){
	if (strlen($zipcode)>0){
		$address_to_search=$zipcode;
		$components="&components=country:".$country . "|postal_code:" . $zipcode;
		$coordinates = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address_to_search) . $components . '&sensor=true');
		$line = "Get lat/lng: " . implode($coordinates) . "\r\n";
		fwrite($fp_log, $line);
		$coordinates = json_decode($coordinates);
		$status=$coordinates->status;
		if ($status=="OK"){
			$lat=$coordinates->results[0]->geometry->location->lat;
			$lng=$coordinates->results[0]->geometry->location->lng;
		}
		else{
				$status = "GEOCODEAPI::ZIPCODE_SEARCH::".$status;
				$response_data =  array(
				 'status' =>$status
				);
				$json_response= json_encode($response_data);
				$line = "Request: " . $status . "\r\n";
				fwrite($fp_log, $line);
				fclose($fp_log);
				echo $json_response;
				$broker_result_end = curl("http://fg-dev2-test1:3777/endrequest/google_keyword_scan");
				exit;
		}
		$search_method='zipcode';

	} else
	if (strlen($city)>0 && strlen($state)>0){
		$address_to_search=$city . "," . $state;
		$components="&components=country:".$country;
		$coordinates = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address_to_search) . $components . '&sensor=true');
		$line = "Get lat/lng: " . implode($coordinates) . "\r\n";
		fwrite($fp_log, $line);
		$coordinates = json_decode($coordinates);
		$status=$coordinates->status;
		if ($status=="OK"){
			$lat=$coordinates->results[0]->geometry->location->lat;
			$lng=$coordinates->results[0]->geometry->location->lng;
		}
		else{
				$status = "GEOCODEAPI::CITYSTATE_SEARCH::".$status;
				$response_data =  array(
				 'status' =>$status
				);
				$json_response= json_encode($response_data);
				$line = "Request: " . $status . "\r\n";
				fwrite($fp_log, $line);
				fclose($fp_log);
				echo $json_response;
				$broker_result_end = curl("http://fg-dev2-test1:3777/endrequest/google_keyword_scan");
				exit;
		}
		$search_method='city_state';
	}else
	if (strlen($telephone_input)>0){
		$search_method='telephone';
	}else{
			if (strlen($lat)>0 && strlen($lng)>0){
				$search_method='lat_lng';
			}else
				if (strlen($textsearch)>0){
					$search_method='textsearch';
				}
				else{
					$status = "GEOCODEAPI::EMPTY_SEARCH::";
					$response_data =  array(
					 'status' =>$status
					);
					$json_response= json_encode($response_data);
					$line = "Request: " . $status . "\r\n";
					fwrite($fp_log, $line);
					fclose($fp_log);
					echo $json_response;
					$broker_result_end = curl("http://fg-dev2-test1:3777/endrequest/google_keyword_scan");
					exit;
				}
	}

}else
{
		$address_to_search=$address;
		$components="&components=country:".$country;
		$address_url= "https://maps.googleapis.com/maps/api/geocode/json?key=" . $api_key_google . "&address=" . urlencode($address_to_search) . $components . "&sensor=true";

		//$coordinates = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address_to_search) . $components . '&sensor=true');
		$coordinates = file_get_contents($address_url);
		$line = "Get lat/lng: " . implode($coordinates) . "\r\n";
		fwrite($fp_log, $line);
		$coordinates = json_decode($coordinates);
		$status=$coordinates->status;
		if ($status=="OK"){
			$lat=$coordinates->results[0]->geometry->location->lat;
			$lng=$coordinates->results[0]->geometry->location->lng;
		}
		else{
				$status = "GEOCODEAPI::ADDRESS_SEARCH::".$status;
				$response_data =  array(
				 'status' =>$status
				);
				$json_response= json_encode($response_data);
				$line = "Request: " . $status . "\r\n";
				fwrite($fp_log, $line);
				fclose($fp_log);
				echo $json_response;
				$broker_result_end = curl("http://fg-dev2-test1:3777/endrequest/google_keyword_scan");
				exit;
		}
		$search_method='address';
}

	
	$result_found = 0;
	$num_written_in_db = 0;
	$num_updates_in_db = 0;
	$num_inserts_in_db = 0;
	$is_last_batch_run = 0; // Flag to check if this is the last batch run or not.
	$api_over_query_limit = 0;
	$searched_google_ids = array();
	$results = array();
	
	$index_key=rand(0,7);
	$api_key_google=$api_key[$index_key];

	$line = "# of Key for text/nearsearch: " . $index_key . "\r\n";
	fwrite($fp_log, $line);
	$line = "Key for text/nearseach: " . $api_key_google . "\r\n";
	fwrite($fp_log, $line);
//	echo $api_key_google; exit;
	if ($search_method=='telephone' || $search_method=='textsearch') { //New added by Carlos to avoid telephone Search
		if ($search_method=='telephone'){
			$googlePlaceQuery = "https://maps.googleapis.com/maps/api/place/textsearch/json?key=$api_key_google&sensor=false&";
			$googlePlaceQuery .= "query=".urlencode($telephone_input);
		}else
		{
			$googlePlaceQuery = "https://maps.googleapis.com/maps/api/place/textsearch/json?key=$api_key_google&sensor=false&";
			$googlePlaceQuery .= "query=".urlencode($textsearch);

		}

	}else
	{
			$googlePlaceQuery = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$api_key_google&sensor=false&";
			$googlePlaceQuery .= "location=" . $lat . "," . $lng . "&radius=" . $radius;
			if (strlen($business_name)>0){
			$googlePlaceQuery .= "&" . "keyword=" . urlencode($business_name);
			//echo $googlePlaceQuery;
			}
	}
	$line = "Google Place Query: " . $googlePlaceQuery . "\r\n";
	fwrite($fp_log, $line);
//echo $googlePlaceQuery; exit;
	$web_api_result = null;
		$api_timeout = 2;
		$chk_googleapis = fsockopen ('maps.googleapis.com', 80, $errno, $errstr, $api_timeout);
		if ($chk_googleapis) {
			$web_api_result = curl($googlePlaceQuery);
			if ($web_api_result) 
#echo $web_api_result;
				$line = "API Results: " . $web_api_result . "\r\n";
				fwrite($fp_log, $line);
				$api_result = json_decode($web_api_result, TRUE);
		}

	

		if ($api_result['status']=='OK') {
			$resultArray = array();
			$elements = -1;
			foreach ($api_result['results'] as $key=>$_result) {
			$elements = $elements+1;
			$header_info=$_result;
			$results[$elements]['header'] = $header_info;
				$profile_id = $_result['id'];
				$reference = $_result['reference'];
				$result_biz_name = $_result['name'];
				$isSearched = 0;
				$isTermMatched = 0;

				foreach ($searched_google_ids as $g_key => $searched_id) {
					if ($searched_id==$profile_id) {
						$isSearched = 1;
						break;
					}
				}
				
				// cleaning up variables
				$profile_url = '';
				$profile_website = '';
				$profile_phone = '';
				$profile_street_number = '';
				$profile_street_name = '';
				$profile_street = '';
				$profile_city = '';
				$profile_state = '';
				$profile_zipcode = '';
				$profile_formatted_address = '';

				if (!$isSearched &&  $reference) { // continue if it's not searched in this session
					// Now do detail API search using reference
					$googlePlaceDetailsQuery = "https://maps.googleapis.com/maps/api/place/details/json?key=$api_key_google&sensor=false&";
					$googlePlaceDetailsQuery .= '&reference=' . $reference;
					
					$web_api_result = curl($googlePlaceDetailsQuery);
					if ($web_api_result) $api_result = json_decode($web_api_result, TRUE);
					
					if ($api_result['status']=='OK')
					{
						$result_found++;
					
						$third_party_ids[$key] = $api_result['result']['id'];
						$formatted_addresses[$key] = $api_result['result']['formatted_address'];

						$phone_number = $api_result['result']['formatted_phone_number'];
						$phone_number = preg_replace("/[^0-9]/", "", $phone_number);
						$profile_phone = substr($phone_number, 0, 10);

						$profile_id = $api_result['result']['id'];
						$profile_biz_name = $api_result['result']['name'];
						// $profile_rating = $api_result['result']['rating'];
						// $profile_reviews_arr = $api_result['result']['reviews'];
						// $profile_types_arr = $api_result['result']['types'];
						$profile_url = $api_result['result']['url'];
						$profile_website = $api_result['result']['website'];
						// $profile_hours_arr = $api_result['result']['opening_hours'];
						// $profile_photos_arr = $api_result['result']['photos'];
						$profile_formatted_address = $api_result['result']['formatted_address'];


						// Compiling address components						
						foreach ($api_result['result']['address_components'] as $a_key => $address_component) {
							if ($address_component['types'][0]=='street_number') 				// house number/building number
								$profile_street_number = $address_component['long_name'];
							if ($address_component['types'][0]=='route') 						// street name
								$profile_street_name = $address_component['long_name'];
							if ($address_component['types'][0]=='administrative_area_level_2') 	// city
								$profile_city = $address_component['long_name'];
							else if ($address_component['types'][0]=='locality') 				// city
								$profile_city = $address_component['long_name'];

							if ($address_component['types'][0]=='administrative_area_level_1') 	// state
								$profile_state = $address_component['short_name'];

							if ($address_component['types'][0]=='postal_code') 					// zipcode
								$profile_zipcode = $address_component['long_name'];
						}
						$profile_street = $profile_street_number . ' ' . $profile_street_name;

						// profile_formatted_address seems more accurate and sometimes it has more info
						$address_arr = explode(', ', $profile_formatted_address);

						// If it has 4 comma separated string then it's full. Otherwise it's without street address
						if (count($address_arr)==4) { 
							$profile_street = $address_arr[0];
							$profile_city 	= $address_arr[1];
							$profile_state 	= trim($address_arr[2]);
						}

						if (strlen($profile_state)!=2)
							$profile_state 	= substr($profile_state, 0, 2);

						// Adding new id to this session's list.
						array_push($searched_google_ids, $profile_id);


						$isDupe = 0;
						// check if url is containing maps.google.com. If it does, then don't continue.
						$is_maps_url = 0;
						if (stripos($profile_url, 'maps.google.com')) $is_maps_url = 1;

						
						if (!$is_maps_url) {

							/*=============================================================*/
							// Scraping bot to get if it's claimed or not
							/*=============================================================*/
							$bizUrl = $profile_url;
							$scrapped = 0;
							$result = getPage(
							    $bizUrl,
							    'https://plus.google.com/',
							    'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (FM Scene 4.6.1)',
							    1,
							    5);
							
							if(empty($result['ERR'])) {				
								$html = str_get_html($result['EXE']);
								$pos=0;
								// $search_str = '<div class="Jya">';
								// $search_str = '<div class="VRb">'; // new 2013-10-08
								$search_str = '<span class="VTb d-k-l">'; // new 2013-10-17
								
								$offset = 0;
								$pos=stripos($html, $search_str, $offset);

								$scrapped = 1;
							}
							$owner_claimed = '0';
							if ($pos) $owner_claimed = '1';
							
							$api_result['result'] += array('owner_claimed' => $owner_claimed);
							//Save Detailed info in Json
							$detail_info= $api_result['result'];
							$results[$elements]['detail'] = $detail_info;
							/*=============================================================*/
							// END of Scraping bot to get if it's claimed or not
							/*=============================================================*/

							$searched_str_db 				= webToDB($business_name);
							if ($search_method=='telephone') { //New added by Carlos to avoid telephone Search
								$searched_str_db 				= webToDB($telephone_input);
								
							}
							
							$profile_biz_name_db 			= webToDB($profile_biz_name);
							$profile_url_db 				= webToDB($profile_url);
							$profile_website_db				= webToDB($profile_website);
							$profile_street_db				= webToDB($profile_street);
							$profile_city_db 				= webToDB($profile_city);
							$profile_state_db 				= webToDB(trim($profile_state));
							$profile_zipcode_db				= webToDB(trim($profile_zipcode));
							$profile_formatted_address_db 	= webToDB($profile_formatted_address);

							$profile_area_code = substr($profile_phone, 0, 3);

						} else {

						}

					}
				}
				
			} // END of foreach - API results
		//	echo "<hr>HEADER<hr>";
		//	var_dump($header_info);
		//	echo "<hr>DETAIL<hr>";

		//	var_dump($detail_info);
			
			/*$response_data =  array(
								 'status' =>'OK',
								 'results' =>$resultArray
								);*/
			$response_data =  array(
								 'status' =>'OK',
								 'resultSearch' => $results
							);
								
		} else if ($api_result['status'] == 'OVER_QUERY_LIMIT $index_key') {
			$api_over_query_limit = 1;
			$response_data =  array(
								 'status' =>'GOOGLEPLACESAPI::PLACESEARCH::OVER_QUERY_LIMIT $index_key'
								);			
		} else if ($api_result['status'] == 'INVALID_REQUEST') {
			$response_data =  array(
								 'status' =>'GOOGLEPLACESAPI::PLACESEARCH::INVALID_REQUEST'
								);
			
		} else if ($api_result['status'] == 'ZERO_RESULTS') {

			$response_data =  array(
								 'status' =>'GOOGLEPLACESAPI::PLACESEARCH::ZERO_RESULTS'
								);
			
		} 


	$line = "Response Data:\r\n";
	fwrite($fp_log, $line);
	fwrite($fp_log, print_r($response_data, TRUE));


	fclose($fp_log);

	$broker_result_end = curl("http://fg-dev2-test1:3777/endrequest/google_keyword_scan");

	header('Content-Type: application/json');
	$json_response= json_encode($response_data);
	echo $json_response;
	//echo $results;

	//echo "<hr>TOTAL<hr>";
	//var_dump($json_response);
//Functions Section
function formatPhone($phone) {
	return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
}

function curl($url,$post=null,$headers=null, $multi = 0){
	global $config;
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);

	if(is_array($post)){
		curl_setopt($ch,CURLOPT_POST,true);
		if ($multi)
		{
			curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $config['cookie_file']);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $config['cookie_file']);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
			$data=curl_exec($ch) or curl_error($ch);
			curl_close($ch);

			return $data;

		}
		else
			curl_setopt($ch,CURLOPT_POSTFIELDS,query($post));	
	}

	if(is_array($headers))
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);

	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $config['cookie_file']);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $config['cookie_file']);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

	$data=curl_exec($ch) or curl_error($ch);

	curl_close($ch);

	return $data;
}

function getPage($url, $referer, $agent, $header, $timeout, $proxy=NULL) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($proxy!=NULL) curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
    $result['EXE'] = curl_exec($ch);
    $result['INF'] = curl_getinfo($ch);
    $result['ERR'] = curl_error($ch);
 
    curl_close($ch);

    return $result;
}
?>
