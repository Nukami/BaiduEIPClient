<?php
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT);

	require_once('conf.php');
	require_once('eipclient.php');

	$params['region'] = isset($_GET['region']) ? strtolower($_GET['region'])
		: 'bj';
	if (!in_array($params['region'], ['bj', 'gz', 'su', 'bd'])) {
		$params['region'] = 'bj';
	}
	if (!isset($_GET['instance'])) {
		echo 'Instance param is required!';
		return;
	}
	$params['instance'] = $_GET['instance'];
	$params['bandwidth'] = isset($_GET['bandwidth']) ? $_GET['bandwidth'] : 2;
	$params['billing'] = isset($_GET['billing']) ? $_GET['billing']
		: 'ByTraffic';
	$params['retry'] = isset($_GET['retry']) ? $_GET['retry'] : 5;
	$time = time();

	//Get EIP list
	$tmp = getEipList($ACCESS_KEY, $SECRET_KEY, $params['region']);
	if ($tmp->status != 200) {
		echo "Error while getting Eip list with Error code {$tmp->status}: {$tmp->response
}\n<br/>";
		showUsedTime($time);
		return;
	}
	$eipList = json_decode($tmp->response)->eipList;
	$output  = json_encode($eipList);
	echo "Eip List: \n<br/>{$output}\n<br/>";

	// Unbind eip
	foreach ($eipList as $eip) {
		if ($eip->instanceId === $params['instance']
			&& $eip->paymentTiming
			!= 'Prepaid'
			|| substr($eip->name, 0, 2) === 'e-'
			&& $eip->status === 'creating') {
			//unbind
			$tmp = unbindInstance($ACCESS_KEY, $SECRET_KEY, $params['region'],
				$eip->eip);
			if ($tmp->status != 200) {
				echo "Error while unbind Eip {$eip->eip} with Error code {$tmp->status}: {$tmp->response}\n<br/>";
				showUsedTime($time);
//				return;
			}else{
				echo "Successful unbind Eip {$eip->eip} \n<br/>";
				showUsedTime($time);
			}
		}
	}

	// Delete unused eip
	foreach ($eipList as $eipInfo) {
		if ($eipInfo->instanceId === NULL
			&& $eipInfo->paymentTiming
			!= 'Prepaid') {
			//delete unused EIP
			$tmp = deleteEip($ACCESS_KEY, $SECRET_KEY, $params['region'],
				$eipInfo->eip);
			if ($tmp->status != 200) {
				echo "Error while delete Eip {$eipInfo->eip} with Error code {$tmp->status}: {$tmp->response}\n<br/>";
				showUsedTime($time);
				//				return;
			}
			else {
				echo "Successful delete Eip {$eipInfo->eip} \n<br/>";
				showUsedTime($time);
			}
		}
	}

	// Purchase new EIP
	$tmp = purchaseNewEip($ACCESS_KEY, $SECRET_KEY, $params['region'],
		generateEipName(), $params['bandwidth'], $params['billing']);
	if ($tmp->status != 200) {
		echo "Error while purchase new Eip with Error code {$tmp->status}: {$tmp->response}\n<br/>";
		showUsedTime($time);
		return;
	}
	$eip = json_decode($tmp->response)->eip;
	echo "Successful purchase new Eip: {$eip}\n<br/>";
	showUsedTime($time);

	// Bind to instance
	for ($i = 0; $i < $params['retry']; $i++) {
		$tmp = bindInstance($ACCESS_KEY, $SECRET_KEY, $params['region'], $eip,
			$params['instance']);
		if ($tmp->status != 200) {
			echo "[{$i}] Error while binding Eip with Error code {$tmp->status}: {$tmp->response}\n<br/>";
			showUsedTime($time);
			sleep(2);
			if ($i > $params['retry']) {
				return;
			}
			continue;
		}
		echo "Successful bind Eip to instance!\n<br/>";
		showUsedTime($time);
		break;
	}
	//
	//	//Get New EIP list
	//	$tmp = getEipList($ACCESS_KEY, $SECRET_KEY, $params['region']);
	//	if ($tmp->status != 200) {
	//		echo "Error while getting new Eip list with Error code {$tmp->status}: {$tmp->response}\n<br/>";
	//		showUsedTime($time);
	//		return;
	//	}
	//	$eipList = json_decode($tmp->response)->eipList;
	//
	//
	//	// Delete unused eip
	//	foreach ($eipList as $eipInfo) {
	//		if ($eipInfo->instanceId === NULL && $eipInfo->eip != $eip && $eip->paymentTiming
	//			!= 'Prepaid') {
	//			//delete unused EIP
	//			$tmp = deleteEip($ACCESS_KEY, $SECRET_KEY, $params['region'],
	//				$eipInfo->eip);
	//			if ($tmp->status != 200) {
	//				echo "Error while delete Eip {$eipInfo->eip} with Error code {$tmp->status}: {$tmp->response}\n<br/>";
	//				showUsedTime($time);
	//				//				return;
	//			}else{
	//				echo "Successful delete Eip {$eipInfo->eip} \n<br/>";
	//				showUsedTime($time);
	//			}
	//		}
	//	}

	function showUsedTime($time) {
		$usedTime = time() - $time;
		echo "Time:  {$usedTime} s\n<br/>";
	}