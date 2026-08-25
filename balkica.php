<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');
set_time_limit(0);

define('CATALOG_URL', 'https://vavoo.to/vto-cluster/mediahubmx-catalog.json');
define('OUTPUT_FILE', 'nernur.txt');

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

// -- Temizleme ve Kategorizasyon Yardımcıları -----------------------------

function clean_channel_name($name) {
    // İsim başındaki "4K TR:", "HEVC TR:", "TR:" gibi tüm kalıpları siler
    $s = preg_replace('/^\s*(?:[A-Z0-9-]+\s+)*TR:\s*/i', '', $name);
    
    // Kalite takılarını kategorizasyon öncesi geçici temizlik için hafifletir
    $s = preg_replace('/\s*\.(?:b|c|s)\b/i', '', $s);
    
    // Çift boşlukları düzenler
    $s = preg_replace('/\s+/', ' ', $s);
    
    return trim($s);
}

function normalize_for_category($name) {
    $s = clean_channel_name($name);
    // Kategorizasyon filtrelerinin doğru eşleşmesi için ek kalite etiketlerini kaldırır
    $s = preg_replace('/\s+(?:UHD|FHD|HD\+|HD|SD|HEVC|RAW|H265|H\.265|FEED)(?=\s|$)/i', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function categorize_channel($name) {
    $s = normalize_for_category($name);

    $rules = [
        'TR SPOR' => '/\b(BEIN SPO[RT]{0,3}S?|BEIN 1|S[- ]?SPORTS?|S SPORT|SPOR SMART|EUROSPORT|NBA|TJK TV|TIVIBU ?SPOR|TIVIBUSPOR|TRT SPOR|TABII SPOR|EXXEN SPO[RT]?|HT SPOR|EKOL SPOR|SPORTS TV|IDMAN TV|GALATASARAY TV|FB TV|GS TV|SARAN SPORT|SMART SPOR|SPOR|SPORT)\b/i',
        'TR ÇOCUK' => '/\b(CARTOON|BOOMERANG|DISNEY|NICK(?:ELODEON|TOONS|JR|JUNIOR)?|BABY ?TV|BABYTV|M[İI]?N ?KA|MINIKA|POKEMON|POKÉMON|ANIMATION|ANIMASYON|TRT ?[ÇC]?OCUK|[ÇC]OCUK|BEN ?10|ANGRY BIRDS|CAILLOU|PEPPA|PEPE|HEIDI|SIRINLER|TOM & JERRY|SPIDERMAN|BARBIE|PIJAMA|PIRIL|RAFADAN|KELOGLAN|KUKULI|KUKILI|KOSTEBEK|CHICKY|BOOBA|WAKFU|GABBY|TAYO|NILOYA|PISI|LEYLEK|MASAL|CANIM KARDESIM|ADIBESA|MOMO|ALVIN|VIKINGLER|TRANSFORMERS|TROL AVCILARI|SMART COCUK|ILAHI COCUK|CILGIN ORMAN|KRAL SAKIR|SERCE KUS|ITFAYECI SAM|MUFFETIS|MAYMUNLAR|ELIF VE|ELIFIN|MIMOCAN|HAPSUU|RUYA TRENI|MASA KOCAAYI|PAK PIRPIR|LIMON ZEYTIN|GONCA TV|NASREDDIN|SEKER HOCA|SEVIMLI DOSTLAR|PAW PETROL|OSCAR COLLERDE|CBEEBIES|DUCK TV|JIM ?JAM|ENGLISH CLUB TV|EBA TV|PATRON BEBEK|DA VINC KIDS|DA VINCI KIDS)\b/i',
        'TR BELGESEL' => '/\b(DISCOVERY|NATIONAL GEOGRAPHIC|NAT ?GEO|HISTORY|ANIMAL PLANET|DA VINCI|VIASAT|BBC EARTH|LOVE NATURE|TRT BELGESEL|EPIC DRAMA|TARIH TV|TARIM TV|TGRT BELGESEL|INVESTIGATION|DMAX|DOCUBOX|DOCU SCREEN|SCIENCE|IZ TV|YABAN|OUTDOOR|CHASSE|ANIMAUX|AGRO TV|CIFTCI TV|REDBULL TV|TLC)\b/i',
        'TR SİNEMA' => '/\b(SINEMA|S[İI]NEMA|CINEMA|SINEMAX|SINEVIZYON|MOVIES?|MOVIEMAX|MOVIESMART|BEIN MOVIES|BEIN BOX|BOX OFFICE|FX|FX HD|YESILCAM|YE[ŞS]ILCAM|GLOBAL BOX|PROTURK|FIX CINEMA|KINGBOX|ARENA BOX|SHOWMAX|SHOW MAX|REAL BOX|SMART BOX|FILMBOX|HORROR|OSCAR|KEMAL SUNAL|007|CINE ?1|AKSIYON|KORKU|DRAM|WESTERN|BILIM ?KURGU|SAVAS|IMBD|IMDB|FILM)\b/i',
        'TR DİZİ' => '/\b(SER[İI]ES|DIZI|BEIN SERIES|D[İI]Z[İI] ?SMART|DIZISMART)\b/i',
        'TR HABER' => '/\b(HABER|NEWS|BLOOMBERG|CNN|EKOTURK|EKO ?T[UÜ]RK|EKOL|A ?PARA|APARA|PARANIN|HALK TV|TELE ?1|SOZCU|SZC|BENGU ?T[UÜ]RK|BENGUTURK|TRT WORLD|DHA|LIDER HABER|FLASH HABER|MEDYA HABER|GLOBAL HABER|TRABZON HABER|BEIN SPORTS HABER|T[UÜ]RKHABER|HABERT[UÜ]RK|HABERT RK|ARTI TV)\b/i',
        'TR MÜZİK' => '/\b(POWER T[UÜ]RK|POWER ?TV|POWERTURK|POWER|KRAL POP|KRAL ?TV|KRAL|TRT M[UÜ]?Z[İI]?K|TRT MUZIK|NR ?1|NUMBER ?1|NUMBER ONE|DAMAR|ARABESK|AKUSTIK|AHMET KAYA|IBRAHIM ERKAL|IBRAHIM TATLISES|TATLISES|ZERRIN OZER|SEZEN AKSU|TARKAN|SELDA BAGCAN|CENGIZ KURTOGLU|MAHSUN KIRMIZIGUL|MUSLUM GURSES|YILDIZ TILBE|FERDI TAYFUR|MTV LIVE|VINTAGE MUSIC|RETRO TURK|MUZIK|FM TV|FMTV|REDBOX)\b/i',
        'TR RADYO' => '/\b(RADIO|RADYO|FM|MBAT FM|EFKAR FM|FMTV|POWERTURK|POWER FM|SHOW RADYO|ALEM FM|BABA RADYO|KRAL POP RADYO|PAL STATION|X NOSTALJI|RADIO ROCK|ISTANBUL FM)\b/i',
        'TR DİNİ' => '/\b(D[İI]YANET|AK[İI]?T|MEHTAP|H[İI]LAL|KUDUS|KUDÜS|SEMERKAND|LALEGUL|LÂLEGÜL|MERCAN TV|VUSLAT|KARDELEN|DIYAR TV|DOST TV|YOL TV|KANAL 7|TVNET|TRT DIYANET|TV5|REHBER|ILAHI|ILKE TV|MESAJ TV|SURELER|CEM TV)\b/i',
        'TR ULUSAL' => '/\b(TRT|TRT 1|TRT 2|TRT 3|TRT AVAZ|TRT T[UÜ]RK|TRT KURD[İI]?|TRT WORLD|TRT 4K|TRT EBA|KANAL D|ATV|STAR TV|STAR|SHOW TV|SHOW|NOW ?TV|NOW|TV8|TV8[.,]5|BEYAZ TV|BEYAZ|360|24 TV|A2|A HABER|A NEWS|A PARA|A SPOR|TV100|TV4|FLASH TV|TEVE2|CNN T[UÜ]RK|KRT|ULUSAL KANAL|DREAM TURK|NTV|EXXEN TV|TABII|ULKE TV)\b/i',
        'TR YEREL' => '/\b(ADANA|ADIYAMAN|AFYON|AKSARAY|ALANYA|ANKARA|ANTALYA|BURSA|ELAZIG|ERZURUM|ESKISEHIR|GAZIANTEP|KAHRAMANMARAS|KAYSERI|KOCAELI|KONYA|MALATYA|MERSIN|ORDU|SIVAS|TRABZON|URFA|IZMIR|KIBRIS|DENIZLI|KANAL 12|KANAL 15|KANAL 23|KANAL 24|KANAL 26|KANAL 3|KANAL 32|KANAL 33|KANAL 34|KANAL 42|KANAL 58|KANAL 68|KANAL FIRAT|KANAL URFA|KANAL V|KARADENIZ|EGE|MELTEM|CAY TV|OLAY TV|TIVI 6|TV 41|TV 42|TV 52|TV 264)\b/i',
    ];

    foreach ($rules as $cat => $pattern) {
        if (preg_match($pattern, $s)) {
            return $cat;
        }
    }

    return 'TR GENEL';
}

// -- Ana İşlem -----------------------------------------------------------

function main() {
    global $worker_proxies;
    $signature = get_vavoo_signature();

    $ch = curl_init(CATALOG_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TCP_KEEPALIVE => 1,
        CURLOPT_HTTPHEADER => [
            'User-Agent: MediaHubMX/3.0.2',
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'X-MediaHubMX-Signature: ' . $signature,
            'Connection: keep-alive'
        ]
    ]);

    $cursor = 0;
    $has_next = true;
    $seen_cursors = [];
    $seen_urls = [];
    $page_count = 0;
    $max_pages = 200;

    $payload = [
        "language" => "en",
        "region" => "ALL",
        "catalogId" => "iptv",
        "id" => "iptv",
        "adult" => false,
        "search" => "",
        "sort" => "name",
        "filter" => new stdClass(),
        "clientVersion" => "3.0.2"
    ];

    $output = "#EXTM3U\n";

    $proxy_index = 0;
    $proxy_count = count($worker_proxies);

    while ($has_next && $page_count < $max_pages) {
        $page_count++;
        $payload["cursor"] = $cursor;
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response && $http_code === 200) {
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
                if (empty($item['url'])) continue;

                $raw_url = $item['url'];
                
                // Mükerrer yayın kontrolü
                if (isset($seen_urls[$raw_url])) {
                    continue;
                }
                $seen_urls[$raw_url] = true;

                $raw_name = isset($item['name']) ? $item['name'] : 'Bilinmeyen Kanal';
                
                // "4K TR:", "HEVC TR:", "TR:" temizliği yapılır
                $clean_name = clean_channel_name($raw_name);

                $raw_group = isset($item['group']) ? $item['group'] : '';
                
                if (strcasecmp($raw_group, 'Turkey') === 0 || empty($raw_group)) {
                    $group = categorize_channel($clean_name);
                } else {
                    $group = $raw_group;
                }

                // Proxy sıra döngüsü
                $proxy = $worker_proxies[$proxy_index];
                $proxy_index = ($proxy_index + 1) % $proxy_count;

                $proxied_url = $proxy . "/?url=" . urlencode($raw_url) . "&master&transport=http&.m3u8";
                $output .= '#EXTINF:-1 group-title="' . htmlspecialchars($group) . '",' . $clean_name . "\n" . $proxied_url . "\n";
            }

            $next_cursor = isset($data['nextCursor']) ? $data['nextCursor'] : null;
            if (!$next_cursor || in_array($next_cursor, $seen_cursors, true)) {
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

    file_put_contents(OUTPUT_FILE, $output);
}

main();
?>
