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

class ImportLogger {
	private string $logfile;
	private float $startTime;
	private int $importedRows = 0;

	public function __construct( string $logfile = 'import.log' ) {
		$this->logfile = $logfile;
	}

	public function start( string $taskName = 'Import' ) : void {
		$this->startTime = microtime( true );
		$this->log( "=== START: $taskName ===" );
		$this->log( "Startzeit: " . date( 'Y-m-d H:i:s' ) );
	}

	public function incrementRow() : void {
		$this->importedRows++;
	}

	public function end() : void {
		$duration = microtime(true) - $this->startTime;
		$this->log( "Importierte Zeilen: $this->importedRows" );
		$this->log( "Endzeit: " . date( 'Y-m-d H:i:s' ) );
		$this->log( "Dauer: " . round( $duration, 2 ) . " Sekunden" );
		$this->log( "=== ENDE ===\n" );
	}

	private function log( string $message ) : void {
		file_put_contents( $this->logfile, $message . PHP_EOL, FILE_APPEND );
	}
}
?>