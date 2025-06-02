<?php 
/****
 * Copyright (C) 2025 Dave Beusing <david.beusing@gmail.com>
 * 
 * MIT License - https://opensource.org/license/mit/
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the “Software”), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished 
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 */
// Development ONLY! - MUST be removed in production
error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', true );
ini_set( 'html_errors', true );
//workaround Fatal error: Maximum execution time of 30 seconds exceeded in app/dataprovider/Crawler.php on line 80
ini_set('max_execution_time', '300');
// workaround Fatal error: Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes) in app/dataprovider/ApiGmbH.php on line 60
ini_set('memory_limit', '256M');
/**
 * Autoload
 **/
require_once 'src/autoload.php';
/**
 * Includes
 */
use app\config\Config;
use app\utils\Utils;
use app\utils\Template;
use app\dataprovider\Catalog;

switch( filter_input( INPUT_GET, 'view', FILTER_SANITIZE_SPECIAL_CHARS ) ):

	case 'image':
		$uid = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_SPECIAL_CHARS );
		$size = explode( 'x', ( filter_input( INPUT_GET, 'size', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '800x600' ) );
		Catalog::getProductImage( $uid, $size[0], $size[1] );
	break;

	case 'api':
		$query = filter_input( INPUT_GET, 'query', FILTER_SANITIZE_SPECIAL_CHARS );
		Catalog::getSearchResults( $query );
	break;

	case 'item':
		$uid = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_SPECIAL_CHARS );
		if( !$uid || !Utils::validateUID( $uid ) ){
			header( 'HTTP/1.0 404 Not Found' );
			header( 'Location: '. Config::get()->app->url );
			exit;
		}
		$item = Catalog::getItemDetails( $uid );
		Template::view(
			Config::get()->html->template->path.'item.html',
			[
				'Title' => Config::get()->app->name,
				'URL' => Config::get()->app->url,
				'Slogan' => Config::get()->app->slogan,
				'Item' => (object) $item
			]
		);
	break;

	case 'debug':
		$uid = Utils::generateUID();
		$isValid = Utils::validateUID( $uid );
		print "UID: $uid / isValid: $isValid";
	break;

	default:
		Template::view(
			Config::get()->html->template->path.'search.html',
			[
				'Title' => Config::get()->app->name,
				'URL' => Config::get()->app->url,
				'Slogan' => Config::get()->app->slogan,
			]
		);
endswitch;
?>