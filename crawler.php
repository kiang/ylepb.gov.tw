<?php

$rootPath = __DIR__;

$targetPath = __DIR__ . '/data/' . date('Y/m');

if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777, true);
}

$baseUrl = 'http://218.161.81.10/epb/';

$stations = array(
    '大城站' => 'SCC.asp?DN=0',
    '東石站' => 'SCC.asp?DN=1',
    '褒忠站' => 'SCC.asp?DN=2',
    '崙背站' => 'SCC.asp?DN=3',
    '四湖站' => 'SCC.asp?DN=4',
    '東勢站' => 'SCC.asp?DN=5',
    '麥寮站' => 'SCC.asp?DN=6',
    '台西站' => 'SCC.asp?DN=7',
    '土庫站' => 'SCC.asp?DN=8',
    '西螺站' => 'SCC.asp?DN=9',
);

// station, time, item, value, unit
$targetFile = $targetPath . '/' . date('Ymd') . '.csv';

$ref = array();
if (file_exists($targetFile)) {
    $fh = fopen($targetFile, 'r');
    fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        $ref[implode(',', $line)] = true;
    }
} else {
    $fh = fopen($targetFile, 'w');
    fputcsv($fh, array('station', 'time', 'item', 'value', 'unit'));
}
$fh = fopen($targetFile, 'a');

foreach ($stations AS $station => $url) {
    $page = file_get_contents($baseUrl . $url);
    $pos = strpos($page, '最後監測時間');
    $page = substr($page, $pos);
    $pos = strpos($page, '<p');
    $time = preg_split('/[\\/\\s]+/', substr($page, 0, $pos));
    $time[2] = str_pad($time[2], 2, '0', STR_PAD_LEFT);
    $time[3] = str_pad($time[3], 2, '0', STR_PAD_LEFT);
    $time[4] = str_pad(intval($time[4]), 2, '0', STR_PAD_LEFT);

    $lines = explode('</tr>', $page);
    foreach ($lines AS $line) {
        $cols = explode('</td>', $line);
        foreach ($cols AS $k => $v) {
            $cols[$k] = trim(strip_tags($v));
        }
        if (count($cols) === 4 && $cols[0] !== '光化測項') {
            $result = array(
                $station, $time[4], $cols[0], $cols[1], $cols[2]
            );
            if (!isset($ref[implode(',', $result)])) {
                fputcsv($fh, $result);
            }
        }
    }
}

$now = date('Y-m-d H:i:s');

exec("cd {$rootPath} && /usr/bin/git add -A");

exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");

exec("cd {$rootPath} && /usr/bin/git push origin gh-pages");