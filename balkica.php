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
    if (!$name) return "Bilinmeyen Kanal";

    $s = (string)$name;
    // 1. Baştaki "4K TR:", "TR:", "4K TR :" gibi ifadeleri kaldırır
    $s = preg_replace('/^\s*(?:4K\s*)?TR\s*:\s*/i', '', $s);
    // 2. Sondaki veya kelime aralarındaki .b, .c, .s gibi nokta uzantılarını kaldırır
    $s = preg_replace('/\s*\.[bcs]\b/i', '', $s);
    // 3. Çözünürlük ve yayın kalitesi etiketlerini temizler
    $s = preg_replace('/\s+(?:4K|UHD|FHD|HD\+|HD|SD|HEVC|RAW|H265|H\.265|FEED)(?=\s|$)/i', '', $s);
    // 4. Bozuk Sinema, Minica ve Dream Turk metinlerini düzeltir
    $s = preg_replace('/S\s*[\x00-\x1F\x7F-\xFF\?\s]*NEMA/iu', 'SINEMA', $s);
    $s = preg_replace('/DREAM\s*T\s*R\s*K/iu', 'DREAM TURK', $s);
    $s = preg_replace('/MINICA/iu', 'MINIKA', $s);
    // 5. Fazla boşlukları temizler
    $s = preg_replace('/\s+/', ' ', $s);

    return trim($s);
}

function normalize_for_category($name) {
    $s = clean_channel_name($name);

    // Türkçe karakterleri standart İngilizce harflere çevir
    $char_map = [
        'Ç' => 'C', 'ç' => 'c',
        'Ğ' => 'G', 'ğ' => 'g',
        'İ' => 'I', 'I' => 'I', 'ı' => 'i',
        'Ö' => 'O', 'ö' => 'o',
        'Ş' => 'S', 'ş' => 's',
        'Ü' => 'U', 'ü' => 'u'
    ];
    $s = strtr($s, $char_map);

    $search  = ['/\bT RK\b/u', '/\bT RKIYEM\b/u', '/\bBENG\b/u', '/\bBENGT\b/u', '/\bAK T\b/u', '/\bS\s*NEMA\b/u', '/\bM N KA\b/u', '/\bOCUK\b/u', '/\bM Z K\b/u', '/\bS ZC\b/u', '/\bSZC\b/u', '/\bLKE\b/u', '/\bYE IL AM\b/u', '/\bYE IL[ ]?CAM\b/u', '/\bT[ÜU]RK\b/ui'];
    $replace = ['TURK', 'TURKIYEM', 'BENGU', 'BENGUT', 'AKIT', 'SINEMA', 'MINIKA', 'COCUK', 'MUZIK', 'SOZCU', 'SOZCU', 'ULKE', 'YESILCAM', 'YESILCAM', 'TURK'];

    $s = preg_replace($search, $replace, $s);

    return $s;
}

function categorize_channel($name) {
    $s = normalize_for_category($name);

    // Muaf tutulan özel kanallar (Dogrudan TR Diğer grubuna gitmesi istenenler)
    if (preg_match('/KARADENIZ\s*BRT|TRT\s*MUZIK/i', $s)) {
        return 'TR Diğer';
    }

    $rules = [
        'TR Radyo' => '/\b(RADIO|RADYO)\b|\b(FM|MBAT FM|EFKAR FM|FMTV|F ?M)\b(?!\s*TV)|POWERTURK|POWER FM|SHOW RADYO|ALEM (?:FM|RADYO)|BABA RADYO|KRAL POP RADYO|PAL STATION|X NOSTALJI|RADIO ROCK|STANBUL FM/i',
        'TR Çocuk' => '/CARTOON|BOOMERANG|DISNEY|NICK(?:ELODEON|TOONS|JR|JUNIOR|\b)|BABY ?TV|BABYTV|MINIKA|MINICA|POKEMON|ANIMATION|ANIMASYON|TRT ?COCUK|COCUK HD|\bCOCUK\b|ANKA|BEN ?10|ANGRY BIRDS|CAILLOU|PEPPA|PEPE|HEIDI|SIRINLER|TOM & JERRY|SUNGER|SUNGER\s*BOB|SPIDERMAN|BARBIE|PIJAMA|PIRIL|RAFADAN|KELOGLAN|KUKULI|KUKILI|KOSTEBEK|CHICKY|BOOBA|WAKFU|GABBY|TAYO|NILOYA|PISI|LEYLEK|MASAL|CANIM KARDESIM|ADIBESA|MOMO|ALVIN|VIKINGLER|TRANSFORMERS|TROL AVCILARI|SMART\s*COCUK|COCUK\s*SMART|ILAHI COCUK|CILGIN ORMAN|KRAL SAKIR|SERCE KUS|ITFAYECI SAM|MUFFETIS|MAYMUNLAR|ELIF VE|ELIFIN|MIMOCAN|HAPSUU|RUYA TRENI|MASA KOCAAYI|PAK PIRPIR|LIMON ZEYTIN|GONCA TV|NASREDDIN|SEKER HOCA|SEVIMLI DOSTLAR|PAW PETROL|OSCAR COLLERDE|SL NILOYA|CBEEBIES|DUCK TV|JIM ?JAM|ENGLISH CLUB TV|EBA TV|TAVSAN|PATRON BEBEK|DIYARI|BAHA\b|SEF ROKKA|BULMACA KULESI|AKILLI TAVSAN|AKLILI|DA VINC KIDS|DA VINCI KIDS|DINAMIK ANIMASYON|BEST ANIMASYON|YILDIZ KIZ|KONUSAN TOM|JURASSIC WORLD|MONTAG|64\s*KARE/i',
        'TR Belgesel' => '/DISCOVERY|NATIONAL GEOGRAPHIC|NAT ?GEO|\bHISTORY\b|ANIMAL PLANET|DA VINCI(?! KIDS)|VIASAT|BBC EARTH|LOVE NATURE|TRT BELGESEL|EPIC DRAMA|TARIH TV|TARIM TV|TGRT BELGESEL|INVESTIGATION|DMAX|DOCUBOX|DOCU SCREEN|SCIENCE|\bIZ TV\b|YABAN|OUTDOOR|CHASSE|ANIMAUX|AGRO TV|CIFTCI TV|REDBULL TV|\bTLC\b/i',
        'TR Spor' => '/BEIN SPO[RT]{0,3}S?|\bBEIN 1\b|S[- ]?SPORTS?|\bS SPORT\b|SPOR SMART|EUROSPORT|\bNBA\b|TJK TV|TIVIBU ?SPOR|TIVIBUSPOR|TRT SPOR|TABII SPOR|EXXEN SPO[RT]?|\bHT SPOR\b|EKOL SPOR|SPORTS TV|IDMAN TV|GALATASARAY TV|\bFB TV\b|\bGS TV\b|SARAN SPORT|SMART SPOR|\bSPOR\b|\bSPORT\b/i',
        'TR Yaşam' => '/24 KITCHEN|GURME|BEIN GURME|LIFESTYLE|\bLIFE TV\b|FASHION|WM TV|EGE ILE GAGA|24 RAW|\bTVEM\b|\bTV EM\b|AUTOMOTO|LINE TV|BILGILENDIRME|WOMAN|TELEGRAM/i',
        'TR Haber' => '/\b24\s*TV\b|\bTV\s*24\b|\b24\s*HABER\b|^24$|\bHABER\b|\bNEWS\b|BLOOMBERG|\bCNN\b|EKOTURK|\bEKO ?TURK\b|\bEKOL\b|A ?PARA|APARA|PARANIN|HALK TV|TELE ?1|SOZCU|\bSZC\b|BENGU|BENGUTURK|TRT WORLD|TRT HABER|\bDHA\b|LIDER HABER|FLASH|FLASH HABER|MEDYA HABER|GLOBAL HABER|TRABZON HABER|BEIN SPORTS HABER|TURKHABER|HABERTURK|\bARTI TV\b|\bAKIT\b|TVNET|TV NET|MELTEM|A HABER|ULKE|A NEWS|KRT|ULUSAL/i',
        'TR Film' => '/DREAM|DINAMIK\s*TURK|SINEMA|CINEMA|SINEMAX|SINEVIZYON|\bMOVIES?\b|MOVIEMAX|MOVIESMART|BEIN MOVIES|BEIN BOX|BOX OFFICE|\bFX\b|FX HD|YESILCAM|GLOBAL BOX|PROTURK|FIX CINEMA|KINGBOX|ARENA BOX|SHOWMAX|SHOW MAX|REAL BOX|SMART BOX|BEST (?:AKSIYON|BILIMKURGU|DRAM|HABABAM|IMBD|KOMEDI|KORKU|LOCA|NETFLIX|SALON|SAVAS|TURK|WESTERN|YESILCAM)|MAX|\bLOCA\b|\bSALON\b|\bVIZYON\b|AKSIYON|KOMEDI|\bKORKU\b|\bDRAM\b|WESTERN|BILIM ?KURGU|\bSAVAS\b|\bIMBD\b|\bIMDB\b|\bFILM\b|FILMBOX|HORROR|OSCAR|KEMAL SUNAL|\b007\b|\bCINE ?1\b|SIFIR TV|SON C BOOM|\bYERLI\b|SPIDERMAN(?! TV)|ARENA BOX|MOVIE SMART|\bMTURK TV\b/i',
        'TR Ulusal' => '/\bTRT\b|\bTRT 1\b|\bTRT ?2\b|TRT2|\bTRT 3\b|TRT AVAZ|TRT TURK|TRT KURDI|TRT 4K|TRT EBA|\bKANAL D\b|\bATV\b|ATV AVRUPA|ATV EUROPA|STAR TV|\bSTAR\b|STAR HD|SHOW TV|SHOW TURK|\bSHOW\b|\bFOX\b|NOW ?TV|\bNOW\b|TV ?8|TV8[.,]5|BEYAZ TV|BEYAZ HD|\bBEYAZ\b|\b360\b|\bA2\b|TV ?100|TV ?4|TEVE ?2|TEVE2|CNN TURK|\bBRT ?[0-9]|\bBRTV\b|EURO ?D|EURO ?STAR|\bNTV\b|EXXEN TV|TIVITURK|TABII|OLAY TURK|KANAL AVRUPA|\bKANAL 7\b|KANAL 7 (?:AVRUPA|EUROPA)|EURO D|EURO STAR|SHOW TV EUROPA|TGRT EU|DUGUN TV|\bTBMM\b|\bTV 1\b|TVO TV|BEIN IZ/i',
        'TR Dizi' => '/SERIES|\bDIZI\b|BEIN SERIES|DIZISMART/i',
        'TR Müzik' => '/POWER TURK|POWER ?TV|POWERTURK|POWER (?:DANCE|LOVE|HD)|\bPOWER\b|KRAL POP|KRAL ?TV|\bKRAL\b|NR ?1|NUMBER ?1|NUMBER ONE|DAMAR|ARABESK|AKUSTIK|AHMET KAYA|IBRAHIM ERKAL|IBRAHIM TATLISES|\bTATLISES\b|ZERRIN OZER|SEZEN AKSU|TARKAN|SELDA BAGCAN|CENGIZ KURTOGLU|MAHSUN KIRMIZIGUL|MUSLUM GURSES|YILDIZ TILBE|FERDI TAYFUR|DURSUN AL|MTV LIVE|VINTAGE MUSIC|RETRO TURK|TURKCE POP|TURKCE KLASIK|SLOW KARADENIZ|\bSLOW\b|\bZARA\b|\bSONER ARICA\b|MUZIK|\bFM TV\b|\bFMTV\b|REDBOX/i',
        'TR Dini' => '/DIYANET|MEHTAP|HILAL|KUDUS|SEMERKAND|LALEGUL|MERCAN TV|VUSLAT|KARDELEN|DIYAR TV|\bDOST TV\b|\bYOL TV\b|HAYAT|HAYIRLI|HZ MERYEM|HZ OMER|HZ YUSUF|MAM EBU|ASHABI KEHF|HASAN VE HUSEYIN|SAT ?7 TURK|TRT DIYANET|\bTV ?5\b|\bTV5\b|REHBER|ILAHI|ILKE TV|MESAJ TV|SURELER|TURKCE MEAL|DURSUN AL ERZINCANLI|YUNUS EMRE|CEM TV|BARBAROS TV|ASLAN TV|TYT TURK|SATRANC|FASIL/i',
        'TR Yerel' => '/TVDEN|TV DEN|ADANA|ADIYAMAN|AFYON|AKSARAY|ALANYA|ANAKKALE|\bANKARA\b|ANKA TV|ANKARA TURKIYEM|SANLIURFA|ANTALYA|\bBURSA\b|ELAZIG|ERCIS|ERZURUM|ESKISEHIR|\bES TV\b|\bER TV\b|ETV KAYSERI|ETV MANISA|GAZIANTEP|\bICEL\b|KAHRAMANMARAS|KAYSERI|KOCAELI|KON TV|KONYA|MALATYA|MERSIN|ORDU|ALTAS TV|SIVAS|TRABZON|TUNCELI|DERSIM|\bURFA\b|IZMIR TV|TON TV|KIBRIS|EDIRNE|DENIZLI|\bKAY TV\b|KENT TURK|HUNAT|\bOBB\b|KANAL 12|KANAL 15|KANAL 23|KANAL 24|KANAL 26|KANAL 3\b|KANAL 32|KANAL 33|KANAL 34|KANAL 360|KANAL 42|KANAL 58|KANAL 68|KANAL FIRAT|KANAL URFA|KANAL V\b|\bKANAL Z\b|KANAL T\b|KANAL HAYAT|GUNEYDOGU|\bEGE\b|TEK RUMELI|YENI KOCAELI|OLAY TV|\bGRT\b|SUN RTV|SUN TV|\bKOY TV\b|IZMIR|TIVI 6|TV 41|TV 42|TV 52|TV 264|KOZA TV|MC EU|MERCAN|KADIRGA|\bFANATIK\b|AS TV|ISVI|GURBET24|T\.A\.Y|TAY TV|\bTAY\b|\bTMB\b|AV TV|MAVI KARADENIZ|EGE ILE GAGA|GAZIANTEP GRT|VIYANA TV|LUYS|EDESSA|BIR TV|ANADOLU|DIYAR|ERTV|HRT|VIZYON 58|ADA TV|CAN TV|DEHA|SIFIR|EKIN TURK|AFROTURK|ARAS|ARKADAG|VATAN|DORU|AKSU TV|KARE TV|ON 4|ON 6|PAMUKKALE|UCANKUS|DENIZ POSTASI/i'
    ];

    foreach ($rules as $cat => $pattern) {
        if (preg_match($pattern, $s)) {
            return $cat;
        }
    }

    return 'TR Diğer';
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
        "language" => "tr",
        "region" => "TR",
        "catalogId" => "iptv",
        "id" => "iptv",
        "adult" => false,
        "search" => "",
        "sort" => "",
        "filter" => ["group" => "Turkey"],
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
                
                // Kanal ismi temizleniyor
                $clean_name = clean_channel_name($raw_name);

                $raw_group = isset($item['group']) ? $item['group'] : '';
                
                if (strcasecmp($raw_group, 'Turkey') === 0 || empty($raw_group)) {
                    $group = categorize_channel($clean_name);
                } else {
                    $group = $raw_group;
                }

                $logo = isset($item['logo']) ? ' tvg-logo="' . htmlspecialchars($item['logo']) . '"' : '';

                // Proxy sıra döngüsü
                $proxy = $worker_proxies[$proxy_index];
                $proxy_index = ($proxy_index + 1) % $proxy_count;

                $proxied_url = $proxy . "/?url=" . urlencode($raw_url) . "&master&transport=http&.m3u8";
                $output .= '#EXTINF:-1 group-title="' . htmlspecialchars($group) . '"' . $logo . ',' . $clean_name . "\n" . $proxied_url . "\n";
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
