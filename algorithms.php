<?php
	function generateClientToken($params) {
		$md5 = md5($params);
		$tmp = substr($md5, 0, 8) . '-' . substr($md5, 8, 4) . '-'
			. substr($md5, 12, 4) . '-'
			. substr($md5, 16, 4) . '-' . substr($md5, 20, 12);
		return $tmp;
	}

	function generateEipName($length = 6) {
		require_once('chars.php');
		$keys   = array_rand(CharsSet::$LOWER_CHARS, $length);
		$word   = '';
		for ($i = 0; $i < $length; $i++) {
			$word .= CharsSet::$LOWER_CHARS[$keys[$i]];
		}
		return "e-{$word}";
	}

	function generateAuthorization(
		$accessId,
		$secretKey,
		$timeStamp,
		$path,
		$params,
		$httpMethod,
		$headers,
		$headerToSign
	) {
		$authString = generateAuthString($accessId, $timeStamp);
		$signature  = generateSignature($authString, $secretKey, $path,
			$params, $httpMethod, $headers, $headerToSign);
		$authorization = "{$authString}/";
		if ($headerToSign != NULL){
			sort($headerToSign);
			$authorization .= join(';', $headerToSign);
		}
		$authorization.= "/{$signature}";
		return $authorization;
	}

	function generateSignature(
		$authString,
		$secretKey,
		$path,
		$params,
		$httpMethod,
		$headers,
		$headerToSign
	) {
		$signKey      = generateSignKey($authString, $secretKey);
		$path         = normalizeString($path);
		$queryString  = generateQueryString($params);
		$headerString = generateHeaderString($headers, $headerToSign);
		$stringToSign = $httpMethod . "\n" . $path . "\n" . $queryString . "\n"
			. $headerString;
		$signature    = hash_hmac('SHA256', $stringToSign, $signKey);
		return $signature;
	}

	function generateSignKey($secretKey, $authString) {
		return hash_hmac('SHA256', $secretKey, $authString);
	}

	function generateAuthString($accessId, $timeStamp) {
		return "bce-auth-v1/{$accessId}/" . getCanonicalTime($timeStamp)
			. '/1800';
	}

	function getCanonicalTime($time = -1) {
		return date('Y-m-d\TH:i:s\Z', $time === -1 ? time() : $time);
	}

	function normalizeString($str, $encodingSlash = FALSE) {
		$tmp = urlencode($str);
		if ($encodingSlash === FALSE) {
			$tmp = str_replace('%2F', '/', $tmp);
		}
		return $tmp;
	}

	function generateQueryString($params) {
		ksort($params);
		$tmp = '';
		foreach ($params as $key => $value) {
			if(gettype($key) === 'integer'){
				$tmp .= "{$value}=&";
			}else{
				$tmp .= "{$key}=" . normalizeString($value, TRUE) .'&';
			}
		}
		return substr($tmp, 0, strlen($tmp) - 1);
	}

	function generateHeaderString($headers, $headersToSign) {
		ksort($headers);
		$headersToSign = !isset($headersToSign) ? [
			'host',
			'content-md5',
			'content-length',
			'content-type',
		] : $headersToSign;
		$tmp           = '';
		foreach ($headers as $key => $value) {
			if (in_array($key, $headersToSign, TRUE) or strpos($key,
					'x-bce-') === 0) {
				$tmp .= normalizeString(strtolower($key)) . ':' .
					normalizeString($value,
						TRUE)
					. "\n";
			}
		}
		return substr($tmp, 0, strlen($tmp) - 1);
	}