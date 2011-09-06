<?php

function get_flickr_set($set_id) {

	// options
	$api_key			= ''; // required
	$flickr_username	= ''; // required
	$cache_dir			= $_SERVER['DOCUMENT_ROOT'] . '/cache/';
	$cache_life			= 2592000; // 30 days in seconds
	
	if (!empty($set_id)) {
		
		// construct the cache file path
		$cache_file = $cache_dir . $set_id . ".json";
				
		// get the modified time of the file, doubles as file_exists too
		$filemtime = @filemtime($cache_file);
		
		// is the file missing or over the cache life		
		if (!$filemtime || (time() - $filemtime >= $cache_life)) {

			// set api url
    		$api_url = 'http://api.flickr.com/services/rest/?';
			
			// set query string array
			$query_string = array(
				'method'		=>	'flickr.photosets.getPhotos',
				'api_key'		=>	$api_key,
				'photoset_id'	=>	$set_id,
				'format'		=>	'json'
			);
			
			// construct api url with query string vars
			foreach ($query_string as $key => $value) {
				$api_url .= '&' . $key . '=' . $value;
			}
			
			// get contents from flickr api
			$json = file_get_contents($api_url);
			
			// remove start and end function name things
			$json = substr(substr($json, 14), 0, -1);
			
			// decode json feed
			$arr = json_decode($json);
			
			// set image and page urls
			$page_url	= 'http://www.flickr.com/photos/' . $flickr_username . '/%s/';
			$img_url	= 'http://farm%s.static.flickr.com/%s/%s_%s%s.jpg';
			
			// set photo sizes array
			$photo_sizes = array(
				'square'		=> '_s',
				'thumb'			=> '_t',
				'small'			=> '_m',
				'medium_500'	=> '',
				'medium_640'	=> '_z',
				'large'			=> '_b',
				'original'		=> '_o',
			);
			
			$photos = array();
			
			// loop through flickr data to construct output array
			if (!empty($arr->photoset->photo)) {
			
				foreach ($arr->photoset->photo as $key => $value) {
				
					$photos[$key]['title']		= $value->title;
					$photos[$key]['page_url']	= sprintf($page_url, $value->id);
					
					foreach ($photo_sizes as $size_name => $size_code) {
					
						$photos[$key]['photos'][$size_name] = sprintf($img_url, $value->farm, $value->server, $value->id, $value->secret, $size_code);		
							
					}
					
				}
				
			}
			
			// cache the output
			if (is_writable($cache_dir)) {

				// write new data back to cache, jsonified
				$fh = @fopen($cache_file, 'w');
				
				if ($fh) {
					fwrite($fh, json_encode($photos));
					fclose($fh);
				}
			
			}

		} else {
		
		echo "reading from file";
						
			// get contents of json cache
			$json = file_get_contents($cache_file);
			
			// decode json cache
			$photos = json_decode($json);
			
		}
				
		return $photos;
		
	} else {
	
		return FALSE;
		
	}

}

echo '<pre>' . print_r(get_flickr_set(), TRUE) . '</pre>';

// end of flickr.php