<?php

/*
 * Copyright (C) 2011 Ben Everard
 *
 * http://beneverard.co.uk
 * http://github.com/beneverard
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *     
 */

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

// pass through the set ID (as a string), ensure your flickr username and api key are set within the function
echo '<pre>' . print_r(get_flickr_set(), TRUE) . '</pre>';

// end of flickr.php