<?php
/*
	@name:                    Local CloudFlare DDNS Updater
	@filename:                cloudflare_ddns_remote.php
	@version:                 0.1
	@date:                    September 23, 2017
	
	@author:                  Nycholas F.
	@website:                 https://nycholas.com
	@email:                   nycholas@nycholas.com

	Copyright 2017 Nycholas Fortuna
	
	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at
	
	http://www.apache.org/licenses/LICENSE-2.0
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

// USAGE: http://ip-server/cloudflare_ddns_remote.php?domain=home.example.com&email=cloudflare-account@example.com&key=YOUR_CLOUDFLARE_AUTHKEY&zone=example.com
// Try here if you want: https://nycholas.com/cloudflare_ddns_remote.php
$domain 		= $_GET['domain'];
$authemail		= $_GET['email'];
$authkey		= $_GET['key'];
$zone_name		= $_GET['zone'];

function getUserIP()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    return $ip;
}

$ip = getUserIP();

$ddns_update = array(
    "type" => "A",
    "name" => $domain,
    "content" => $ip,
	"ttl" => 120,
);

// Get Zone ID by Zone Name
$ch = curl_init("https://api.cloudflare.com/client/v4/zones?name=".$zone_name);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'X-Auth-Email: '.$authemail,
	    'X-Auth-Key: '.$authkey,
	    'Content-Type: application/json'
	    ));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$r = json_decode($response, true);
$result = $r['result'];

$zone_id = $result[0]['id'];

// Get Record ID by Zone ID + Domain Name
$ch = curl_init("https://api.cloudflare.com/client/v4/zones/".$zone_id."/dns_records?name=".$domain);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Auth-Email: '.$authemail,
            'X-Auth-Key: '.$authkey,
            'Content-Type: application/json'
            ));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$r = json_decode($response, true);
$result = $r['result'];

$record_id = $result[0]['id'];

// Update record with current ip
$ch = curl_init("https://api.cloudflare.com/client/v4/zones/".$zone_id."/dns_records/".$record_id);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'X-Auth-Email: '.$authemail,
	    'X-Auth-Key: '.$authkey,
	    'Content-Type: application/json'
	    ));
$data_string = json_encode($ddns_update);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);
$r = json_decode($response, true);
$result = $r['result'];

// Print
echo "External IP: ".$ip."<br>"."Zone id: ".$zone_id."<br>"."Record id: ".$record_id."<br>";
echo "<br>";
print("<pre>".print_r($r,true)."</pre>");
?>
