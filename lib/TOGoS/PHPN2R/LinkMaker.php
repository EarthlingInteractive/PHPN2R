<?php

class TOGoS_PHPN2R_LinkMaker {
	protected $rp;
	/**
	 * @param string $rp Root path prefix; relative URI, including trailing slash, to uri-res/
	 */
	public function __construct( $rp ) {
		$this->rp = $rp;
	}
	public function componentUrl( $comp, $urn, $filenameHint=null ) {
		if( $comp == 'raw' and $filenameHint === null ) {
			return $this->rp.'N2R?'.$urn;
		} else {
			return $this->rp.$comp.'/'.$urn.($filenameHint === null ? '' : '/'.$filenameHint);
		}
	}
	public function htmlLink( $url, $text ) {
		return "<a href=\"".htmlspecialchars($url)."\">".htmlspecialchars($text)."</a>";
	}
	public function htmlLinkForUrn( $urn, $filenameHint, $text ) {
		if( $text === null ) $text = $urn;
		if( preg_match('/^(x-parse-rdf|(?:x-)?(?:rdf-)?subject(?:-of)?):(.*)$/',$urn,$bif) ) {
			$subjectScheme = $bif[1];
			$blobUrn = $bif[2];
			if( $text == $urn ) {
				return
					$this->htmlLink($this->componentUrl('browse', $blobUrn, $filenameHint), $subjectScheme).':'.
					$this->htmlLinkForUrn($blobUrn, $filenameHint, $blobUrn);
			} else {
				return $this->htmlLink($this->componentUrl('browse', $blobUrn, $filenameHint), $text);
			}
		} else {
			return $this->htmlLink($this->componentUrl('raw', $urn, $filenameHint), $text);
		}
	}
	public function urnHtmlLinkReplacementCallback( $matches ) {
		return $this->htmlLinkForUrn( $matches[0], null, $matches[0] );
	}
}