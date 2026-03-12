<?php
/**
 * GITHUB PUSH via Contents API (file by file - more reliable)
 * Works with repos of any state (empty or not)
 */
$token    = getenv('GITHUB_TOKEN') ?: ''; // Set via environment variable - NEVER hardcode tokens!
$owner    = 'poxwarriors-netizen';
$repo     = 'workout-solo-level';
$branch   = 'main';
$base_dir = __DIR__;
$skip_dirs  = ['node_modules', 'env', '.git', '__MACOSX'];
$skip_files = ['push_to_github.php', 'setup_admin.php', 'check_repo.php'];

function gh($method, $url, $data, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: token ' . $token,
            'User-Agent: SoloLevelingPush/2.0',
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($resp, true)];
}

function api($method, $endpoint, $data, $token) {
    return gh($method, "https://api.github.com$endpoint", $data, $token);
}

function scan_files($dir, $base, $skip_dirs, $skip_files) {
    $result = [];
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        if (in_array($item, $skip_dirs) || in_array($item, $skip_files)) continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        $rel  = str_replace('\\', '/', ltrim(str_replace($base, '', $full), '/\\'));
        if (is_dir($full)) $result = array_merge($result, scan_files($full, $base, $skip_dirs, $skip_files));
        elseif (is_file($full)) $result[] = ['rel' => $rel, 'full' => $full];
    }
    return $result;
}

echo "===========================================\n";
echo "  SOLO LEVELING FITNESS -> GITHUB PUSH\n";
echo "===========================================\n\n";

// Verify auth & check scopes
$me = api('GET', '/user', null, $token);
if ($me['code'] !== 200) { echo "ERROR: Bad token (HTTP {$me['code']})\n"; exit(1); }
echo "Authenticated as: " . $me['body']['login'] . "\n";

// Check token scopes
$ch = curl_init('https://api.github.com/user');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true,
    CURLOPT_HTTPHEADER=>['Authorization: token '.$token, 'User-Agent: test'],
    CURLOPT_SSL_VERIFYPEER=>false]);
$raw = curl_exec($ch); curl_close($ch);
preg_match('/X-OAuth-Scopes: (.+)/i', $raw, $sm);
echo "Token scopes: " . trim($sm[1] ?? 'unknown') . "\n\n";

$files = scan_files($base_dir, $base_dir, $skip_dirs, $skip_files);
echo "Files to push: " . count($files) . "\n\n";

$ok = 0; $fail = 0; $skip = 0;

foreach ($files as $i => $f) {
    $num = $i + 1; $total = count($files);
    $content = @file_get_contents($f['full']);
    if ($content === false) { echo "[$num/$total] SKIP (unreadable): {$f['rel']}\n"; $skip++; continue; }
    
    echo "[$num/$total] {$f['rel']} ... ";
    $encoded = base64_encode($content);
    
    // Check if file already exists (need SHA to update)
    $existing = api('GET', "/repos/$owner/$repo/contents/{$f['rel']}?ref=$branch", null, $token);
    $existing_sha = ($existing['code'] === 200) ? ($existing['body']['sha'] ?? null) : null;
    
    $payload = [
        'message' => "feat: add {$f['rel']}",
        'content' => $encoded,
        'branch'  => $branch,
    ];
    if ($existing_sha) $payload['sha'] = $existing_sha;
    
    $result = api('PUT', "/repos/$owner/$repo/contents/{$f['rel']}", $payload, $token);
    
    if (in_array($result['code'], [200, 201])) {
        echo "OK\n"; $ok++;
    } else {
        echo "FAIL (HTTP {$result['code']}): " . ($result['body']['message'] ?? '') . "\n";
        $fail++;
    }
}

echo "\n===========================================\n";
echo " Done! OK: $ok  Failed: $fail  Skipped: $skip\n";
echo "===========================================\n";
if ($ok > 0) {
    echo "\nhttps://github.com/$owner/$repo\n";
    echo "\nSECURITY: Delete push_to_github.php!\n";
}
