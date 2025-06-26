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
 * Examples:
 * {{user}}
 * {{user.name}}
 * {{user.address.city}}
 * 
 * {{if isLoggedIn}}
 * {{endif}}
 * 
 * {{if isLoggedIn == false}}
 * {{endif}}
 * 
 * {{foreach tasks as task}}
 * {{task}}
 * {{endforeach}}
 * 
 */
namespace app\template;

class TemplateEngine {

	public function __construct(){}

	public static function render( $templatePath, $data = []) : void {
		if( !file_exists( $templatePath ) ){
			throw new \Exception( "Template not found in directory {$templatePath}" );
		}
		$template = file_get_contents( $templatePath );
		// Foreach-Schleifen: {{foreach items as item}}...{{endforeach}}
		$template = preg_replace_callback( '/{{foreach (.*?) as (.*?)}}(.*?){{endforeach}}/s', function( $matches ) use ( $data ){
			$list = self::get( $matches[1], $data );
			$itemVar = trim( $matches[2] );
			$block = $matches[3];
			$result = '';
			if( is_iterable( $list ) ){
				foreach( $list as $item ){
					$temp = $block;
					// Ersetze {{item.field}} mit Objekt- oder Arraywert
					$temp = preg_replace_callback( '/{{' . $itemVar . '\.(.*?)}}/', function( $v ) use ( $item ){
						return htmlspecialchars( self::access( $item, $v[1] ) );
					}, $temp );
					// Fallback: einfache {{item}}-Ersetzung für Strings
					if( !is_array( $item ) && !is_object( $item ) ){
						$temp = str_replace( '{{' . $itemVar . '}}', htmlspecialchars( $item ), $temp );
					}
					$result .= $temp;
				}
			}
			return $result;
		}, $template);
		// If-Bedingungen
		$template = preg_replace_callback( '/{{if (.*?)}}(.*?){{endif}}/s', function( $matches ) use ( $data ){
			$condition = self::get( $matches[1], $data );
			return $condition ? $matches[2] : '';
		}, $template);
		// Variablen ersetzen mit Punktnotation
		$template = preg_replace_callback( '/{{(.*?)}}/', function( $matches ) use ( $data ){
			return htmlspecialchars( self::get( $matches[1], $data ) );
		}, $template);
		echo $template;
		exit;
	}

	// Zugriff auf verschachtelte Daten: user.name, user.address.city
	private static function get( string $key, $data ){
		$keys = explode( '.', trim( $key ) );
		$value = $data;
		foreach( $keys as $k ){
			$value = self::access( $value, $k );
			if( $value === null ) break;
		}
		return $value;
	}

	// Greift sicher auf Array- oder Objektfeld zu
	private static function access( $value, string $key ){
		if( is_array( $value ) && array_key_exists( $key, $value ) ){
			return $value[$key];
		}
		if( is_object ($value ) && isset( $value->$key ) ){
			return $value->$key;
		}
		return null;
	}
}

?>