<?php
	require_once('algorithms.php');

	class BccResponse {

		public $response = '';

		public $headers = '';

		public $status = '';
	}

	function httpSendRequest(
		$url,
		$headers,
		$method = 'GET',
		$body
		= NULL
	) {
		$curlp = curl_init();
		curl_setopt($curlp, CURLOPT_URL, $url);
		curl_setopt($curlp, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curlp, CURLOPT_CUSTOMREQUEST, $method);
		if ($body != NULL) {
			$bodyStr = json_encode($body);
			curl_setopt($curlp, CURLOPT_POSTFIELDS, $bodyStr);
		}
		curl_setopt($curlp, CURLINFO_HEADER_OUT, 1);
		curl_setopt($curlp, CURLOPT_RETURNTRANSFER, 1);
		$rp               = curl_exec($curlp);
		$result           = new BccResponse();
		$result->response = $rp;
		$result->headers  = curl_getinfo($curlp, CURLINFO_HEADER_OUT);
		$result->status   = curl_getinfo($curlp, CURLINFO_HTTP_CODE);
		curl_close($curlp);
		return $result;
	}


	function getBccList($accessId, $secretKey, $region) {
		$method        = 'GET';
		$host          = "bcc.{$region}.baidubce.com";
		$path          = '/v2/instance';
		$timeStamp     = time();
		$bceTime       = getCanonicalTime($timeStamp);
		$url           = "http://{$host}{$path}";
		$params        = [];
		$headers       = [
			'host'       => $host,
			'x-bce-date' => $bceTime,
		];
		$headersToSign = ['host', 'x-bce-date'];
		$authorization = generateAuthorization($accessId, $secretKey,
			$timeStamp, $path,
			$params, $method, $headers, $headersToSign);
		$httpHeaders   = [
			"Host: {$host}",
			"Authorization: {$authorization}",
			"x-bce-date: {$bceTime}",
		];
		$response      = httpSendRequest($url,
			$httpHeaders);
		return $response;
	}


	function getBccInfo($accessId, $secretKey, $region, $instance) {
		$method        = 'GET';
		$host          = "bcc.{$region}.baidubce.com";
		$path          = "/v2/instance/{$instance}";
		$timeStamp     = time();
		$bceTime       = getCanonicalTime($timeStamp);
		$url           = "http://{$host}{$path}";
		$params        = [];
		$headers       = [
			'host'       => $host,
			'x-bce-date' => $bceTime,
		];
		$headersToSign = ['host', 'x-bce-date'];
		$authorization = generateAuthorization($accessId, $secretKey,
			$timeStamp, $path,
			$params, $method, $headers, $headersToSign);
		$httpHeaders   = [
			"Host: {$host}",
			"Authorization: {$authorization}",
			"x-bce-date: {$bceTime}",
		];
		$response      = httpSendRequest($url,
			$httpHeaders);
		return $response;
	}
