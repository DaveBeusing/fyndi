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

namespace app\identity;

use app\config\Config;
use app\database\MySQLPDO;

class IdentityAccessManagement {

	private $PDO;
	private $SessionName;
	private $SessionLifetime;
	private $MaxLoginAttempts;

	private $Emails = [
		'From' => 'SYS-OPS <sysops@bsng.eu>',
		'Return' => 'sysops@bsng.eu',
		'Support' => 'support@bsng.eu'
	];

	private $Mailings = [
		'Verify' => '/assets/html/backend/mailing/account-verify.html',
		'ResetPassword' => '/assets/html/backend/mailing/account-reset.html'
	];

	private $Locations = [
		'BaseDirectory' => '', 
		'BaseURL' => 'https://bsng.eu/app/fyndi/',
		'LoginURL' => 'login',
		'LogoutURL' => 'logout',
		'VerifyURL' => 'backend/verify',
		'ResetURL' => 'backend/reset'
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

	public function __construct( string $session_name = 'iam', int $session_lifetime = 3600, int $max_login_attempts = 3 ){
		$this->SessionName = $session_name;
		$this->SessionLifetime = $session_lifetime;
		$this->MaxLoginAttempts = $max_login_attempts;
		$this->Platforms = json_decode( json_encode( $this->Platforms ) );
		$this->Locations = (object) $this->Locations;
		$this->Emails = (object) $this->Emails;
		$this->Mailings = (object) $this->Mailings;
		$this->Locations->BaseDirectory = realpath( __DIR__ . '/../../../' ); // currently in .../www/app/fyndi/src/app/utils
		session_name( $this->SessionName );
		session_set_cookie_params( $this->SessionLifetime );
		if( session_status() === PHP_SESSION_NONE ){
			session_start();
		}
		$this->PDO = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$this->PDO->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
	}

	public function secure( array $allowedRoles ) : void {
		if( !isset( $_SESSION[ $this->SessionName ] ) || !isset( $_COOKIE[ $this->SessionName ] ) || !$this->validateFingerprint( $_COOKIE[ $this->SessionName ] ) ) {
			$this->redirect( $this->Locations->LoginURL );
			exit;
		}
		if( !in_array( $_SESSION[ $this->SessionName ]->role, $allowedRoles ) ){
			http_response_code(403);
			exit;
		}
	}

	public function auth( string $email, string $password, string $returnURL ) : string {
		$user = $this->authenticate( $email, $password );
		if( $user ){
			$this->redirect( $returnURL );
		} else {
			return 'E-Mail or Password incorrect or Account suspended.';
		}
	}

	public function deauth() : void {
		setcookie( $this->SessionName, '', time() - ( $this->SessionLifetime + 2 ), "/" );
		$_SESSION[ $this->SessionName ] = (object) array();
		session_destroy();
		$this->redirect( $this->Locations->LoginURL );
		exit;
	}

	public function createUser( string $email, string $password, string $role = 'user' ) : bool {
		if( empty( $email ) || empty( $password ) ) {
			return false;
		}
		$hash = password_hash( $password, PASSWORD_DEFAULT );
		$token = bin2hex( random_bytes( 32 ) );
		$stmt = $this->PDO->prepare( "INSERT INTO iam_users ( email, password_hash, role, token ) VALUES ( :email, :hash, :role, :token )" );
		$stmt->bindParam( ':email', $email, \PDO::PARAM_STR );
		$stmt->bindParam( ':hash', $hash, \PDO::PARAM_STR );
		$stmt->bindParam( ':role', $role, \PDO::PARAM_STR );
		$stmt->bindParam( ':token', $token, \PDO::PARAM_STR );
		try {
			$success = $stmt->execute();
			if( $success ){
				$verifyLink = $this->Locations->BaseURL . $this->Locations->VerifyURL . '/' . $token;
				$payload = [
					'subject' => 'Verify your email',
					'supportEmail' => $this->Emails->Support,
					'verifyLink' => $verifyLink
				];
				$this->sendEmail(
					$email,
					$payload,
					$this->Mailings->Verify
				);
				return true;
			}
		}
		catch( \PDOException $error ){
			return false;
		}
	}

	public function loadUsers(){
		$stmt = $this->PDO->query( "SELECT uid, email, role, status, login_attempts, created_at, updated_at, updated_by FROM iam_users ORDER BY uid ASC" );
		$stmt->execute();
		$users = $stmt->fetchAll( \PDO::FETCH_ASSOC );
		if( $users ){
			return $users;
		}
		return false;
	}

	private function authenticate( string $email, string $password ) : bool {
		$stmt = $this->PDO->prepare( "SELECT uid, email, password_hash, role, status, login_attempts FROM iam_users WHERE email = :email" );
		$stmt->bindParam( ':email', $email, \PDO::PARAM_STR );
		$stmt->execute();
		$user = $stmt->fetch( \PDO::FETCH_ASSOC );
		if( $user ){
			if( password_verify( $password, $user[ 'password_hash' ] ) ){
				if( $user['login_attempts'] >= $this->MaxLoginAttempts ){
					$this->setStatus( $user['uid'], 2 );
					return false;
				}
				else {
					$hash = $this->createFingerprint();
					$_COOKIE[ $this->SessionName ] = $hash;
					$_SESSION[ $this->SessionName ] = (object) [ 'email' => $user[ 'email' ], 'role' => $user[ 'role' ], 'hash' => $hash ];
					setcookie( $this->SessionName, $hash, time() + $this->SessionLifetime, "/" );
					$this->updateLoginAttempt( $user['uid'], true );
					$this->logLogin( $user['uid'], true );
					return true;
				}
			}
			else{
				$this->updateLoginAttempt( $user['uid'], false );
				$this->logLogin( $user['uid'], false );
			}
		}
		return false;
	}

	public function verifyUser( string $token = '' ) : void {
		if( !$token ){
			$this->redirect( $this->Locations->LoginURL );
		}
		$stmt_uid = $this->PDO->prepare( "SELECT uid FROM iam_users WHERE token = :token AND status = 0" );
		$stmt_uid->bindParam( ':token', $token, \PDO::PARAM_STR );
		$stmt_uid->execute();
		$user = $stmt_uid->fetch( \PDO::FETCH_ASSOC );
		if( $user ){
			$stmt_update = $this->PDO->prepare( "UPDATE iam_users SET status = 1, token = NULL WHERE uid = :uid" );
			$stmt_update->bindParam( ':uid', $user['uid'], \PDO::PARAM_STR );
			$stmt_update->execute();
			$this->redirect( $this->Locations->LoginURL );
		}
		else {
			$this->redirect( $this->Locations->LoginURL );
		}
	}

	public function requestPasswordReset( string $email = '' ){
		if( $email ){
			$stmt = $this->PDO->prepare( "SELECT uid FROM iam_users WHERE email = :email" );
			$stmt->bindParam( ':email', $email, \PDO::PARAM_STR );
			$stmt->execute();
			$user = $stmt->fetch( \PDO::FETCH_ASSOC );
			if( $user ){
				$token = bin2hex( random_bytes( 32 ) );
				$expires = date( 'Y-m-d H:i:s', time() + 3600 );
				$stmt_update = $this->PDO->prepare( "UPDATE iam_users SET token = :token, token_expires = :expires WHERE uid = :uid" );
				$stmt_update->bindParam( ':token', $token, \PDO::PARAM_STR );
				$stmt_update->bindParam( ':expires', $expires, \PDO::PARAM_STR );
				$stmt_update->bindParam( ':uid', $user['uid'], \PDO::PARAM_STR );
				$stmt_update->execute();
				$resetLink = $this->Locations->BaseURL . $this->Locations->ResetURL . '/' . $token;
				$payload = [
					'subject' => 'Password reset request',
					'supportEmail' => $this->Emails->Support,
					'resetLink' => $resetLink
				];
				$this->sendEmail(
					$email,
					$payload,
					$this->Mailings->ResetPassword
				);
			}
		}
	}

	public function resetPassword( string $token = '', string $password = '' ){
		$stmt = $this->PDO->prepare( "SELECT uid, email FROM iam_users WHERE token = :token AND token_expires > NOW()" );
		$stmt->bindParam( ':token', $token, \PDO::PARAM_STR );
		$stmt->execute();
		$user = $stmt_uid->fetch( \PDO::FETCH_ASSOC );
		if( $user ){
			$hash = password_hash( $password, PASSWORD_DEFAULT );
			$stmt_update = $this->PDO->prepare( "UPDATE iam_users SET password_hash = :hash, token = NULL, expires = NULL WHERE uid = :uid" );
			$stmt_update->bindParam( ':hash', $hash, \PDO::PARAM_STR );
			$success = $stmt_update->execute();
			if( $success ){
				$payload = [
					'subject' => 'Password reset successfull',
					'supportEmail' => $this->Emails->Support
				];
				$this->sendEmail(
					$user['email'],
					$payload,
					'/assets/html/backend/mailing/account-reset-success.html'
				);
			}
			$this->redirect( $this->Locations->LoginURL );
		}
		$this->redirect( $this->Locations->LoginURL );
	}

	private function setStatus( int $uid, int $status = 0 ) : void {
		$stmt = $this->PDO->prepare( "UPDATE iam_users SET status = :status WHERE uid = :uid" );
		$stmt->bindParam( ':uid', $email, \PDO::PARAM_INT );
		$stmt->bindParam( ':status', $email, \PDO::PARAM_INT );
		$stmt->execute();
	}

	private function updateLoginAttempt( int $uid, bool $success = false ) : void {
		if( $success ){
			$stmt = $this->PDO->prepare( "UPDATE iam_users SET login_attempts = 0 WHERE uid = :uid" );
			$stmt->bindParam( ':uid', $email, \PDO::PARAM_INT );
			$stmt->execute();
		}
		else {
			$stmt = $this->PDO->prepare( "UPDATE iam_users SET login_attempts = login_attempts + 1 WHERE uid = :uid" );
			$stmt->bindParam( ':uid', $email, \PDO::PARAM_INT );
			$stmt->execute();
		}
	}

	private function logLogin( int $uid, bool $success ) : void {
		$success = (int) $success;
		$ip = ip2long( $this->getClientIP() );
		$agent = $this->getClientUseragent() ?? 'Unknown';
		$stmt = $this->PDO->prepare( "INSERT INTO iam_logins ( uid, success, ip_address, user_agent ) VALUES ( :uid, :success, :ip, :agent )" );
		$stmt->bindParam( ':uid', $uid, \PDO::PARAM_INT );
		$stmt->bindParam( ':success', $success, \PDO::PARAM_INT );
		$stmt->bindParam( ':ip', $ip, \PDO::PARAM_INT );
		$stmt->bindParam( ':agent', $agent, \PDO::PARAM_STR );
		$stmt->execute();
	}

	public function getUserLogins( int $uid ) : string {
		$stmt_summary = $this->PDO->prepare( "
			SELECT 
				u.uid,
				u.email,
				u.role,
				u.status,
				u.login_attempts,
				MAX( l.login_time ) AS last_login_time,
				INET_NTOA( MAX( CASE WHEN l.success = 1 THEN l.ip_address ELSE NULL END ) ) AS last_login_ip,
				COUNT( CASE WHEN l.success = 0 THEN 1 END ) AS failed_logins_total,
				COUNT( CASE WHEN l.success = 1 THEN 1 END ) AS successfull_logins_total
			FROM iam_users u
			LEFT JOIN iam_logins l ON l.uid = u.uid
			WHERE u.uid = :uid
			GROUP BY u.uid, u.email, u.role, u.status, u.login_attempts;
		" );
		$stmt_summary->bindParam( ':uid', $uid, \PDO::PARAM_INT );
		$stmt_summary->execute();
		$user_summary = $stmt_summary->fetch( \PDO::FETCH_ASSOC );
		$stmt_logins = $this->PDO->prepare( " 
			SELECT 
				success,
				INET_NTOA(ip_address) AS ip,
				user_agent,
				login_time
			FROM iam_logins
			WHERE uid = :uid
			ORDER BY login_time DESC
			LIMIT 10;
		" );
		$stmt_logins->bindParam( ':uid', $uid, \PDO::PARAM_INT );
		$stmt_logins->execute();
		$user_logins = $stmt_logins->fetchAll( \PDO::FETCH_ASSOC );
		print json_encode( [ 'success' => true, 'last_logins' => $user_logins, 'summary' => $user_summary ] );
		exit;
	}

	private function createFingerprint() : string {
		return hash( 'sha256', $this->getClientPlatform()->os );
	}

	private function validateFingerprint( $fingerprint ) : bool {
		return hash_equals( $fingerprint, $this->createFingerprint() );
	}

	private function redirect( $target ) : void {
		header( 'HTTP/1.1 302 Found' );
		header( 'Location: ' . $target );
		exit;
	}

	private function getClientIP() : string {
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

	private function getClientUseragent() : string {
		return $_SERVER[ 'HTTP_USER_AGENT' ] ?? false;
	}

	private function getClientPlatform() : object {
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

	private function sendEmail( $recipient = false, $payload = [], $template = false ) : bool {
		$template = file_get_contents( $this->Locations->BaseDirectory . $template );
		$replacements = [];
		$values = [];
		foreach( $payload as $key => $value ){
			$replacements[] = '{{' . $key . '}}';
			$values[] = is_string( $value ) ? nl2br( htmlspecialchars( $value ) ) : $value;
		}
		$html = str_replace( $replacements, $values, $template );
		$html = preg_replace('/{{\s*[\w]+\s*}}/', '', $html); //remove all forgotten placeholders!
		$headers = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/html; charset=utf8";
		$headers[] = "From: ".$this->Emails->From;
		$headers[] = "Return-Path: ".$this->Emails->Return;
		return mail( $recipient, $payload['subject'], $html, implode( "\r\n", $headers ) );
	}
}
?>