<?php 
/****
 * Copyright (C) 2015-2025 Dave Beusing <david.beusing@gmail.com>
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

class Crawler {

	private $id;
	private $curl;
	private $handles;
	private $options;

	public	function __construct( $options = array() ){
		$this->id = 0;
		$this->handles = array();
		$this->curl = curl_multi_init();
		$this->options = $options;
	}

	public function add( $request, $options = array() ){
		$url = ( is_array( $request ) && !empty( $request['url'] ) ) ? $request['url'] : $request;
		$this->handles[$this->id] = curl_init();
		//set minimum options
		curl_setopt( $this->handles[$this->id], CURLOPT_URL, $url );
		curl_setopt( $this->handles[$this->id], CURLOPT_HEADER, false );
		curl_setopt( $this->handles[$this->id], CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->handles[$this->id], CURLOPT_CONNECTTIMEOUT, 120 );
		curl_setopt( $this->handles[$this->id], CURLOPT_TIMEOUT, 120 );
		curl_setopt( $this->handles[$this->id], CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $this->handles[$this->id], CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $this->handles[$this->id], CURLOPT_AUTOREFERER, true );
		//handle post requests
		if( is_array( $request ) ){
			if( !empty( $request['post'] ) ){
				curl_setopt( $this->handles[$this->id], CURLOPT_POST, true );
				curl_setopt( $this->handles[$this->id], CURLOPT_POSTFIELDS, $request['post'] );
			}
		}
		//set global options
		if( !empty( $this->options ) ){
			curl_setopt_array( $this->handles[$this->id], $this->options );
		}
		// set local options
		if( !empty( $options ) ){
			curl_setopt_array( $this->handles[$this->id], $options );
		}
		//add curl handle
		curl_multi_add_handle( $this->curl, $this->handles[$this->id] );
		//increment identifier
		$this->id++;
	}

	public function run(){
		$result = array();
		$running = null;
		do{
			curl_multi_exec( $this->curl, $running);
		} while( $running > 0 );
		// get content and remove handles
		foreach( $this->handles as $id => $handle ){
			$result[$id]['meta'] = curl_getinfo( $handle );
			$result[$id]['data'] = curl_multi_getcontent( $handle );
			curl_multi_remove_handle( $this->curl, $handle );
		}
		// all done, close current handle
		curl_multi_close( $this->curl );
		// return the $result Array( ['meta'] => Array(...), ['data'] => Array(...) );
		return $result;
	}

	public function getDOMxPath( $markup ){
		$dom = new \DOMDocument();
		@$dom->loadHTML( $markup );
		return new \DOMXPath( $dom );
	}

	public function getInnerHTML( $node ){
		$innerHTML= '';
		$children = $node->childNodes; 
		foreach ($children as $child) { 
			$innerHTML .= $child->ownerDocument->saveXML( $child ); 
		} 
		return $innerHTML;
	}

}
?>