<?php

set_time_limit(0);

function needDirectory($directory) {
    if (!file_exists($directory)) {
        return mkdir($directory, 0777, true);
    }

    return false;
}

$minimumVersion = '2.30.0';

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

$addons = [
    'cmixin/business-day' => ['1.26.2', '2.0.0'],
    'cmixin/business-time' => ['2.0.0'],
    'cmixin/enhanced-period' => ['2.24.0'],
    'kylekatarnls/carbonite' => ['2.24.0'],
];

list($majorMinimum, $minorMinimum) = explode('.', $minimumVersion);

header('Content-type: text/plain; charset=UTF-8');

function isAvailable($major, $minor, $patch, &$minorTag)
{
    global $majorMinimum, $minorMinimum;

    if ($major === '1') {
        if ($major === '1' && $minor === '26') {
            if ($patch === '3') {
                return true;
            }

            $minorTag .= '-last';

            return true;
        }

        $minorTag = '1-last';

        return true;
    }

    if ($major === '2' && $minor === '0' && $patch === '0') {
        return true;
    }

    return $major > $majorMinimum || $minor >= $minorMinimum;
}

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

    array_unshift(
        $tags,
        (object) [
            'name' => '2.x-dev',
        ],
        (object) [
            'name' => '3.x-dev',
        ]
    );

    $minorTags = [];

    foreach ($tags as $tag) {
        list($major, $minor, $patch) = explode('.', $tag->name . '..');
        $minorTag = "$major.$minor";

        if (isAvailable($major, $minor, $patch, $minorTag)  && !isset($minorTags[$minorTag])) {
            $minorTags[$minorTag] = $tag;
        }
    }

    foreach ($minorTags as $tag) {
        echo "Load $url {$tag->name}\n";
        $optionsHtml .= '<option value="' . $tag->name . '">' . $tag->name . '</option>';
        $versionDirectory = $directory . DIRECTORY_SEPARATOR . $tag->name;

        if (needDirectory($versionDirectory) || !file_exists($versionDirectory . '/vendor/autoload.php')) {
            $touched = true;
            $composerJson = [
                'require' => [
                    'nesbot/carbon' => $tag->name,
                ],
            ];
            $currentVersion = str_replace('.x-dev', '.99999.99999', $tag->name);

            foreach ($addons as $name => $versions) {
                list($min, $max) = array_pad($versions, 2, null);

                if ($min && version_compare($currentVersion, $min, '<')) {
                    continue;
                }

                if ($max && version_compare($currentVersion, $max, '>')) {
                    continue;
                }

                $composerJson['require'][$name] = '*';
            }

            file_put_contents($versionDirectory.'/composer.json', json_encode($composerJson));
            echo shell_exec("cd $versionDirectory && composer update --optimize-autoloader --no-dev --ignore-platform-reqs")."\n\n";
        }
    }

    if ($touched) {
        file_put_contents($cacheDirectory . DIRECTORY_SEPARATOR . $repository . '-versions-options.html', $optionsHtml);
    }
}

//shell_exec('chmod -R 0555 .');
