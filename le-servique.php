<?php

class PHPN2R_Repository {
	protected $dataDir;
	
	public function __construct( $dataDir ) {
		$this->dataDir = $dataDir;
	}
	
	function urnToBasename( $urn ) {
		if( preg_match( '/^urn:(?:sha1|bitprint):([0-9A-Z]{32})/', $urn, $bif ) ) {
			return $bif[1];
		}
		return null;
	}
	
	public function findFile( $urn ) {
		$basename = $this->urnToBasename($urn);
		if( $basename === null ) return null;
		
		$first2 = substr($basename,0,2);
		
		$dir = opendir( $this->dataDir );
		$fil = null;
		while( $dir !== false and ($en = readdir($dir)) !== false ) {
			$fil = "{$this->dataDir}/$en/$first2/$basename";
			if( is_file($fil) ) break;
			else $fil = null;
		}
		closedir($dir);
		return $fil;
	}
}

class PHPN2R_Server {
	protected $repo;
	
	public function __construct( $repo ) {
		$this->repo = $repo;
	}
	
	protected function guessFileType( $file, $filenameHint ) {
		if( preg_match('/.ogg$/',$filenameHint) ) {
			// finfo will report the skeleton type, application/ogg :(
			return 'audio/ogg';
		} else if( function_exists('finfo_open') and $finfo = finfo_open(FILEINFO_MIME_TYPE|FILEINFO_MIME_ENCODING) ) {
			$ct = finfo_file( $finfo, $file );
			finfo_close($finfo);
			return $ct;
		} else if( preg_match('/.html?$/i',$filenameHint) ) {
			return 'text/html';
		} else if( preg_match('/.jpe?g$/i',$filenameHint) ) {
			return 'image/jpeg';
		} else if( preg_match('/.png$/i',$filenameHint) ) {
			return 'image/png';
		} else {
			return null;
		}
	}
	
	protected function _serveBlob( $urn, $filenameHint, $sendContent ) {
		if( ($file = $this->repo->findFile($urn)) ) {
			$size = filesize($file);
			
			$ct = null;
			$enc = null;
			$ct = $this->guessFileType( $file, $filenameHint );
			if( $ct == null ) $ct = 'application/octet-stream';
			
			if( is_int($size) ) {
				header("Content-Length: $size");
			}
			header("Content-Type: $ct");
			header('Cache-Control: public');
			header('Expires: '.gmdate(DATE_RFC1123, time() + (3600*24*365)));
			
			if( $sendContent ) {
				readfile($file);
			}
		} else {
			header('HTTP/1.0 404 Blob not found');
			header('Content-Type: text/plain');
			if( $sendContent ) {
				echo "I coulnd't find $urn, bro.\n";
			}
		}
	}
	
	public function serveBlob( $urn, $filenameHint ) {
		$this->_serveBlob( $urn, $filenameHint, true );
	}

	public function serveBlobHeaders( $urn, $filenameHint ) {
		$this->_serveBlob( $urn, $filenameHint, false );
	}
}

function server_la_php_error( $errlev, $errstr, $errfile=null, $errline=null ) {
	if( ($errlev & error_reporting()) == 0 ) return;
	if( !headers_sent() ) {
		header('HTTP/1.0 500 Erreaux');
		header('Content-Type: text/plain');
	}
	echo "HTTP 500!  Server error!\n";
	echo "Error (level $errlev): $errstr\n";
	if( $errfile or $errline ) {
		echo "\n";
		echo "at $errfile:$errline\n";
	}
	exit;
}

function server_la_contenteaux( $urn, $filenameHint ) {
	ini_set('html_errors', false);
	set_error_handler('server_la_php_error');
	
	$config = include('config.php');
	if( $config === false ) {
		header('HTTP/1.0 500 No config.php present');
		header('Content-Type: text/plain');
		echo "'config.php' does not exist or is returning false.\n";
		echo "\n";
		echo "Copy config.php.example to config.php and fix.\n";
		exit;
	}
	$repo = null;
	foreach( $config['repositories'] as $repoPath ) {
		$repo = new PHPN2R_Repository( "$repoPath/data" );
	}
	if( $repo === null ) {
		header('HTTP/1.0 500 No repositories configured');
		header('Content-Type: text/plain');
		echo "No repositories configured!\n";
		exit;
	}
	
	$availableMethods = array("GET", "HEAD", "OPTIONS");
	
	$serv = new PHPN2R_Server( $repo );
	switch( ($meth = $_SERVER['REQUEST_METHOD']) ) {
	case 'GET':
		$serv->serveBlob( $urn, $filenameHint );
		return;
	case 'HEAD':
		$serv->serveBlobHeaders( $urn, $filenameHint );
		return;
	case 'OPTIONS':
		header('HTTP/1.0 200 No repositories configured');
		header('Content-Type: text/plain');
		echo implode("\n", $availableMethods), "\n";
		return;
	default:
		header('HTTP/1.0 405 Method not supported');
		header('Content-Type: text/plain');
		echo "Method '$meth' is not supported by this service.\n";
		echo "\n";
		echo "Allowed methods: ".implode(', ', $availableMethods), "\n";
	}
}
