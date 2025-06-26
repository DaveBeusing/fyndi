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
use app\dataprovider\ApiGmbH;

class Catalog {

	public function __construct( ){ }

	public static function getSearchFilters(){
		if( file_exists( Config::get()->dataprovider->filters ) ){
			$json = file_get_contents( Config::get()->dataprovider->filters );
		}
		else {
			$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
			$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
			$stmt = $pdo->prepare("
				SELECT DISTINCT manufacturer AS value, 'manufacturer' AS type FROM catalog WHERE manufacturer != '' 
				UNION
				SELECT DISTINCT category1 AS value, 'category1' AS type FROM catalog WHERE category1 != '';"
			);
			$stmt->execute();
			$results = $stmt->fetchAll( \PDO::FETCH_ASSOC );
			$brands = [];
			$categories = [];
			foreach ($results as $row) {
				if( $row['type'] === 'manufacturer' && !empty( $row['value'] ) ){
					$brands[] = $row['value'];
				}
				elseif( $row['type'] === 'category1' && !empty( $row['value'] ) ){
					$categories[] = $row['value'];
				}
			}
			$json = json_encode([
				'brands' =>  $brands,
				'categories' => $categories
			]);
			file_put_contents( Config::get()->dataprovider->filters, $json );
		}
		header( 'Content-Type: application/json' );
		echo $json;
	}

	public static function getSearchResults( $query ){
		if( !$query || mb_strlen( $query ) < 2 ){
			echo json_encode( [] );
			exit;
		}
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$stmt = $pdo->prepare("
			SELECT *,
				ROUND(
					(
						( MATCH( title ) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 6 +
						( MATCH( sku ) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 8 +
						( MATCH( mpn ) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 8 +
						( MATCH( ean ) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 8 +
						( MATCH( manufacturer ) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 20 +
						-- ( MATCH( description) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 2 +
						( MATCH( category1, category2, category3, category4, category5 ) AGAINST( :query IN NATURAL LANGUAGE MODE ) ) * 3
					), 2
				) AS score
				FROM catalog
				WHERE MATCH( title, description, manufacturer, mpn, ean, category1, category2, category3, category4, category5, sku )
					AGAINST( :query IN NATURAL LANGUAGE MODE )
				ORDER BY score DESC
				LIMIT 16;
		");
		$stmt->bindParam( ':query', $query, \PDO::PARAM_STR );
		$stmt->execute();
		$results = $stmt->fetchAll( \PDO::FETCH_ASSOC );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Content-Type: application/json' );
		echo json_encode( $results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		exit;
	}

	public static function getItemDetails( $uid ){
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$stmt = $pdo->prepare( "SELECT * FROM catalog WHERE uid = :uid" );
		$stmt->bindParam( ':uid', $uid, \PDO::PARAM_STR );
		$stmt->execute();
		$result = $stmt->fetch( \PDO::FETCH_ASSOC );
		if( $result === false ){
			echo "Kein Eintrag mit UID $uid gefunden.";
			exit;
		}
		$gpsr_dummy = array(
			'brand' => '',
			'company' => '',
			'street' => '',
			'country' => '',
			'city' => '',
			'homepage' => '',
			'support_url' => '',
			'support_email' => '',
			'support_hotline' => ''
		);
		$gpsr = false;
		if( $result['gpsr'] != null ){
			$gpsr_uid = $result['gpsr'];
			$stmt = $pdo->prepare( "SELECT * FROM gpsr WHERE uid = :uid" );
			$stmt->bindParam( ':uid', $gpsr_uid, \PDO::PARAM_STR );
			$stmt->execute();
			$gpsr = $stmt->fetch( \PDO::FETCH_ASSOC );
			if( $gpsr === false ){
				$gpsr = $gpsr_dummy;
			}
			else {
				$gpsr = $gpsr;
			}
			$result['gpsr'] = (object) $gpsr;
		}
		$result['gpsr'] = ( $gpsr === false ) ? (object) $gpsr_dummy : (object) $gpsr;
		$result['iscondition'] = ApiGmbH::convertCondition( $result['iscondition'] );
		$result['availability'] = ApiGmbH::convertAvailability( $result['availability'] );
		$result['shipping'] = ApiGmbH::convertShipping( $result['shipping'] );
		return $result;
	}

	public static function getProductImage( $uid = false, $width = 800, $height = 600, $placeholdertext = 'Kein Bild' ){
		$extensions = ['jpg', 'jpeg', 'png'];
		$found = false;
		foreach( $extensions as $ext ){
			$fullPath = Config::get()->html->images->path . $uid . '.' . $ext;
			if( file_exists( $fullPath ) ){
				$found = $fullPath;
				break;
			}
		}
		if( $found ){
			$info = getimagesize( $found );
			$mime = $info['mime'];
			switch ($mime) {
				case 'image/jpeg': 
					$src = imagecreatefromjpeg( $found );
				break;
				case 'image/png':
					$src = imagecreatefrompng( $found );
				break;
				case 'image/webp':
					$src = imagecreatefromwebp( $found );
				break;
				case 'image/gif':
					$src = imagecreatefromgif( $found );
				break;
				default:
					http_response_code( 415 );
					exit( "Unsupported format" );
			}
			$resized = imagecreatetruecolor( $width, $height );
			imagecopyresampled( $resized, $src, 0, 0, 0, 0, $width, $height, imagesx( $src ), imagesy( $src ) );
			header( "Content-Type: $mime" );
			switch ($mime) {
				case 'image/jpeg':
					imagejpeg( $resized );
				break;
				case 'image/png':
					imagepng( $resized );
				break;
				case 'image/webp':
					imagewebp( $resized );
				break;
				case 'image/gif':
					imagegif( $resized );
				break;
			}
			imagedestroy( $src );
			imagedestroy( $resized );
		} else {
			// SVG-Fallback
			header( "Content-Type: image/svg+xml" );
			$label = htmlspecialchars( $placeholdertext );
			echo <<<SVG
				<svg width="$width" height="$height" xmlns="http://www.w3.org/2000/svg">
					<rect width="100%" height="100%" fill="#ccc"/>
					<text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="16" fill="#444">$label</text>
				</svg>
			SVG;
		}
	}

	public static function getMetrics() : void {
		$pdo = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$stats = [];
		$stats[ 'total'] = $pdo->query("SELECT COUNT(*) FROM catalog")->fetchColumn();
		$stats[ 'stocked'] = $pdo->query("SELECT COUNT(*) FROM catalog WHERE stock > 0")->fetchColumn();
		$stats[ 'eol'] = $pdo->query("SELECT COUNT(*) FROM catalog WHERE iseol = 1")->fetchColumn();
		$stats[ 'unavailable'] = $pdo->query("SELECT COUNT(*) FROM catalog WHERE availability = 0")->fetchColumn();
		$stats[ 'virtual'] = $pdo->query("SELECT COUNT(*) FROM catalog WHERE category1 = 'Garantie virtuell'")->fetchColumn();
		$stats[ 'avg_price'] = $pdo->query("SELECT ROUND(AVG(price),2) FROM catalog WHERE price > 0")->fetchColumn();
		$stats[ 'total_stock_value'] = $pdo->query( "SELECT ROUND( SUM( price * stock ), 2 ) FROM catalog WHERE category1 != 'Garantie virtuell' AND price > 0" )->fetchColumn();
		$stats[ 'created_7d'] = $pdo->query("SELECT COUNT(*) FROM catalog WHERE created >= NOW() - INTERVAL 7 DAY")->fetchColumn();
		$stats[ 'avg_weight'] = $pdo->query("SELECT ROUND(AVG(weight),3) FROM catalog WHERE weight > 0")->fetchColumn();
		$stats[ 'avg_volweight'] = $pdo->query("SELECT ROUND(AVG(volweight),3) FROM catalog WHERE volweight > 0")->fetchColumn();
		$stats[ 'unique_manufacturers' ] = $pdo->query( "SELECT COUNT(DISTINCT manufacturer) FROM catalog" )->fetchColumn();
		$stats[ 'unique_categories' ] = $pdo->query( "SELECT COUNT(DISTINCT category1) FROM catalog" )->fetchColumn();
		$stats[ 'missing_mpn' ] = $pdo->query( "SELECT SUM(CASE WHEN mpn IS NULL OR mpn = '' THEN 1 ELSE 0 END) FROM catalog" )->fetchColumn();
		$stats[ 'missing_ean' ] = $pdo->query( "SELECT SUM(CASE WHEN ean IS NULL OR ean = '' THEN 1 ELSE 0 END) FROM catalog" )->fetchColumn();
		$stats[ 'missing_taric' ] = $pdo->query( "SELECT SUM(CASE WHEN taric IS NULL OR taric = 0 THEN 1 ELSE 0 END) FROM catalog" )->fetchColumn();
		$stats[ 'refurbished'] = $pdo->query( "SELECT COUNT(*) FROM catalog WHERE iscondition = 1" )->fetchColumn();
		$stats[ 'shipping_parcel' ] = $pdo->query( "SELECT SUM(CASE WHEN shipping = 1 THEN 1 ELSE 0 END) FROM catalog" )->fetchColumn();
		$stats[ 'shipping_bulk' ] = $pdo->query( "SELECT SUM(CASE WHEN shipping = 2 THEN 1 ELSE 0 END) FROM catalog" )->fetchColumn();
		$stats[ 'shipping_epal' ] = $pdo->query( "SELECT SUM(CASE WHEN shipping = 3 THEN 1 ELSE 0 END) FROM catalog" )->fetchColumn();
		$stmt = $pdo->query("
			SELECT category1, COUNT(*) as count 
			FROM catalog 
			WHERE category1 IS NOT NULL AND category1 != '' 
			GROUP BY category1 
			ORDER BY count DESC 
			LIMIT 10
		");
		$stats['top_categories'] = $stmt->fetchAll( \PDO::FETCH_ASSOC );
		$stmt = $pdo->query("
			SELECT manufacturer, COUNT(*) as count 
			FROM catalog 
			WHERE manufacturer IS NOT NULL AND manufacturer != '' 
			GROUP BY manufacturer 
			ORDER BY count DESC 
			LIMIT 10
		");
		$stats['top_manufacturers'] = $stmt->fetchAll( \PDO::FETCH_ASSOC );
		$stmt = $pdo->query("
			SELECT availability, COUNT(*) as count 
			FROM catalog 
			GROUP BY availability 
			ORDER BY count DESC
		");
		$stats['availability_dist'] = $stmt->fetchAll( \PDO::FETCH_ASSOC );
		print json_encode( $stats );
		exit;
	}

	public static function generateUID() : string {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = 10;
		$uid = $chars[ random_int( 0, strlen( $chars ) - 1) ];
		while( strlen( $uid ) < $len ){
			$nextChar = $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
			if( $nextChar !== $uid[ strlen( $uid ) - 1 ] ){
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

}
?>