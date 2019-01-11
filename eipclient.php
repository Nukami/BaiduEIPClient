<?php
	require_once('algorithms.php');

	class EipResponse {
		public $response = '';
		public $headers = '';
		public $status = '';
	}

	$BY_TRAFFIC   = 'ByTraffic';
	$BY_BANDWIDTH = 'ByBandwidth';

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
		$result           = new EipResponse();
		$result->response = $rp;
		$result->headers  = curl_getinfo($curlp, CURLINFO_HEADER_OUT);
		$result->status   = curl_getinfo($curlp, CURLINFO_HTTP_CODE);
		curl_close($curlp);
		return $result;
	}

	function getEipList($accessId, $secretKey, $region) {
		$method        = 'GET';
		$host          = "eip.{$region}.baidubce.com";
		$path          = '/v1/eip';
		$timeStamp     = time();
		$url           = "http://{$host}{$path}";
		$params        = [];
		$headers       = [
			'host'       => $host,
			'x-bce-date' => getCanonicalTime($timeStamp),
		];
		$headersToSign = ['host', 'x-bce-date'];
		$authorization = generateAuthorization($accessId, $secretKey,
			$timeStamp, $path,
			$params, $method, $headers, $headersToSign);
		$httpHeaders   = [
			'Host: ' . $host,
			'Authorization: ' . $authorization,
			'x-bce-date: ' . getCanonicalTime($timeStamp),
		];
		$response      = httpSendRequest($url,
			$httpHeaders);
		return $response;
	}

	function purchaseNewEip(
		$accessId,
		$secretKey,
		$region,
		$name = '',
		$bandwidthInMbps = 2,
		$billingMethod = 'ByTraffic'
	) {
		$method        = 'POST';
		$host          = "eip.{$region}.baidubce.com";
		$path          = '/v1/eip';
		if ($name ===''){
			$name = generateEipName();
		}
		$body          = [
			'name'            => $name,
			'bandwidthInMbps' => $bandwidthInMbps,
			'billing'         => [
				'paymentTiming' => 'Postpaid',
				'billingMethod' => $billingMethod,
			],
		];
		$clientToken   = generateClientToken(json_encode($body));
		$url           = "http://{$host}{$path}?clientToken={$clientToken}";
		$params        = ['clientToken' => $clientToken];
		$timeStamp     = time();
		$bceTime       = getCanonicalTime($timeStamp);
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
			'Content-Type: application/json; charset=utf-8',
			"x-bce-date: {$bceTime}",
		];
		$response      = httpSendRequest($url,
			$httpHeaders, $method, $body);
		return $response;
	}

	function bindInstance(
		$accessId,
		$secretKey,
		$region,
		$eip,
		$instanceId,
		$instanceType = 'BCC'
	) {
		$method        = 'PUT';
		$host          = "eip.{$region}.baidubce.com";
		$path          = "/v1/eip/{$eip}";
		$action        = 'bind';
		$body          = [
			'instanceType' => $instanceType,
			'instanceId'   => $instanceId,
		];
		$bodyJson = json_encode($body);
		$timeStamp     = time();
		$clientToken   = generateClientToken("{$bodyJson}/{$timeStamp}");
		$params        = [$action => '', 'clientToken' => $clientToken];
		$bceTime       = getCanonicalTime($timeStamp);
		$headers       = [
			'host'       => $host,
			'x-bce-date' => $bceTime,
		];
		$headersToSign = ['host', 'x-bce-date'];
		$url
		               = "http://{$host}{$path}?{$action}&clientToken={$clientToken}";
		$authorization = generateAuthorization($accessId, $secretKey,
			$timeStamp, $path,
			$params, $method, $headers, $headersToSign);
		$httpHeaders   = [
			"Host: {$host}",
			"Authorization: {$authorization}",
			"x-bce-date: {$bceTime}",
			'Content-Type: application/json; charset=utf-8',
		];
		$response      = httpSendRequest($url,
			$httpHeaders, $method, $body);
		return $response;
	}


	function unbindInstance(
		$accessId,
		$secretKey,
		$region,
		$eip
	) {
		$method        = 'PUT';
		$host          = "eip.{$region}.baidubce.com";
		$path          = "/v1/eip/{$eip}";
		$action        = 'unbind';
		$timeStamp     = time();
		$clientToken = generateClientToken("/{$timeStamp}");
		$params        = [$action => '', 'clientToken' => $clientToken];
		$bceTime       = getCanonicalTime($timeStamp);
		$headers       = [
			'host'       => $host,
			'x-bce-date' => $bceTime,
		];
		$headersToSign = ['host', 'x-bce-date'];
		$url
		               = "http://{$host}{$path}?{$action}&clientToken={$clientToken}";
		$authorization = generateAuthorization($accessId, $secretKey,
			$timeStamp, $path,
			$params, $method, $headers, $headersToSign);
		$httpHeaders   = [
			"Host: {$host}",
			"Authorization: {$authorization}",
			"x-bce-date: {$bceTime}",
		];
		$response      = httpSendRequest($url,
			$httpHeaders, $method);
		return $response;
	}


	function deleteEip(
		$accessId,
		$secretKey,
		$region,
		$eip
	) {
		$method        = 'DELETE';
		$host          = "eip.{$region}.baidubce.com";
		$path          = "/v1/eip/{$eip}";
		$timeStamp     = time();
		$clientToken = generateClientToken("/{$timeStamp}");
		$params        = ['clientToken' => $clientToken];
		$bceTime       = getCanonicalTime($timeStamp);
		$headers       = [
			'host'       => $host,
			'x-bce-date' => $bceTime,
		];
		$headersToSign = ['host', 'x-bce-date'];
		$url
			= "http://{$host}{$path}?clientToken={$clientToken}";
		$authorization = generateAuthorization($accessId, $secretKey,
			$timeStamp, $path,
			$params, $method, $headers, $headersToSign);
		$httpHeaders   = [
			"Host: {$host}",
			"Authorization: {$authorization}" ,
			"x-bce-date: {$bceTime}"
		];
		$response      = httpSendRequest($url,
			$httpHeaders, $method);
		return $response;
	}