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

namespace app\dataprovider;

use app\config\Config;

class Catalog {

	public function __construct( ){ }

	public static function getProductImage( $uid = false, $width = 800, $height = 600, $placeholdertext = 'Kein Bild' ){
		$extensions = ['jpg', 'jpeg', 'png'];
		$found = false;
		foreach( $extensions as $ext ){
			$fullPath = Config::get()->html->images->path . $uid . '.' . $ext;
			if( file_exists( $fullPath ) ){
				$found = $fullPath;
				break;
			}
		}
		if( $found ){
			$info = getimagesize( $found );
			$mime = $info['mime'];
			switch ($mime) {
				case 'image/jpeg': 
					$src = imagecreatefromjpeg( $found );
				break;
				case 'image/png':
					$src = imagecreatefrompng( $found );
				break;
				case 'image/webp':
					$src = imagecreatefromwebp( $found );
				break;
				case 'image/gif':
					$src = imagecreatefromgif( $found );
				break;
				default:
					http_response_code( 415 );
					exit( "Unsupported format" );
			}
			$resized = imagecreatetruecolor( $width, $height );
			imagecopyresampled( $resized, $src, 0, 0, 0, 0, $width, $height, imagesx( $src ), imagesy( $src ) );
			header( "Content-Type: $mime" );
			switch ($mime) {
				case 'image/jpeg':
					imagejpeg( $resized );
				break;
				case 'image/png':
					imagepng( $resized );
				break;
				case 'image/webp':
					imagewebp( $resized );
				break;
				case 'image/gif':
					imagegif( $resized );
				break;
			}
			imagedestroy( $src );
			imagedestroy( $resized );
		} else {
			// SVG-Fallback
			header( "Content-Type: image/svg+xml" );
			$label = htmlspecialchars( $placeholdertext );
			echo <<<SVG
				<svg width="$width" height="$height" xmlns="http://www.w3.org/2000/svg">
					<rect width="100%" height="100%" fill="#ccc"/>
					<text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="16" fill="#444">$label</text>
				</svg>
			SVG;
		}
	}

}
?>