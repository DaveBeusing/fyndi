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

namespace app\dataprovider;

use app\config\Config;
use app\utils\Utils;
use app\utils\ImportLogger;
use app\dataprovider\Crawler;
use app\database\MySQLPDO;

class ApiGmbH {
	public function __construct( ){ }
	public static function getPricelist(){
		$crawler = new Crawler(
			array(
				CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0',
				CURLOPT_COOKIEFILE => 'data/crawler.cookie',
				CURLOPT_COOKIEJAR => 'data/crawler.cookie'
			)
		);
		$source = 'https://pricelist.api.de/pricelist/?cid='.Config::Dataprovider_User.'&cpw='.Config::Dataprovider_Password.'&shop=API&action=FULL&attachmentFilename=pricelist.csv&opt[]=CLEANUP_PRODUCT_TITLE&opt[]=ADD_DESCRIPTION&opt[]=ADD_CANCEL_DATE&opt[]=ADD_EXPECTED_%20STOCK&opt[]=ADD_REFURBISHED&opt[]=SPEAKING_%20AVAILABILITY_TAGS&opt[]=SHOW_SHIPPING_TYPE&addMap=cancelDate;nextExpectedStockDate;articleType;availability;typeOfShipping;cancelDate;copyrightCharge;deliveryTime;description;isEol;itemGroup;maxOrderQty;minOrderQty;nextExpectedStockDate;shippingCost;status;taxCode;volumetricWeight;weeenr';
		$crawler->add( $source );
		$data = $crawler->run()[0];//['data'];
		file_put_contents( Config::Dataprovider_Pricelist, $data['data'] );
		print 'done';
	}

	public static function processPricelist(){
		$logger = new ImportLogger( Config::get()->dataprovider->logfile );
		$logger->start('CSV-Import');
		$sql = new MySQLPDO( Config::get()->db->host, Config::get()->db->database, Config::get()->db->user, Config::get()->db->password, 'utf8mb4' );
		$date = date( 'Y-m-d H:i:s' );
		$handle = fopen( Config::get()->dataprovider->pricelist, 'r' );
		$i = 0;
		$header = array();
		$items = array();
		while( ( $line = fgets( $handle ) ) !== false ){
			//if($i === 5) break;
			$line = trim( $line );
			$item = explode( ';', $line );
			if( $i === 0 ){
				$header = $item;
			}
			else{
				//$items[] = array_combine( $header, $item );
				$stmt = $sql->prepare( 'INSERT INTO catalog ( 
					uid, 
					status, 
					created, 
					updated, 
					title, 
					description, 
					manufacturer, 
					mpn, 
					ean, 
					taric,
					unspc,
					eclass,
					weeenr,
					tax,
					category1,
					category2,
					category3,
					category4,
					category5,
					weight,
					width,
					depth,
					height,
					volweight,
					iseol,
					eoldate,
					minorderqty,
					maxorderqty,
					copyrightcharge,
					shipping,
					sku,
					iscondition,
					availability,
					stock,
					stocketa,
					price ) 
					VALUES ( 
					:uid, 
					:status, 
					:created, 
					:updated, 
					:title, 
					:description, 
					:manufacturer, 
					:mpn, 
					:ean, 
					:taric,
					:unspc,
					:eclass,
					:weeenr,
					:tax,
					:category1,
					:category2,
					:category3,
					:category4,
					:category5,
					:weight,
					:width,
					:depth,
					:height,
					:volweight,
					:iseol,
					:eoldate,
					:minorderqty,
					:maxorderqty,
					:copyrightcharge,
					:shipping,
					:sku,
					:iscondition,
					:availability,
					:stock,
					:stocketa,
					:price
				 	) '
				);
				$uid = Utils::generateUID();
				$isTrue = 1;
				$isFalse = 0;
				$updated = null;
				$tax = ( $item[32] == 1 ) ? 0 : 1;
				$isEOL = ( $item[25] == 0 ) ? 0 : 1;
				$shipping = self::getShippingType( $item[20] );
				$availability = self::getAvailability( $item[10] );// item 10 oder 19 is doppelt muss beim listenabruf konsolididert werden
				$title = self::cleanString( $item[4] );
				$description = self::cleanString( $item[26] );
				$manufacturer = self::cleanString( $item[5] );
				$mpn = self::cleanString( $item[6] );
				$category1 = self::cleanString( $item[9] );
				$condition = ( $item[18] == 'REF' ) ? 1 : 0;
				$eoldate = self::validateDate( $item[21] ); //( $item[21] === '' || $item[21] == 0 ) ? null : $item[21];
				$stocketa = self::validateDate( $item[29] ); //( $item[29] === '' || $item[29] == 0 || $item[29] == 1 ) ? null : $item[29];
				$volweight = self::cleanVolweight( $item[33] ); //( $item[33] === '' || $item[33] == 0 ) ? 0 : $item[33];
				$taric = self::validateTARIC( $item[8] );
				$stmt->bindParam( ':uid', $uid, \PDO::PARAM_STR );
				$stmt->bindParam( ':status', $isTrue, \PDO::PARAM_INT );
				$stmt->bindParam( ':created', $date, \PDO::PARAM_STR );
				$stmt->bindParam( ':updated', $updated, \PDO::PARAM_STR );
				$stmt->bindParam( ':title', $title, \PDO::PARAM_STR );
				$stmt->bindParam( ':description', $description, \PDO::PARAM_STR );
				$stmt->bindParam( ':manufacturer', $manufacturer, \PDO::PARAM_STR );
				$stmt->bindParam( ':mpn', $mpn, \PDO::PARAM_STR );
				$stmt->bindParam( ':ean', $item[7], \PDO::PARAM_STR );
				$stmt->bindParam( ':taric', $taric, \PDO::PARAM_INT );
				$stmt->bindParam( ':unspc', $isFalse, \PDO::PARAM_INT );
				$stmt->bindParam( ':eclass', $isFalse, \PDO::PARAM_INT );
				$stmt->bindParam( ':weeenr', $item[34], \PDO::PARAM_INT );
				$stmt->bindParam( ':tax', $tax, \PDO::PARAM_INT );
				$stmt->bindParam( ':category1', $category1, \PDO::PARAM_STR );
				$stmt->bindParam( ':category2', $isFalse, \PDO::PARAM_STR );
				$stmt->bindParam( ':category3', $isFalse, \PDO::PARAM_STR );
				$stmt->bindParam( ':category4', $isFalse, \PDO::PARAM_STR );
				$stmt->bindParam( ':category5', $isFalse, \PDO::PARAM_STR );
				$stmt->bindParam( ':weight', $item[11], \PDO::PARAM_STR );
				$stmt->bindParam( ':width', $item[12], \PDO::PARAM_STR );
				$stmt->bindParam( ':depth', $item[13], \PDO::PARAM_STR );
				$stmt->bindParam( ':height', $item[14], \PDO::PARAM_STR );
				$stmt->bindParam( ':volweight', $volweight, \PDO::PARAM_STR );
				$stmt->bindParam( ':iseol', $isEOL, \PDO::PARAM_INT );
				$stmt->bindParam( ':eoldate', $eoldate, \PDO::PARAM_STR );
				$stmt->bindParam( ':minorderqty', $item[28], \PDO::PARAM_INT );
				$stmt->bindParam( ':maxorderqty', $item[27], \PDO::PARAM_INT );
				$stmt->bindParam( ':copyrightcharge', $item[22], \PDO::PARAM_STR );
				$stmt->bindParam( ':shipping', $shipping, \PDO::PARAM_INT );
				$stmt->bindParam( ':sku', $item[0], \PDO::PARAM_INT );
				$stmt->bindParam( ':iscondition', $condition, \PDO::PARAM_INT );
				$stmt->bindParam( ':availability', $availability, \PDO::PARAM_INT );
				$stmt->bindParam( ':stock', $item[1], \PDO::PARAM_INT );
				$stmt->bindParam( ':stocketa', $stocketa, \PDO::PARAM_STR );
				$stmt->bindParam( ':price', $item[3], \PDO::PARAM_STR );
				$stmt->execute();
			}
			$i++;
			$logger->incrementRow();
		}
		fclose( $handle );
		$logger->end();
		print "<br> import of $i rows";
	}

	private static function getShippingType( $data ){
		switch( $data ):
			case 1:
				$d = 1;
			break;
			case 'SPERRGUT':
				$d = 2;
			break;
			case 'PALETTENWARE':
				$d = 3;
			break;
			default:
				$d = 1;
		endswitch;
		return $d;
	}

	private static function getAvailability( $data ){
		switch( $data ):
			case 'B':
				$d = 1;
			break;
			case 'K':
				$d = 0;
			break;
			case 'W':
				$d = 2;
			break;
			default:
				$d = 1;
		endswitch;
		return $d;
	}

	private static function validateDate( $date, $format = 'Y-m-d'){
		$d = \DateTime::createFromFormat( $format, $date );
		if( $d && $d->format($format) === $date ){
			return $date;
		}
		return null;
	}

	private static function validateTARIC( $input ){
		// Länge: exakt 8 Ziffern (manchmal + 2 nationale Zusatzziffern → 10-stellig für z. B. DE)
		// Nur Ziffern, keine Buchstaben, keine Sonderzeichen
		// Entferne Leerzeichen oder Trennzeichen
		$clean = preg_replace( '/\D/', '', $input );
		if( preg_match( '/^\d{8}(\d{2})?$/', $clean ) === 1 ){
			return $input;
		}
		return 0;
	}

	private static function cleanVolweight( $input ){
		$volweight = floatval( $input );
		if( $volweight > 999.99 ){
			$volweight = 0; // oder Fehler werfen??
		}
		return $volweight;
	}

	private static function cleanString( string $input ) : string {
		$input = str_replace( '"', '', $input );
		return self::replace_invalid_utf8_literals( $input );
	}

	private static function replace_invalid_utf8_literals( string $input ) : string {
		// Replaces known problematic sequences (e.g., lone 0xC3) with ?
		$input = preg_replace('/[\xC0-\xC1\xF5-\xFF]/', '?', $input); // ungültige UTF-8-Leitbytes
		$input = mb_convert_encoding($input, 'UTF-8', 'UTF-8'); // sicherstellen, dass Rest gültig bleibt
		return $input;
	}

}
?>