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

use app\config\Config;
use app\database\MySQLPDO;

class IdentityAccessManagement {

	private $Session;
	private $SessionName;
	private $SessionLifetime;

	private $Locations = [
		'login' => 'login',
		'logout' => 'logout'
	];

	private $Platforms = [
		'browser' => [
			'Chrome' => '/Chrome\/([0-9.]+)/i',
			'Firefox' => '/Firefox\/([0-9.]+)/i',
			'Safari' => '/Safari\/([0-9.]+)/i',
			'Internet Explorer' => '/MSIE ([0-9.]+)/i',
			'Edge' => '/Edg\/([0-9.]+)/i'
		],
		'os' => [
			'Windows 10' => '/Windows NT 10.0/i',
			'macOS' => '/Mac OS X/i',
			'Linux' => '/Linux/i',
			'Android' => '/Android/i',
			'iOS' => '/iPhone|iPad/i'
		]
	];

	public function __construct( $session_name = 'iam', $session_lifetime = 3600 ){
		$this->SessionName = $session_name;
		$this->SessionLifetime = $session_lifetime;
		$this->Platforms = json_decode( json_encode( $this->Platforms ) );
		$this->Locations = (object) $this->Locations;
		session_name( $this->SessionName );
		session_set_cookie_params( $this->SessionLifetime );
		if( session_status() === PHP_SESSION_NONE ){
			session_start();
		}
	}

	public function secure( array $allowedRoles ){
		if( !isset( $_SESSION[ $this->SessionName ] ) || !isset( $_COOKIE[ $this->SessionName ] ) || !$this->validateFingerprint( $_COOKIE[ $this->SessionName ] ) ) {
			$this->redirect( $this->Locations->login );
			exit;
		}
		if( !in_array( $_SESSION[ $this->SessionName ]->role, $allowedRoles ) ){
			http_response_code(403);
			exit;
		}
	}

	public function auth( $username, $password, $returnURL ){
		$user = $this->authenticate( $username, $password );
		if( $user ){
			$this->redirect( $returnURL );
			exit;
		} else {
			return 'Benutzername oder Passwort falsch.';
		}
	}

	public function deauth(){
		setcookie( $this->SessionName, '', time() - ( $this->SessionLifetime + 2 ), "/" );
		$_SESSION[ $this->SessionName ] = (object) array();
		session_destroy();
		$this->redirect( $this->Locations->login );
		exit;
	}

	private function authenticate( $username, $password ){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$stmt = $pdo->prepare( "SELECT username, password_hash, role FROM users WHERE username = :username" );
		$stmt->bindParam( ':username', $username, \PDO::PARAM_STR );
		$stmt->execute();
		$user = $stmt->fetch( \PDO::FETCH_ASSOC );
		if( $user && password_verify( $password, $user[ 'password_hash' ] ) ){
			$hash = $this->createFingerprint();
			setcookie( $this->SessionName, $hash, time() + $this->SessionLifetime, "/" );
			$_SESSION[ $this->SessionName ] = (object) [ 'name' => $user[ 'username' ], 'role' => $user[ 'role' ], 'hash' => $hash ];
			return true;
		}
		return false;
	}

	private function createFingerprint(){
		return hash( 'sha256', $this->getClientPlatform()->os );
	}

	private function validateFingerprint( $fingerprint ){
		return hash_equals( $fingerprint, $this->createFingerprint() );
	}

	private function redirect( $target ){
		header( 'HTTP/1.1 302 Found' );
		header( 'Location: ' . $target );
		exit;
	}

	private function getClientIP(){
		if( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ){
			$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
		}
		elseif( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ){
			$ip = explode( ',', $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] )[0];
		} else {
			$ip = $_SERVER[ 'REMOTE_ADDR' ];
		}
		return $ip;
	}

	private function getClientUseragent(){
		return $_SERVER[ 'HTTP_USER_AGENT' ] ?? false;
	}

	private function getClientPlatform(){
		$useragent = $this->getClientUseragent();
		$browser = false;
		$os = false;
		if( $useragent ){
			foreach( $this->Platforms->browser as $platform => $pattern ){
				if( preg_match( $pattern, $useragent ) ){
					$browser = $platform;
				}
			}
			foreach( $this->Platforms->os as $platform => $pattern ){
				if( preg_match( $pattern, $useragent ) ){
					$os = $platform;
				}
			}
		}
		return (object) [
			'useragent' => $useragent,
			'browser' => $browser,
			'os' => $os
		];
	}
}
?>