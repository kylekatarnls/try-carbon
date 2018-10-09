<?php

set_time_limit(0);

function needDirectory($directory) {
    if (!file_exists($directory)) {
        return mkdir($directory, 0777, true);
    }
    
    return false;
}

$enginesDirectory = __DIR__ . '/var/engines';
needDirectory($enginesDirectory);
$cacheDirectory = __DIR__ . '/var/cache';
needDirectory($cacheDirectory);

$gitHost = 'https://github.com/';
$apiHost = 'https://api.github.com/';
$apiContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: PHP',
        ],
    ],
]);

$enginesRepositories = [
    'carbon' => 'briannesbitt/Carbon',
];

header('Content-type: text/plain; charset=UTF-8');

foreach ($enginesRepositories as $repository => $url) {
    $optionsHtml = '';
    $directory = $enginesDirectory . DIRECTORY_SEPARATOR . $repository;
    needDirectory($directory);
    $versionCache = $cacheDirectory . DIRECTORY_SEPARATOR . $repository . '-tags.json';
    $versionFile = $versionCache;
    if (!file_exists($versionCache) || time() - filemtime($versionCache) > 3600) {
        $list = array();
        for ($i = 1; true; $i++) {
            $items = json_decode(file_get_contents(
                $apiHost . 'repos/' . $url . '/tags?page=' . $i,
                false,
                $apiContext
            ));
            if (!is_array($items)) {
                $items = json_decode(file_get_contents(
                    __DIR__ . '/fallback/' . $repository . '-tags.json'
                ));
            }
            $list = array_merge($list, $items);
            if (count($items) < 30) {
                break;
            }
        }
        file_put_contents($versionCache, json_encode($list));
    }
    $touched = false;
    $tags = json_decode(file_get_contents($versionCache));
    usort($tags, function ($a, $b) {
        return version_compare($b->name, $a->name);
    });
    array_unshift($tags, (object) [
       'name' => 'master (next version)',
    ]);
    $minorTags = [];
    foreach ($tags as $tag) {
        list($major, $minor, $patch) = explode('.', $tag->name . '..');
        $minorTag = "$major.$minor";
        if (!isset($minorTags[$minorTag])) {
            $minorTags[$minorTag] = $tag;
        }
    }
    foreach ($minorTags as $tag) {
        echo "Load $url {$tag->name}\n";
        $optionsHtml .= '<option value="' . $tag->name . '">' . $tag->name . '</option>';
        $versionDirectory = $directory . DIRECTORY_SEPARATOR . $tag->name;
        $shortName = substr($tag->name, 0, 6);
        if (needDirectory($versionDirectory) || !file_exists($versionDirectory . '/vendor/autoload.php')) {
            $touched = true;
            chdir($versionDirectory);
            shell_exec('rm -rf ./*');
            echo shell_exec('git clone ' . $gitHost . $url . ' .');
            $branch = $shortName === 'master' ? 'master' : 'tags/' . $tag->name;
            echo shell_exec('git checkout ' . $branch);
            shell_exec('rm -rf tests');
            echo shell_exec('composer install --optimize-autoloader --no-dev --ignore-platform-reqs &');
        } elseif ($shortName === 'master') {
            chdir($versionDirectory);
            echo shell_exec('git pull origin master');
        }
    }
    if ($touched) {
        file_put_contents($cacheDirectory . DIRECTORY_SEPARATOR . $repository . '-versions-options.html', $optionsHtml);
    }
}
