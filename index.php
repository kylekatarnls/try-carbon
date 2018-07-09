<?php
$leftWidth = isset($_GET['width']) ? $_GET['width'] : 70;
$rightWidth = 100 - $leftWidth;
$engine = isset($_GET['engine']) ? $_GET['engine'] : 'carbon';
$inline = isset($_GET['inline']) ? boolval(intval($_GET['inline'])) : true;
$inputLanguage = isset($_GET['language']) ? $_GET['language'] : 'php';
$hasOptions = isset($_GET['options']);
$border = isset($_GET['border']) ? $_GET['border'] : '#232323';
$radius = isset($_GET['radius']) ? $_GET['radius'] : '2';
$vPadding = isset($_GET['v-padding']) ? $_GET['v-padding'] : 0;
$hPadding = isset($_GET['h-padding']) ? $_GET['h-padding'] : 0;
$options = $hasOptions ? @json_decode($_GET['options']) : (object) [];

function getOption($option, $default = null) {
    global $options;

    if (property_exists($options, $option)) {
        return $options->$option;
    }
    
    return $default;
}

include_once __DIR__ . '/allow-csrf.php';

?><!DOCTYPE html>
<html lang="en">
<head>
<title>Try Carbon</title>
<style type="text/css" media="screen">
    html,
    body {
        padding: 0;
        margin: 0;
        font-family: sans-serif;
        background: #454545;
        color: #c9c9c9;
    }
    h1,
    p {
        padding: 0;
        margin: 20px;
    }
    h1 {
        font-weight: normal;
        text-align: center;
    }
    h1 select {
        width: 220px;
        font-size: 32px;
        padding: 4px;
        border: 1px solid #232323;
        background: #454545;
        color: #c9c9c9;
        border-radius: <?php echo $radius; ?>px;
    }
    aside {
        float: right;
        padding-right: 20px;
    }
    aside a {
        color: #90a0ff;
        text-decoration: none;
    }
    aside a:hover {
        text-decoration: underline;
    }
    sup {
        color: gray;
    }
    #h-resize,
    #input,
    #output,
    #options,
    #preview {
        position: absolute;
        bottom: 20px;
        top: 90px;
    }
    #input,
    #output,
    #preview {
        border-radius: <?php echo $radius; ?>px;
        border: <?php echo $border === 'none' ? 'none' : "1px solid $border"; ?>;
        box-sizing: border-box;
    }
    #input {
        left: 20px;
        right: calc(<?php echo $rightWidth; ?>% + 10px);
    }
    #options {
        bottom: auto;
        height: 0;
        right: calc(<?php echo $leftWidth; ?>% + 10px);
        overflow: visible;
        z-index: 8;
        display: none;
    }
    #right-buttons a,
    #options a {
        color: white;
        float: right;
        padding: 10px;
        text-decoration: none;
    }
    #right-buttons a {
        position: absolute;
        top: 120px;
        right: 20px;
        z-index: 2;
    }
    #right-buttons a:hover,
    #options a:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    #options .list {
        display: none;
        clear: right;
        color: white;
        background: <?php echo isset($_GET['options-color']) ? $_GET['options-color'] : 'rgba(75, 75, 75, 0.8)'; ?>;
        padding: 5px;
    }
    #options input[type="text"],
    #options input[type="number"],
    #options select {
        width: 60px;
        box-sizing: border-box;
    }
    #preview,
    #output {
        right: 20px;
        left: calc(<?php echo $leftWidth; ?>% + 10px);
    }
    #preview {
        display: none;
        color: gray;
    }
    #preview pre.line-numbers {
        background: gray;
    }
    #preview pre {
        overflow: auto;
    }
    #h-resize {
        left: calc(<?php echo $leftWidth; ?>% - 7px);
        width: 14px;
        cursor: col-resize;
    }
    .ace_scroller,
    .ace_gutter {
        padding: <?php echo $vPadding; ?>px <?php echo $hPadding; ?>px;
    }
    <?php if (isset($_GET['hide-output'])) { ?>
        #output {
            display: none;
        }
    <?php } ?>
    <?php if (isset($_GET['embed'])) { ?>
        html,
        body {
            background: transparent;
        }
        #right-buttons a {
            top: 0;
            right: 0;
        }
        #input {
            top: 0;
            height: 100%;
            left: 0;
            right: calc(<?php echo $rightWidth; ?>% + 7px);
        }
        #h-resize {
            top: 0;
            bottom: 0;
        }
        #preview,
        #output {
            top: 0;
            bottom: 0;
            right: 0;
            left: calc(<?php echo $leftWidth; ?>% + 7px);
        }
        #right-buttons a,
        #options a {
            color: gray;
            background: rgba(0, 0, 0, 0.1);
        }
        #options {
            display: block;
            top: 0;
            height: 0;
            right: calc(<?php echo $rightWidth; ?>% + 7px);
        }
    <?php } ?>
</style>
</head>
<body>

<?php if (!isset($_GET['embed'])) { ?>
    <h1>
        Try Carbon
        <select id="version-carbon" onchange="evaluateCode(event)">
            <?php include __DIR__ . '/var/cache/carbon-versions-options.html'; ?>
        </select>
    </h1>
<?php } ?>

<div id="options">
    <a href="#" onclick="toggleOptions(this)">Options</a>
    <div class="list">
        <table>
            <?php if (isset($_GET['embed'])) { ?>
                <tr>
                    <td>version</td>
                    <td>
                        <select id="version-carbon" onchange="evaluateCode(event)">
                            <?php include __DIR__ . '/var/cache/carbon-versions-options.html'; ?>
                        </select>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <?php if (isset($_GET['export'])) { ?>
            <button onclick="exportEmbed()" style="width: 100%;">Export</button>
        <?php } ?>
    </div>
</div>

<div id="input"><?php if (isset($_GET['embed'])) {
    echo isset($_GET['input']) ? $_GET['input'] : '';
} else { ?>echo Carbon::now()->subMonths(2)->diffForHumans();<?php } ?></div>

<div id="output"><?php
if (!isset($_GET['embed'])) { ?>2 months ago<?php } ?></div>
<div id="h-resize"></div>
<div id="preview"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.3/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.3/mode-jade.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.3/mode-html.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.3/mode-php.js" type="text/javascript" charset="utf-8"></script>
<?php if (!in_array($inputLanguage, array('jade', 'pug', 'html', 'php', 'css', 'js', 'javascript'))) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.3/mode-<?php echo $inputLanguage; ?>.js" type="text/javascript" charset="utf-8"></script>
<?php } ?>
<script>
    var lastRequest;
    var lastInput = '';
    var lastTime = 0;
    localStorage || (localStorage = {});
    localStorage.files || (localStorage.files = '{}');
    
    setInterval(function () {
        var files = JSON.parse(localStorage.files);
        Object.keys(files).forEach(function (file) {
            if (file !== saveAs) {
                var value = files[file];
                if (value > lastTime && lastInput.indexOf(file) !== -1) {
                    evaluateCode();
                    lastTime = (new Date()).getTime();
                }
            }
        });
    }, 200);

    function evaluateCode(e) {
        var xhr;

        if (typeof XMLHttpRequest !== 'undefined') {
            xhr = new XMLHttpRequest();
        } else {
            var versions = [
                'MSXML2.XmlHttp.5.0',
                'MSXML2.XmlHttp.4.0',
                'MSXML2.XmlHttp.3.0',
                'MSXML2.XmlHttp.2.0',
                'Microsoft.XmlHttp',
            ];
            for (var i = 0, len = versions.length; i < len; i++) {
                try {
                    /* global ActiveXObject */
                    xhr = new ActiveXObject(versions[i]);
                    break;
                }
                catch (e) {}
             }
        }

        lastRequest = xhr;

        xhr.onreadystatechange = function () {
            if (xhr.readyState < 4 || xhr !== lastRequest) {
                return;
            }

            if (xhr.status !== 200) {
                return;
            }

            if (xhr.readyState === 4) {
                var session = output.getSession();
                session.setMode('ace/mode/text');
                output.setValue(xhr.responseText, 1);
                document.getElementById('preview').innerHTML = xhr.responseText;
            }
        };

        var version = document.getElementById('version-carbon').value;

        lastInput = input.getValue() + '';

        xhr.open('POST', '/api/carbon.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send(
            'input=' + encodeURIComponent(lastInput) +
            '&version=' + encodeURIComponent(version)
        );
    }
    
    function exportEmbed() {
        var _input = encodeURIComponent(input.getValue());
        var link = '/?embed' +
            '&theme=xcode' +
            '&border=silver' +
            '&options-color=rgba(120,120,120,0.5)' +
            '&input=' + _input;

        window.open(link);
    }

    function editor(id, mode, readonly) {
        /* global ace */
        var editor = ace.edit(id);
        editor.setTheme('ace/theme/<?php echo isset($_GET['theme']) ? $_GET['theme'] : 'monokai' ?>');
        var session = editor.getSession();
        session.setMode({
            path: mode,
            inline: <?php echo json_encode($inline); ?>,
        });
        session.setTabSize(2);
        editor.setShowPrintMargin(false);
        editor.setAutoScrollEditorIntoView(true);
        if (readonly) {
            editor.setReadOnly(true);
        }
        editor.$blockScrolling = Infinity;

        return editor;
    }

    function toggleOptions(link) {
        var list = document.querySelector('#options .list');
        link.innerHTML = list.style.display === 'block' ? 'Options' : 'Close';
        list.style.display = list.style.display === 'block' ? '' : 'block';
    }

    var input = editor('input', 'ace/mode/<?php echo $inputLanguage; ?>');

    var output = editor('output', 'ace/mode/html', true);

    if (parent) {
        window.onload = function () {
            parent.postMessage(JSON.stringify({
                sender: 'try-carbon',
                token: (location.search.match(/[?&]token=([^&]+)/) || [])[1],
                event: 'ready',
            }), '*');
        };
    }

    input.getSession().on('change', evaluateCode);

    var dragAndDrop = {h: null};
    document.getElementById('h-resize').onmousedown = function (e) {
        dragAndDrop.h = {
            x: e.pageX,
            inputWidth: document.getElementById('input').offsetWidth,
            outputWidth: document.getElementById('output').offsetWidth
        };
        e.preventDefault();

        return false;
    };
    var minWidth = 80;
    var resize = {
        width: window.innerWidth,
    };
    function setInputWidth(inputWidth, outputWidth) {
        resize.inputWidth = inputWidth;
        resize.outputWidth = outputWidth;
        inputWidth = Math.round(inputWidth);
        outputWidth = Math.round(outputWidth);
        document.getElementById('input').style.width = inputWidth + 'px';
        document.getElementById('h-resize').style.left = (<?php echo isset($_GET['embed']) ? 0 : 20; ?> + inputWidth) + 'px';
        document.getElementById('options').style.right = (<?php echo isset($_GET['embed']) ? 0 : 26; ?> + outputWidth + 14) + 'px';
        document.getElementById('output').style.width = outputWidth + 'px';
    }
    window.onresize = function (e) {
        var width = window.innerWidth;
        var diffWidth = width - resize.width;
        if (diffWidth && resize.inputWidth && resize.outputWidth) {
            var deltaH = <?php echo isset($_GET['embed']) ? 14 : 54; ?>;
            var ratioH = (width - deltaH) / (resize.width - deltaH);
            setInputWidth(resize.inputWidth * ratioH, resize.outputWidth * ratioH);
        }
        resize.width = width;
    };
    window.onmousemove = function (e) {
        if (dragAndDrop.h) {
            document.getElementById('output').style.left = 'auto';
            var inputWidth = Math.max(minWidth, dragAndDrop.h.inputWidth + e.pageX - dragAndDrop.h.x);
            var outputWidth = dragAndDrop.h.outputWidth - inputWidth + dragAndDrop.h.inputWidth;
            if (outputWidth < minWidth) {
                inputWidth -= minWidth - outputWidth;
                outputWidth = minWidth;
            }
            setInputWidth(inputWidth, outputWidth);
        }
        window.dispatchEvent(new Event('resize'));
    };
    window.onmouseup = function (e) {
        dragAndDrop = {};
    };

    <?php
    if (isset($_GET['embed']) || $hasOptions) {
        echo 'evaluateCode()';
    }
    ?>

</script>

</body>
</html>
