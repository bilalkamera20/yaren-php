<?php
error_reporting(0);
set_time_limit(0);
define('CATALOG_URL', 'https://vavoo.to/vto-cluster/mediahubmx-catalog.json');
$worker_proxies = [
    'https://halil.bilalkamera20.workers.dev',
    'https://adam.bilalkamera20.workers.dev',
    'https://ner.bilalkamera20.workers.dev',
    'https://nur.bilalkamera20.workers.dev',
    'https://vavoo-iptv-proxy.bilalkamera20.workers.dev',
    'https://nernur.bilalkamera20.workers.dev',
    'https://balkica.bilalkamera20.workers.dev',
    'https://bilal.bilalkamera20.workers.dev',
    'https://vav20.bilalkamera20.workers.dev',
    'https://hmeb.bilalkamera20.workers.dev'
];

function get_vavoo_signature() {
    return ""; 
}

function main() {
    global $worker_proxies;
    $signature = get_vavoo_signature();
    $ch = curl_init(CATALOG_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: MediaHubMX/3.0.2',
        'Accept: application/json',
        'Content-Type: application/json; charset=utf-8',
        'X-MediaHubMX-Signature: ' . $signature,
        'Connection: keep-alive'
    ]);

    $cursor = 0;
    $has_next = true;
    $seen_cursors = [];
    $page_count = 0;
    $max_pages = 200;
    $payload = [
        "language" => "tr",
        "region" => "TR",
        "catalogId" => "iptv",
        "id" => "iptv",
        "adult" => false,
        "search" => "",
        "sort" => "name",
        "filter" => new stdClass(),
        "clientVersion" => "3.0.2"
    ];

    $output = "#EXTM3U\n"; // Başlık

    while ($has_next && $page_count < $max_pages) {
        $page_count++;
        $payload["cursor"] = $cursor;
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        if ($response && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            $json_start = strpos($response, '{');
            if ($json_start !== false) {
                $response = substr($response, $json_start);
            }
            $data = json_decode($response, true);
            $items = isset($data['items']) ? $data['items'] : [];
            
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                if (!isset($item['url']) || empty($item['url'])) continue;
                $name = isset($item['name']) ? $item['name'] : 'Unknown';
                $url = $item['url'];
                $group = isset($item['group']) && !empty($item['group']) ? $item['group'] : 'Genel';
                if (strcasecmp($group, 'Turkey') === 0 && preg_match('/bein|exxen|spor/i', $name)) {
                    $group = 'TR SPOR';
                }
                $random_proxy = $worker_proxies[array_rand($worker_proxies)];
                $proxied_url = $random_proxy . "/?url=" . urlencode($url) . "&master&transport=http&.m3u8";
                $output .= '#EXTINF:-1 group-title="' . $group . '",' . $name . "\n" . $proxied_url . "\n";
            }
            $next_cursor = isset($data['nextCursor']) ? $data['nextCursor'] : null;
            if (!$next_cursor || in_array($next_cursor, $seen_cursors)) {
                $has_next = false;
            } else {
                $seen_cursors[] = $cursor;
                $cursor = $next_cursor;
            }
        } else {
            break;
        }
    }
    curl_close($ch);

    // Çıktıyı dosyaya yaz
    file_put_contents('nernur.txt', $output);
}

main();
?>
