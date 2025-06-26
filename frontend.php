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
/**
 * Autoload
 **/
require_once 'src/autoload.php';
/**
 * Includes
 */
use app\config\Config;
use app\catalog\Catalog;
use app\template\TemplateEngine;
/**
 * Main switching
 */
switch( filter_input( INPUT_GET, 'view', FILTER_SANITIZE_SPECIAL_CHARS ) ):

	case 'image':
		$uid = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_SPECIAL_CHARS );
		$size = explode( 'x', ( filter_input( INPUT_GET, 'size', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '800x600' ) );
		Catalog::getProductImage( $uid, $size[0], $size[1] );
	break;

	case 'item':
		$uid = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_SPECIAL_CHARS );
		if( !$uid || !Utils::validateUID( $uid ) ){
			header( 'HTTP/1.0 404 Not Found' );
			header( 'Location: '. Config::get()->app->url );
			exit;
		}
		$item = Catalog::getItemDetails( $uid );
		TemplateEngine::render(
			Config::get()->html->template->path.'item.html',
			[
				'Title' => Config::get()->app->name,
				'BaseURL' => Config::get()->app->url,
				'Slogan' => Config::get()->app->slogan,
				'Item' => (object) $item
			]
		);
	break;

	case 'debug':
		$iam->secure( [ 'Admin', 'Editor' ] );
		$uid = Catalog::generateUID();
		$isValid = Catalog::validateUID( $uid );
		print "UID: $uid / isValid: $isValid <br><br>";
		print "Generated Password: <br> Hash:" .password_hash( '', PASSWORD_DEFAULT ) . "<br>";
		print_r( $_SESSION );
		print '<br>';

	break;

	default:
		TemplateEngine::render(
			Config::get()->html->template->path.'search.html',
			[
				'Title' => Config::get()->app->name,
				'BaseURL' => Config::get()->app->url,
				'Slogan' => Config::get()->app->slogan,
			]
		);
endswitch;

?>