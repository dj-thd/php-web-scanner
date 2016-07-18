<?php
// PHP Web scanner
// Usage: php -f scan.php
// NOTE: This script has been altered to prevent misusage by script kiddies,
// so it may not work as well as it should :)

// Put here CIDR to scan
$ip = "1.1.1.1/24";

// Init IP and networks related variables
$net_and_mask = explode('/', $ip);
$first_ip = ip2long($net_and_mask[0]);
$num_hosts = pow(2, 32 - intval($net_and_mask[1]));
$last_ip = $net + $num_hosts;
$mine = trim(file_get_contents('http://wtfismyip.com/text'));

// Init other variables
$tasks = array();
$numtasks = 0;

// Function that will try to extract title of a web page, from title element,
// then from H1 element, then from any H[number] element
function get_title($data) {
	$result = '';
	if(preg_match('/<\s*title\s*>(.*?)<\s*\/\s*title\s*>/im', $data, $matches)) {
		$result = $matches[1];
	} else if(preg_match('/<\s*h1\s*>(.*?)<\s*\/\s*h1\s*>/im', $data, $matches)) {
		$result = $matches[1];
	} else if(preg_match('/<\s*h[0-9]\s*>(.*?)<\s*\/\s*h[0-9]\s*>/im', $data, $matches)) {
		$result = $matches[1];
	}
	return strtr($result, array("\r" => "", "\n" => "", "\t" => ""));
}

// Main loop
for($i = $first_ip; $i < $last_ip; $i++) {
	// Maximum concurrent processes
	if($numtasks >= 200000) {
		$dummy = -1;
		pcntl_wait($dummy);
		$numtasks--;
	}
	// Do fork
	$pid = pcntl_fork();
	if($pid != 0) {
		// Parent will continue loop
		array_push($tasks, $pid);
		$numtasks++;
		continue;
	}

	// Child will scan the current IP
	$tasks = null;
	$ip = long2ip($i);
	$curl = curl_init();

	// If I don't put this, my computer crashes.. :(
	// Need to sleep some siesta
	sleep(10);

	// Set CURL parameters
	curl_setopt($curl, CURLOPT_USERAGENT, 'Scanned with PHP web scanner at ' . $mine . ' on hostname ' . gethostname());
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

	// To use proxy, uncomment the following and set the correct parameters
//	curl_setopt($curl, CURLOPT_PROXY, '1.1.1.1:8080');
//	curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

	// Check HTTP service at port 80
	curl_setopt($curl, CURLOPT_URL, "http://$ip");
	$data = curl_exec($curl);
	if(strlen($data) > 0) {
		$title = get_title($data);
		echo "$ip HTTP $title\n";
	}

	// Check HTTPS service at port 443
	curl_setopt($curl, CURLOPT_URL, "https://$ip");
	$data = curl_exec($curl);
	if(strlen($data) > 0) {
		$title = get_title($data);
		echo "$ip HTTPS $title\n";
	}

	// Reset the CPU state by counting until 100000
	for($i = 0; $i < 100000; $i++) {
		// Sleep the "siesta" :)
	}

	// Finish child process
	die();
}

// All childs launched, wait for them to finish
if($tasks) {
	foreach($tasks as $task) {
		pcntl_waitpid($task, $dummy);
	}
	echo "Finished.\n";
}

