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

namespace app\utils;

class Utils {

	public static function generateUID() : string {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = 10;
		$uid = $chars[random_int(0, strlen($chars) - 1)];
		while (strlen($uid) < $len) {
			$nextChar = $chars[random_int(0, strlen($chars) - 1)];
			if ($nextChar !== $uid[strlen($uid) - 1]) {
				$uid .= $nextChar;
			}
		}
		return $uid;
	}

	public static function validateUID( $uid ) : bool {
		if( strlen( $uid ) !== 10 ){
			return false;
		}
		if( !preg_match( '/^[a-zA-Z0-9]+$/', $uid ) ){
			return false;
		}
		for( $i = 1; $i < strlen( $uid ); $i++ ){
			if( $uid[$i] === $uid[$i - 1] ){
				return false;
			}
		}
		return true;
	}


	/*** 
	 * @source https://stackoverflow.com/a/5249971
	 * @author https://stackoverflow.com/users/353790/robertpitt
	 * @usage 
	 * $success = file_get_contents_chunked( "my/large/file", 4096, function( $chunk, &$handle, $iteration ){
	 *		Do what you will with the {$chunk} here
	 *		{$handle} is passed in case you want to seek to different parts of the file
	 *		{$iteration} is the section of the file that has been read so
	 *		($i * 4096) is your current offset within the file.
	 * });
	 * if( !$success ){ //It Failed }
	 * 
	 */
	public static function file_get_contents_chunked( $file, $chunk_size, $callback ){
		try {
			$handle = fopen( $file, 'r' );
			$i = 0;
			while( !feof( $handle ) ) {
				call_user_func_array( $callback, array( fread( $handle, $chunk_size ), &$handle, $i ) );
				$i++;
			}
			fclose( $handle );
		}
		catch( Exception $e ) {
			 trigger_error( "file_get_contents_chunked::" . $e->getMessage(), E_USER_NOTICE );
			 return false;
		}
		return true;
	}

}
?>