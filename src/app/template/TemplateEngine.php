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
namespace app\template;

class TemplateEngine {

	public function __construct(){}

	public static function render( $templatePath, $data = []) : void {
		if( !file_exists( $templatePath ) ){
			throw new \Exception( "Template not found in directory {$templatePath}" );
		}
		$template = file_get_contents( $templatePath );
		$template = preg_replace_callback( '/{{foreach (.*?) as (.*?)}}(.*?){{endforeach}}/s', function( $matches ) use ( $data ){
			$list = $this->getVariable( $matches[1], $data );
			$itemVar = trim( $matches[2] );
			$block = $matches[3];
			$result = '';
			if( is_array( $list ) ){
				foreach( $list as $item ){
					$tempBlock = $block;
					// Ersetze itemVar mit Punktnotation
					$tempBlock = preg_replace_callback( '/{{' . $itemVar . '\.(.*?)}}/', function( $m ) use ( $item ){
						return htmlspecialchars( $item[ $m[1] ] ?? '' );
					}, $tempBlock );
					// Fallback: einfache {{ item }}-Ersetzung
					if( !is_array( $item ) ){
						$tempBlock = str_replace('{{ ' . $itemVar . ' }}', htmlspecialchars($item), $tempBlock);
					}
					$result .= $tempBlock;
				}
			}
			return $result;
		}, $template);
		// If-Bedingungen
		$template = preg_replace_callback( '/{{if (.*?)}}(.*?){{endif}}/s', function( $matches ) use ( $data ){
			$condition = $this->getVariable( $matches[1], $data );
			return $condition ? $matches[2] : '';
		}, $template);
		// Variablen ersetzen mit Punktnotation
		$template = preg_replace_callback( '/{{(.*?)}}/', function( $matches ) use ( $data ){
			return htmlspecialchars( $this->getVariable( $matches[1], $data ) );
		}, $template);
		echo $template;
		exit;
	}
	private function getVariable( string $key, array $data ){
		$keys = explode( '.', trim( $key ) );
		$value = $data;
		foreach( $keys as $k ){
			if( is_array( $value ) && array_key_exists( $k, $value ) ){
				$value = $value[$k];
			}
			else {
				return null;
			}
		}
		return $value;
	}
}

?>