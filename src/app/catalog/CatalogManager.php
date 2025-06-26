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

namespace app\catalog;

use app\config\Config;
use app\database\MySQLPDO;

class CatalogManager {


	public static function searchItems( $query, $sort, $dir, $page, $limit, $offset ){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$allowedSorts = ['uid', 'title', 'status', 'price', 'stock'];
		if( !in_array( $sort, $allowedSorts ) ) $sort = 'uid';
		$like = '%' . $query . '%';
		$whereSql = "WHERE uid LIKE :q OR title LIKE :q";
		$countStmt = $pdo->prepare( "SELECT COUNT(*) FROM catalog $whereSql" );
		$countStmt->bindParam( ':q', $like, \PDO::PARAM_STR );
		$countStmt->execute();
		$total = $countStmt->fetchColumn();
		$dataStmt = $pdo->prepare("
			SELECT uid, title, status, price, stock
			FROM catalog
			$whereSql
			ORDER BY $sort $dir
			LIMIT :limit OFFSET :offset
		");
		$dataStmt->bindValue( ':q', $like );
		$dataStmt->bindValue( ':limit', $limit, \PDO::PARAM_INT );
		$dataStmt->bindValue( ':offset', $offset, \PDO::PARAM_INT );
		$dataStmt->execute();
		$rows = $dataStmt->fetchAll( \PDO::FETCH_ASSOC );
		echo json_encode([
			'rows' => $rows,
			'total' => (int)$total,
		]);
		exit;
	}

	public static function saveItem(){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$data = json_decode( file_get_contents( "php://input" ), true );
		$uid = $data['uid'];
		$now = date("Y-m-d H:i:s");
		$entry = [];
		foreach( $fields as $field ){
			$entry[$field] = $data[$field] ?? null;
		}
		$stmt = $pdo->prepare( "SELECT COUNT(*) FROM catalog WHERE uid = :uid" );
		$stmt->bindParam( ':uid', $uid, \PDO::PARAM_STR );
		$stmt->execute();
		if( $stmt->fetchColumn() ){
			$set = implode(", ", array_map( fn ($field) => "$field = ?", array_keys( $entry ) ) );
			$pdo->prepare( "UPDATE catalog SET $set, updated = ? WHERE uid = ?" )->execute( [...array_values( $entry ), $now, $uid ] );
		} else {
			$cols = implode( ", ", array_keys( $entry ) );
			$qs = implode( ", ", array_fill( 0, count( $entry ), "?" ) );
			$pdo->prepare( "INSERT INTO catalog (uid, $cols, created) VALUES (?, $qs, ?)" )->execute( [ $uid, ...array_values( $entry ), $now ] );
		}
		echo json_encode( [ 'success' => true ] );
		exit;
	}

	public static function loadItem( $uid ){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$stmt = $pdo->prepare( "SELECT * FROM catalog WHERE uid = :uid" );
		$stmt->bindParam( ':uid', $uid, \PDO::PARAM_STR );
		$stmt->execute();
		echo json_encode( $stmt->fetch( \PDO::FETCH_ASSOC ) );
		exit;
	}

	public static function deleteItem( $uid ){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$pdo->prepare( "DELETE FROM catalog WHERE uid = :uid" );
		$stmt->bindParam( ':uid', $uid, \PDO::PARAM_STR );
		$stmt->execute();
		echo json_encode( [ 'deleted' => true ] );
		exit;
	}

	public static function exportCSV(){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		if (isset($_GET['csv']) && $_GET['csv'] === '1') {
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename="catalog_export.csv"');
			$stmt = $pdo->query("SELECT * FROM catalog");
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$out = fopen('php://output', 'w');
			fputcsv($out, array_keys($rows[0]));
			foreach ($rows as $row) fputcsv($out, $row);
			fclose($out);
			exit;
		}
	}

}
?>