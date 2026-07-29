<?php
$c = json_decode(file_get_contents($argv[1] ?? 'composer.json'), true);
$c['repositories'] = [
    ['type' => 'path', 'url' => '/tmp/chandler']
];
file_put_contents($argv[1] ?? 'composer.json', json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Fixed repositories:\n";
print_r($c['repositories']);
