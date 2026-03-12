<?php
$token = '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';

// Check user
$ch = curl_init('https://api.github.com/user');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true,
    CURLOPT_HTTPHEADER=>['Authorization: token '.$token,'User-Agent: diag','Accept: application/vnd.github.v3+json'],
    CURLOPT_SSL_VERIFYPEER=>false]);
$raw = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
echo "Auth HTTP: $code\n";
preg_match('/X-OAuth-Scopes: (.+)/i',$raw,$sm);
echo "Scopes: ".trim($sm[1]??'NONE')."\n\n";

$body = substr($raw, strpos($raw,"\r\n\r\n")+4);
$d = json_decode($body,true);
echo "GitHub user: ".($d['login']??'?')."\n";
echo "Email: ".($d['email']??'?')."\n\n";

// Test write: create a file
$ch2 = curl_init('https://api.github.com/repos/poxwarriors-netizen/workout-solo-level/contents/test.txt');
curl_setopt_array($ch2,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'PUT',
    CURLOPT_HTTPHEADER=>['Authorization: token '.$token,'User-Agent: diag','Accept: application/vnd.github.v3+json','Content-Type: application/json'],
    CURLOPT_POSTFIELDS=>json_encode(['message'=>'test','content'=>base64_encode('hello')]),
    CURLOPT_SSL_VERIFYPEER=>false]);
$r = curl_exec($ch2); $c = curl_getinfo($ch2,CURLINFO_HTTP_CODE); curl_close($ch2);
echo "Write test HTTP: $c\n";
echo "Response: $r\n";
