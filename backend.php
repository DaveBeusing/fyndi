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
use app\template\TemplateEngine;
use app\identity\IdentityAccessManagement;
/**
 * Initialising stage
 */
$iam = new IdentityAccessManagement();
/**
 * Main switching
 */
switch( filter_input( INPUT_GET, 'view', FILTER_SANITIZE_SPECIAL_CHARS ) ):

	case 'login':
		if( $_SERVER['REQUEST_METHOD'] === 'POST' ){
			$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '';
			$password = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '';
			$response = $iam->auth( $email, $password, 'dashboard' );
		}
		else{
			TemplateEngine::render(
				Config::get()->html->template->backend.'login.html',
				[
					'Title' => Config::get()->app->name,
					'BaseURL' => Config::get()->app->url
				]
			);
		}
	break;

	case 'logout':
		$iam->deauth();
	break;

	case 'verify':
		$token = filter_input( INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS );
		$iam->verifyUser( $token );
	break;

	case 'reset-password':
		if( $_SERVER['REQUEST_METHOD'] === 'POST' ){
			$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '';
			if( $email ){
				$iam->requestPasswordReset( $email );
			}
		}
		else{
			TemplateEngine::render(
				Config::get()->html->template->backend.'reset-password.html',
				[
					'Title' => Config::get()->app->name,
					'BaseURL' => Config::get()->app->url,
					'Slogan' => Config::get()->app->slogan,
				]
			);
		}
	break;

	default:
		TemplateEngine::render(
			Config::get()->html->template->path.'search.html',
			[
				'name' => 'Dave',
				'role' => 'Teamlead Sales'
			]
		);
/*
		Template::view(
			Config::get()->html->template->path.'search.html',
			[
				'Title' => Config::get()->app->name,
				'URL' => Config::get()->app->url,
				'Slogan' => Config::get()->app->slogan,
			]
		);
*/
endswitch;
?>