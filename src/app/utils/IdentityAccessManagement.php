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


	public function __construct( $session_name = 'iam', $session_lifetime = 3600 ){
		$this->SessionName = $session_name;
		$this->SessionLifetime = $session_lifetime;

		session_name( $this->SessionName );
		session_set_cookie_params( $this->SessionLifetime );
		if( session_status() === PHP_SESSION_NONE ){
			session_start();
		}
	}

	public function secure( $requiredRole = 'Admin', $return_url = false ){
		if( !isset( $_SESSION[$this->SessionName] ) ) {
			header('Location: login');
			exit;
		}
		if( !isset( $_SESSION[$this->SessionName] ) || $_SESSION[$this->SessionName]['role'] !== $requiredRole ){
			http_response_code(403);
			echo "Access denied -> required Role: ".$requiredRole;
			exit;
		}
	}

	public function auth( $username, $password, $return_url ){
		$user = $this->authenticate( $username, $password );
		if( $user ){
			$_SESSION[$this->SessionName] = $user;
			header( "Location: ".$return_url );
			exit;
		} else {
			return 'Benutzername oder Passwort falsch.';
		}
	}

	public function deauth(){
		$_SESSION[$this->SessionName] = array();
		session_destroy();
		header('Location: login');
		exit;
	}

	private function authenticate( $username, $password ){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$stmt = $pdo->prepare( "SELECT username, password_hash, role FROM users WHERE username = :username" );
		$stmt->bindParam( ':username', $username, \PDO::PARAM_STR );
		$stmt->execute();
		$user = $stmt->fetch( \PDO::FETCH_ASSOC );
		if( $user && password_verify( $password, $user['password_hash'] ) ){
			return [ 'name' => $user['username'], 'role' => $user['role'] ];
		}
		return false;
	}

}
?>