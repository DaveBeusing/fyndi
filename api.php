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
use app\catalog\CatalogManager;
use app\identity\IdentityAccessManagement;
/**
 * Initialising stage
 */
$iam = new IdentityAccessManagement();
/**
 * Main switching
 */
switch( filter_input( INPUT_GET, 'target', FILTER_SANITIZE_SPECIAL_CHARS ) ):
	/**
	 * Frontend
	 */
	case 'frontend':
		$filters = filter_input( INPUT_GET, 'filters', FILTER_SANITIZE_SPECIAL_CHARS ) ?? false;
		if( $filters ){
			Catalog::getSearchFilters();
			exit;
		}
		$query = filter_input( INPUT_GET, 'query', FILTER_SANITIZE_SPECIAL_CHARS );
		Catalog::getSearchResults( $query );
	break;
	/**
	 * Backend
	 */
	case 'backend':
		$iam->secure( [ 'admin' ] );
		$action_get = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS );
		$action_post = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS );
		$uid = filter_input( INPUT_GET, 'uid', FILTER_SANITIZE_SPECIAL_CHARS );
		$query = filter_input( INPUT_GET, 'query', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '';
		$sort = filter_input( INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS ) ?? 'uid';
		$dir = ( filter_input( INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS ) ?? 'asc' ) === 'desc' ? 'DESC' : 'ASC';
		$page = max( 1, (int)( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT ) ?? 1 ) );
		$limit = max( 1, (int)( filter_input( INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT ) ?? 10 ) );
		$offset = ( $page - 1 ) * $limit;
		if( $action_get === 'load' && Utils::validateUID( $uid ) ){
			CatalogManager::loadItem( $uid );
		}
		if( $action_get === 'search' ){
			CatalogManager::searchItems( $query, $sort, $dir, $page, $limit, $offset );
		}
		if( $action_post === 'delete' && Utils::validateUID( $uid ) ){
			CatalogManager::deleteItem( $uid );
		}
		if( $action_post === 'save' && Utils::validateUID( $uid ) ){
			CatalogManager::saveItem();
		}
		if( $action_get === 'users' ){
			$users = $iam->loadUsers();
			if( $users ){
				print json_encode( [ 'success' => true, 'users' => $users ] );
				exit;
			}
		}
		if( $action_get === 'metrics' ){
			CatalogManager::getMetrics();
		}
		if( $action_post === 'createuser' ){
			$email = trim( filter_input( INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '' );
			$password = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS ) ?? '';
			$role = filter_input( INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS ) ?? 'user';
			$success = $iam->createUser( $email, $password, $role );
			print json_encode( ['success' => $success] );
			exit;
		}
		if( $action_post === 'userlogins' ){
			$uid = filter_input( INPUT_POST, 'uid', FILTER_SANITIZE_NUMBER_INT );
			$iam->getUserLogins( $uid );
		}
		print json_encode( ['success' => false, 'error' => 'Keine Berechtigung.'] );
		exit;
	break;

	default:
		//TODO fix me
endswitch;
?>