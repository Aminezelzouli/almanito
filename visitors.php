<?php
session_start();
$pass = "tantouni"; 
if (!isset($_GET['p']) || $_GET['p'] !== $pass) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$file = 'log.txt';
$filter = $_GET['show'] ?? 'all';
$logs = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];

// --- [حـساب الإحـصائيات] ---
$totalCount = 0;
$passedCount = 0;
$blockedCount = 0;

foreach ($logs as $line) {
    if (empty($line)) continue;
    $d = explode('|', $line);
    $totalCount++;
    if ($d[7] == 'Passed') $passedCount++;
    if ($d[7] == 'Blocked') $blockedCount++;
}

if (isset($_GET['reset'])) { file_put_contents($file, ""); header("Location: visitors.php?p=$pass"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIP LOUNGE - ANALYTICS</title>
    <meta http-equiv="refresh" content="15">
    <style>
        :root {
            --bg: #050505; --card: #0d0d0d;
            --neon-blue: #00f2fe; --neon-pink: #ff007c;
            --neon-purple: #bc13fe; --text: #ffffff;
        }
        body { 
            background: var(--bg); color: var(--text); font-family: 'Segoe UI', sans-serif; 
            margin: 0; padding: 20px;
            background-image: radial-gradient(circle at 50% 50%, #1a1a1a 0%, #050505 100%);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { 
            font-size: 2.5rem; text-transform: uppercase; margin: 0;
            text-shadow: 0 0 10px var(--neon-purple); letter-spacing: 5px;
        }

        /* --- Stats Cards --- */
        .stats-container { display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; }
        .stat-box {
            background: rgba(255,255,255,0.05); padding: 15px 30px; border-radius: 15px;
            text-align: center; border: 1px solid #222; min-width: 120px;
        }
        .stat-box h2 { margin: 0; font-size: 28px; }
        .stat-box span { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        .total-glow { border-bottom: 3px solid #fff; box-shadow: 0 5px 15px rgba(255,255,255,0.1); }
        .passed-glow { border-bottom: 3px solid var(--neon-blue); box-shadow: 0 5px 15px rgba(0, 242, 254, 0.2); }
        .blocked-glow { border-bottom: 3px solid var(--neon-pink); box-shadow: 0 5px 15px rgba(255, 0, 124, 0.2); }

        .controls { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
        .tab { 
            padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: bold;
            font-size: 12px; border: 1px solid #333; color: #888; transition: 0.3s;
        }
        .active-all { border-color: #fff; color: #fff; background: rgba(255,255,255,0.1); }
        .active-passed { border-color: var(--neon-blue); color: var(--neon-blue); background: rgba(0, 242, 254, 0.1); }
        .active-blocked { border-color: var(--neon-pink); color: var(--neon-pink); background: rgba(255, 0, 124, 0.1); }

        table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 15px; overflow: hidden; }
        th { background: #111; padding: 15px; text-align: left; font-size: 11px; color: #555; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #111; font-size: 13px; }
        .status-Passed { color: var(--neon-blue); font-weight: bold; text-shadow: 0 0 5px var(--neon-blue); }
        .status-Blocked { color: var(--neon-pink); font-weight: bold; text-shadow: 0 0 5px var(--neon-pink); }
        .badge { background: #222; padding: 3px 7px; border-radius: 4px; font-size: 10px; color: #777; margin-left: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>VIP <span style="color:var(--neon-pink)">LOUNGE</span></h1>
        <p style="color:var(--neon-blue); font-size: 12px; letter-spacing: 3px;">LIVE TRAFFIC STREAM</p>
    </div>

    <div class="stats-container">
        <div class="stat-box total-glow">
            <h2><?php echo $totalCount; ?></h2>
            <span>Total Visitors</span>
        </div>
        <div class="stat-box passed-glow">
            <h2 style="color:var(--neon-blue)"><?php echo $passedCount; ?></h2>
            <span>Passed (Real)</span>
        </div>
        <div class="stat-box blocked-glow">
            <h2 style="color:var(--neon-pink)"><?php echo $blockedCount; ?></h2>
            <span>Blocked (Bots)</span>
        </div>
    </div>

    <div class="controls">
        <a href="?p=<?php echo $pass; ?>&show=all" class="tab <?php echo $filter=='all'?'active-all':''; ?>">ALL LOGS</a>
        <a href="?p=<?php echo $pass; ?>&show=passed" class="tab <?php echo $filter=='passed'?'active-passed':''; ?>">PASSED</a>
        <a href="?p=<?php echo $pass; ?>&show=blocked" class="tab <?php echo $filter=='blocked'?'active-blocked':''; ?>">BLOCKED</a>
        <a href="?p=<?php echo $pass; ?>&reset=1" class="tab" style="color:#ff5555" onclick="return confirm('Wipe data?')">RESET</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Visitor IP</th><th>ISP / Provider</th><th>Organization</th><th>Device</th><th>Geo</th><th>Status</th><th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $displayLogs = array_reverse($logs);
            foreach ($displayLogs as $line) {
                if (empty($line)) continue;
                $d = explode('|', $line);
                if ($filter == 'passed' && $d[7] != 'Passed') continue;
                if ($filter == 'blocked' && $d[7] != 'Blocked') continue;

                echo "<tr>
                    <td><b style='color:#fff'>$d[0]</b> <span class='badge'>x$d[8]</span></td>
                    <td style='color:#ccc'>$d[3]</td>
                    <td style='color:#666; font-size:11px'>$d[4]</td>
                    <td style='color:var(--neon-purple)'>$d[5]</td>
                    <td><b>$d[2]</b></td>
                    <td><span class='status-$d[7]'>$d[7]</span></td>
                    <td style='font-size:11px; color:#444;'>$d[1]</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>