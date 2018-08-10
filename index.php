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
$outputLeftPadding = isset($_GET['output-left-padding']) ? $_GET['output-left-padding'] : 0;
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
    <?php if ($outputLeftPadding) { ?>
        .ace_content {
            padding-left: <?php echo $outputLeftPadding; ?>px;
        }
    <?php } ?>
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
    .ace_gutter {
        padding: <?php echo $vPadding; ?>px <?php echo $hPadding; ?>px;
    }
    #input .ace_scroller, #output .ace_scroller {
        top: <?php echo $vPadding; ?>px;
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/mode-jade.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/mode-html.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/mode-php.js" type="text/javascript" charset="utf-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/ext-language_tools.js" type="text/javascript" charset="utf-8"></script>
<?php if (!in_array($inputLanguage, array('jade', 'pug', 'html', 'php', 'css', 'js', 'javascript'))) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/mode-<?php echo $inputLanguage; ?>.js" type="text/javascript" charset="utf-8"></script>
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

        parent.postMessage(JSON.stringify({
            sender: 'try-carbon',
            token: (location.search.match(/[?&]token=([^&]+)/) || [])[1],
            event: 'input',
            input: lastInput,
        }), '*');

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

    var langTools = ace.require('ace/ext/language_tools');
    var input = editor('input', 'ace/mode/<?php echo $inputLanguage; ?>');
    input.getSelection().on('changeSelection', function () {
        var selection = input.getSelectedText();

        if (selection) {
            parent.postMessage(JSON.stringify({
                sender: 'try-carbon',
                token: (location.search.match(/[?&]token=([^&]+)/) || [])[1],
                event: 'selection',
                selection: selection,
            }), '*');
        }
    });
    input.setOptions({
        enableBasicAutocompletion: true,
    });

    langTools.addCompleter({
        getCompletions: function(editor, session, pos, prefix, callback) {
            var methods = [
                'Carbon\\Carbon::addCenturies',
                'Carbon\\Carbon::addCenturiesNoOverflow',
                'Carbon\\Carbon::addCenturiesWithNoOverflow',
                'Carbon\\Carbon::addCenturiesWithOverflow',
                'Carbon\\Carbon::addCenturiesWithoutOverflow',
                'Carbon\\Carbon::addCentury',
                'Carbon\\Carbon::addCenturyNoOverflow',
                'Carbon\\Carbon::addCenturyWithNoOverflow',
                'Carbon\\Carbon::addCenturyWithOverflow',
                'Carbon\\Carbon::addCenturyWithoutOverflow',
                'Carbon\\Carbon::addDay',
                'Carbon\\Carbon::addDays',
                'Carbon\\Carbon::addDecade',
                'Carbon\\Carbon::addDecadeNoOverflow',
                'Carbon\\Carbon::addDecadeWithNoOverflow',
                'Carbon\\Carbon::addDecadeWithOverflow',
                'Carbon\\Carbon::addDecadeWithoutOverflow',
                'Carbon\\Carbon::addDecades',
                'Carbon\\Carbon::addDecadesNoOverflow',
                'Carbon\\Carbon::addDecadesWithNoOverflow',
                'Carbon\\Carbon::addDecadesWithOverflow',
                'Carbon\\Carbon::addDecadesWithoutOverflow',
                'Carbon\\Carbon::addHour',
                'Carbon\\Carbon::addHours',
                'Carbon\\Carbon::addMicro',
                'Carbon\\Carbon::addMicros',
                'Carbon\\Carbon::addMicrosecond',
                'Carbon\\Carbon::addMicroseconds',
                'Carbon\\Carbon::addMillennia',
                'Carbon\\Carbon::addMillenniaNoOverflow',
                'Carbon\\Carbon::addMillenniaWithNoOverflow',
                'Carbon\\Carbon::addMillenniaWithOverflow',
                'Carbon\\Carbon::addMillenniaWithoutOverflow',
                'Carbon\\Carbon::addMillennium',
                'Carbon\\Carbon::addMillenniumNoOverflow',
                'Carbon\\Carbon::addMillenniumWithNoOverflow',
                'Carbon\\Carbon::addMillenniumWithOverflow',
                'Carbon\\Carbon::addMillenniumWithoutOverflow',
                'Carbon\\Carbon::addMinute',
                'Carbon\\Carbon::addMinutes',
                'Carbon\\Carbon::addMonth',
                'Carbon\\Carbon::addMonthNoOverflow',
                'Carbon\\Carbon::addMonthWithNoOverflow',
                'Carbon\\Carbon::addMonthWithOverflow',
                'Carbon\\Carbon::addMonthWithoutOverflow',
                'Carbon\\Carbon::addMonths',
                'Carbon\\Carbon::addMonthsNoOverflow',
                'Carbon\\Carbon::addMonthsWithNoOverflow',
                'Carbon\\Carbon::addMonthsWithOverflow',
                'Carbon\\Carbon::addMonthsWithoutOverflow',
                'Carbon\\Carbon::addQuarter',
                'Carbon\\Carbon::addQuarterNoOverflow',
                'Carbon\\Carbon::addQuarterWithNoOverflow',
                'Carbon\\Carbon::addQuarterWithOverflow',
                'Carbon\\Carbon::addQuarterWithoutOverflow',
                'Carbon\\Carbon::addQuarters',
                'Carbon\\Carbon::addQuartersNoOverflow',
                'Carbon\\Carbon::addQuartersWithNoOverflow',
                'Carbon\\Carbon::addQuartersWithOverflow',
                'Carbon\\Carbon::addQuartersWithoutOverflow',
                'Carbon\\Carbon::addRealCenturies',
                'Carbon\\Carbon::addRealCentury',
                'Carbon\\Carbon::addRealDay',
                'Carbon\\Carbon::addRealDays',
                'Carbon\\Carbon::addRealDecade',
                'Carbon\\Carbon::addRealDecades',
                'Carbon\\Carbon::addRealHour',
                'Carbon\\Carbon::addRealHours',
                'Carbon\\Carbon::addRealMicrosecond',
                'Carbon\\Carbon::addRealMicroseconds',
                'Carbon\\Carbon::addRealMillennia',
                'Carbon\\Carbon::addRealMillennium',
                'Carbon\\Carbon::addRealMinute',
                'Carbon\\Carbon::addRealMinutes',
                'Carbon\\Carbon::addRealMonth',
                'Carbon\\Carbon::addRealMonths',
                'Carbon\\Carbon::addRealQuarter',
                'Carbon\\Carbon::addRealQuarters',
                'Carbon\\Carbon::addRealSecond',
                'Carbon\\Carbon::addRealSeconds',
                'Carbon\\Carbon::addRealUnit',
                'Carbon\\Carbon::addRealWeek',
                'Carbon\\Carbon::addRealWeeks',
                'Carbon\\Carbon::addRealYear',
                'Carbon\\Carbon::addRealYears',
                'Carbon\\Carbon::addSecond',
                'Carbon\\Carbon::addSeconds',
                'Carbon\\Carbon::addUnit',
                'Carbon\\Carbon::addUnitNoOverflow',
                'Carbon\\Carbon::addWeek',
                'Carbon\\Carbon::addWeekday',
                'Carbon\\Carbon::addWeekdays',
                'Carbon\\Carbon::addWeeks',
                'Carbon\\Carbon::addYear',
                'Carbon\\Carbon::addYearNoOverflow',
                'Carbon\\Carbon::addYearWithNoOverflow',
                'Carbon\\Carbon::addYearWithOverflow',
                'Carbon\\Carbon::addYearWithoutOverflow',
                'Carbon\\Carbon::addYears',
                'Carbon\\Carbon::addYearsNoOverflow',
                'Carbon\\Carbon::addYearsWithNoOverflow',
                'Carbon\\Carbon::addYearsWithOverflow',
                'Carbon\\Carbon::addYearsWithoutOverflow',
                'Carbon\\Carbon::average',
                'Carbon\\Carbon::between',
                'Carbon\\Carbon::calendar',
                'Carbon\\Carbon::ceil',
                'Carbon\\Carbon::ceilCenturies',
                'Carbon\\Carbon::ceilCentury',
                'Carbon\\Carbon::ceilDay',
                'Carbon\\Carbon::ceilDays',
                'Carbon\\Carbon::ceilDecade',
                'Carbon\\Carbon::ceilDecades',
                'Carbon\\Carbon::ceilHour',
                'Carbon\\Carbon::ceilHours',
                'Carbon\\Carbon::ceilMicrosecond',
                'Carbon\\Carbon::ceilMicroseconds',
                'Carbon\\Carbon::ceilMillennia',
                'Carbon\\Carbon::ceilMillennium',
                'Carbon\\Carbon::ceilMillisecond',
                'Carbon\\Carbon::ceilMilliseconds',
                'Carbon\\Carbon::ceilMinute',
                'Carbon\\Carbon::ceilMinutes',
                'Carbon\\Carbon::ceilMonth',
                'Carbon\\Carbon::ceilMonths',
                'Carbon\\Carbon::ceilQuarter',
                'Carbon\\Carbon::ceilQuarters',
                'Carbon\\Carbon::ceilSecond',
                'Carbon\\Carbon::ceilSeconds',
                'Carbon\\Carbon::ceilUnit',
                'Carbon\\Carbon::ceilWeek',
                'Carbon\\Carbon::ceilYear',
                'Carbon\\Carbon::ceilYears',
                'Carbon\\Carbon::clone',
                'Carbon\\Carbon::closest',
                'Carbon\\Carbon::copy',
                'Carbon\\Carbon::create',
                'Carbon\\Carbon::createFromDate',
                'Carbon\\Carbon::createFromTime',
                'Carbon\\Carbon::createFromTimeString',
                'Carbon\\Carbon::createFromTimestamp',
                'Carbon\\Carbon::createFromTimestampMs',
                'Carbon\\Carbon::createFromTimestampUTC',
                'Carbon\\Carbon::createMidnightDate',
                'Carbon\\Carbon::createSafe',
                'Carbon\\Carbon::day',
                'Carbon\\Carbon::dayOfYear',
                'Carbon\\Carbon::days',
                'Carbon\\Carbon::diff',
                'Carbon\\Carbon::diffAsCarbonInterval',
                'Carbon\\Carbon::diffFiltered',
                'Carbon\\Carbon::diffForHumans',
                'Carbon\\Carbon::diffInDays',
                'Carbon\\Carbon::diffInDaysFiltered',
                'Carbon\\Carbon::diffInHours',
                'Carbon\\Carbon::diffInHoursFiltered',
                'Carbon\\Carbon::diffInMicroseconds',
                'Carbon\\Carbon::diffInMinutes',
                'Carbon\\Carbon::diffInMonths',
                'Carbon\\Carbon::diffInRealHours',
                'Carbon\\Carbon::diffInRealMicroseconds',
                'Carbon\\Carbon::diffInRealMinutes',
                'Carbon\\Carbon::diffInRealSeconds',
                'Carbon\\Carbon::diffInSeconds',
                'Carbon\\Carbon::diffInWeekdays',
                'Carbon\\Carbon::diffInWeekendDays',
                'Carbon\\Carbon::diffInWeeks',
                'Carbon\\Carbon::diffInYears',
                'Carbon\\Carbon::disableHumanDiffOption',
                'Carbon\\Carbon::enableHumanDiffOption',
                'Carbon\\Carbon::endOf',
                'Carbon\\Carbon::endOfCentury',
                'Carbon\\Carbon::endOfDay',
                'Carbon\\Carbon::endOfDecade',
                'Carbon\\Carbon::endOfHour',
                'Carbon\\Carbon::endOfMillennium',
                'Carbon\\Carbon::endOfMinute',
                'Carbon\\Carbon::endOfMonth',
                'Carbon\\Carbon::endOfQuarter',
                'Carbon\\Carbon::endOfSecond',
                'Carbon\\Carbon::endOfWeek',
                'Carbon\\Carbon::endOfYear',
                'Carbon\\Carbon::eq',
                'Carbon\\Carbon::equalTo',
                'Carbon\\Carbon::executeWithLocale',
                'Carbon\\Carbon::farthest',
                'Carbon\\Carbon::firstOfMonth',
                'Carbon\\Carbon::firstOfQuarter',
                'Carbon\\Carbon::firstOfYear',
                'Carbon\\Carbon::floor',
                'Carbon\\Carbon::floorCenturies',
                'Carbon\\Carbon::floorCentury',
                'Carbon\\Carbon::floorDay',
                'Carbon\\Carbon::floorDays',
                'Carbon\\Carbon::floorDecade',
                'Carbon\\Carbon::floorDecades',
                'Carbon\\Carbon::floorHour',
                'Carbon\\Carbon::floorHours',
                'Carbon\\Carbon::floorMicrosecond',
                'Carbon\\Carbon::floorMicroseconds',
                'Carbon\\Carbon::floorMillennia',
                'Carbon\\Carbon::floorMillennium',
                'Carbon\\Carbon::floorMillisecond',
                'Carbon\\Carbon::floorMilliseconds',
                'Carbon\\Carbon::floorMinute',
                'Carbon\\Carbon::floorMinutes',
                'Carbon\\Carbon::floorMonth',
                'Carbon\\Carbon::floorMonths',
                'Carbon\\Carbon::floorQuarter',
                'Carbon\\Carbon::floorQuarters',
                'Carbon\\Carbon::floorSecond',
                'Carbon\\Carbon::floorSeconds',
                'Carbon\\Carbon::floorUnit',
                'Carbon\\Carbon::floorWeek',
                'Carbon\\Carbon::floorYear',
                'Carbon\\Carbon::floorYears',
                'Carbon\\Carbon::format',
                'Carbon\\Carbon::formatLocalized',
                'Carbon\\Carbon::from',
                'Carbon\\Carbon::fromNow',
                'Carbon\\Carbon::fromSerialized',
                'Carbon\\Carbon::get',
                'Carbon\\Carbon::getAvailableLocales',
                'Carbon\\Carbon::getCalendarFormats',
                'Carbon\\Carbon::getDays',
                'Carbon\\Carbon::getHumanDiffOptions',
                'Carbon\\Carbon::getIsoFormats',
                'Carbon\\Carbon::getIsoUnits',
                'Carbon\\Carbon::getLocalTranslator',
                'Carbon\\Carbon::getLocale',
                'Carbon\\Carbon::getMidDayAt',
                'Carbon\\Carbon::getOffset',
                'Carbon\\Carbon::getOffsetString',
                'Carbon\\Carbon::getPaddedUnit',
                'Carbon\\Carbon::getPreciseTimestamp',
                'Carbon\\Carbon::getTestNow',
                'Carbon\\Carbon::getTimestamp',
                'Carbon\\Carbon::getTranslationMessage',
                'Carbon\\Carbon::getTranslator',
                'Carbon\\Carbon::getWeekEndsAt',
                'Carbon\\Carbon::getWeekStartsAt',
                'Carbon\\Carbon::getWeekendDays',
                'Carbon\\Carbon::greaterThan',
                'Carbon\\Carbon::greaterThanOrEqualTo',
                'Carbon\\Carbon::gt',
                'Carbon\\Carbon::gte',
                'Carbon\\Carbon::hasFormat',
                'Carbon\\Carbon::hasMacro',
                'Carbon\\Carbon::hasRelativeKeywords',
                'Carbon\\Carbon::hasTestNow',
                'Carbon\\Carbon::hour',
                'Carbon\\Carbon::hours',
                'Carbon\\Carbon::instance',
                'Carbon\\Carbon::isAfter',
                'Carbon\\Carbon::isBefore',
                'Carbon\\Carbon::isBetween',
                'Carbon\\Carbon::isBirthday',
                'Carbon\\Carbon::isCurrentCentury',
                'Carbon\\Carbon::isCurrentDay',
                'Carbon\\Carbon::isCurrentDecade',
                'Carbon\\Carbon::isCurrentHour',
                'Carbon\\Carbon::isCurrentMicro',
                'Carbon\\Carbon::isCurrentMicrosecond',
                'Carbon\\Carbon::isCurrentMillennium',
                'Carbon\\Carbon::isCurrentMinute',
                'Carbon\\Carbon::isCurrentMonth',
                'Carbon\\Carbon::isCurrentQuarter',
                'Carbon\\Carbon::isCurrentSecond',
                'Carbon\\Carbon::isCurrentUnit',
                'Carbon\\Carbon::isCurrentWeek',
                'Carbon\\Carbon::isCurrentYear',
                'Carbon\\Carbon::isDST',
                'Carbon\\Carbon::isDayOfWeek',
                'Carbon\\Carbon::isEndOfDay',
                'Carbon\\Carbon::isFriday',
                'Carbon\\Carbon::isFuture',
                'Carbon\\Carbon::isImmutable',
                'Carbon\\Carbon::isLastCentury',
                'Carbon\\Carbon::isLastDay',
                'Carbon\\Carbon::isLastDecade',
                'Carbon\\Carbon::isLastHour',
                'Carbon\\Carbon::isLastMicro',
                'Carbon\\Carbon::isLastMicrosecond',
                'Carbon\\Carbon::isLastMillennium',
                'Carbon\\Carbon::isLastMinute',
                'Carbon\\Carbon::isLastMonth',
                'Carbon\\Carbon::isLastOfMonth',
                'Carbon\\Carbon::isLastQuarter',
                'Carbon\\Carbon::isLastSecond',
                'Carbon\\Carbon::isLastWeek',
                'Carbon\\Carbon::isLastYear',
                'Carbon\\Carbon::isLeapYear',
                'Carbon\\Carbon::isLocal',
                'Carbon\\Carbon::isLongYear',
                'Carbon\\Carbon::isMidday',
                'Carbon\\Carbon::isMidnight',
                'Carbon\\Carbon::isModifiableUnit',
                'Carbon\\Carbon::isMonday',
                'Carbon\\Carbon::isMutable',
                'Carbon\\Carbon::isNextCentury',
                'Carbon\\Carbon::isNextDay',
                'Carbon\\Carbon::isNextDecade',
                'Carbon\\Carbon::isNextHour',
                'Carbon\\Carbon::isNextMicro',
                'Carbon\\Carbon::isNextMicrosecond',
                'Carbon\\Carbon::isNextMillennium',
                'Carbon\\Carbon::isNextMinute',
                'Carbon\\Carbon::isNextMonth',
                'Carbon\\Carbon::isNextQuarter',
                'Carbon\\Carbon::isNextSecond',
                'Carbon\\Carbon::isNextWeek',
                'Carbon\\Carbon::isNextYear',
                'Carbon\\Carbon::isPast',
                'Carbon\\Carbon::isSameAs',
                'Carbon\\Carbon::isSameCentury',
                'Carbon\\Carbon::isSameDay',
                'Carbon\\Carbon::isSameDecade',
                'Carbon\\Carbon::isSameHour',
                'Carbon\\Carbon::isSameMicro',
                'Carbon\\Carbon::isSameMicrosecond',
                'Carbon\\Carbon::isSameMillennium',
                'Carbon\\Carbon::isSameMinute',
                'Carbon\\Carbon::isSameMonth',
                'Carbon\\Carbon::isSameQuarter',
                'Carbon\\Carbon::isSameSecond',
                'Carbon\\Carbon::isSameUnit',
                'Carbon\\Carbon::isSameWeek',
                'Carbon\\Carbon::isSameYear',
                'Carbon\\Carbon::isSaturday',
                'Carbon\\Carbon::isStartOfDay',
                'Carbon\\Carbon::isStrictModeEnabled',
                'Carbon\\Carbon::isSunday',
                'Carbon\\Carbon::isThursday',
                'Carbon\\Carbon::isToday',
                'Carbon\\Carbon::isTomorrow',
                'Carbon\\Carbon::isTuesday',
                'Carbon\\Carbon::isUTC',
                'Carbon\\Carbon::isUtc',
                'Carbon\\Carbon::isValid',
                'Carbon\\Carbon::isWednesday',
                'Carbon\\Carbon::isWeekday',
                'Carbon\\Carbon::isWeekend',
                'Carbon\\Carbon::isYesterday',
                'Carbon\\Carbon::isoFormat',
                'Carbon\\Carbon::isoWeek',
                'Carbon\\Carbon::isoWeekYear',
                'Carbon\\Carbon::isoWeekday',
                'Carbon\\Carbon::isoWeeksInYear',
                'Carbon\\Carbon::jsonSerialize',
                'Carbon\\Carbon::lastOfMonth',
                'Carbon\\Carbon::lastOfQuarter',
                'Carbon\\Carbon::lastOfYear',
                'Carbon\\Carbon::lessThan',
                'Carbon\\Carbon::lessThanOrEqualTo',
                'Carbon\\Carbon::locale',
                'Carbon\\Carbon::localeHasDiffOneDayWords',
                'Carbon\\Carbon::localeHasDiffSyntax',
                'Carbon\\Carbon::localeHasDiffTwoDayWords',
                'Carbon\\Carbon::localeHasPeriodSyntax',
                'Carbon\\Carbon::localeHasShortUnits',
                'Carbon\\Carbon::longAbsoluteDiffForHumans',
                'Carbon\\Carbon::longRelativeDiffForHumans',
                'Carbon\\Carbon::longRelativeToNowDiffForHumans',
                'Carbon\\Carbon::longRelativeToOtherDiffForHumans',
                'Carbon\\Carbon::lt',
                'Carbon\\Carbon::lte',
                'Carbon\\Carbon::macro',
                'Carbon\\Carbon::make',
                'Carbon\\Carbon::max',
                'Carbon\\Carbon::maxValue',
                'Carbon\\Carbon::maximum',
                'Carbon\\Carbon::micro',
                'Carbon\\Carbon::micros',
                'Carbon\\Carbon::microsecond',
                'Carbon\\Carbon::microseconds',
                'Carbon\\Carbon::midDay',
                'Carbon\\Carbon::min',
                'Carbon\\Carbon::minValue',
                'Carbon\\Carbon::minimum',
                'Carbon\\Carbon::minute',
                'Carbon\\Carbon::minutes',
                'Carbon\\Carbon::mixin',
                'Carbon\\Carbon::modify',
                'Carbon\\Carbon::month',
                'Carbon\\Carbon::months',
                'Carbon\\Carbon::ne',
                'Carbon\\Carbon::next',
                'Carbon\\Carbon::nextWeekday',
                'Carbon\\Carbon::nextWeekendDay',
                'Carbon\\Carbon::notEqualTo',
                'Carbon\\Carbon::now',
                'Carbon\\Carbon::nowWithSameTz',
                'Carbon\\Carbon::nthOfMonth',
                'Carbon\\Carbon::nthOfQuarter',
                'Carbon\\Carbon::nthOfYear',
                'Carbon\\Carbon::ordinal',
                'Carbon\\Carbon::parse',
                'Carbon\\Carbon::pluralUnit',
                'Carbon\\Carbon::previous',
                'Carbon\\Carbon::previousWeekday',
                'Carbon\\Carbon::previousWeekendDay',
                'Carbon\\Carbon::resetMonthsOverflow',
                'Carbon\\Carbon::resetToStringFormat',
                'Carbon\\Carbon::resetYearsOverflow',
                'Carbon\\Carbon::round',
                'Carbon\\Carbon::roundCenturies',
                'Carbon\\Carbon::roundCentury',
                'Carbon\\Carbon::roundDay',
                'Carbon\\Carbon::roundDays',
                'Carbon\\Carbon::roundDecade',
                'Carbon\\Carbon::roundDecades',
                'Carbon\\Carbon::roundHour',
                'Carbon\\Carbon::roundHours',
                'Carbon\\Carbon::roundMicrosecond',
                'Carbon\\Carbon::roundMicroseconds',
                'Carbon\\Carbon::roundMillennia',
                'Carbon\\Carbon::roundMillennium',
                'Carbon\\Carbon::roundMillisecond',
                'Carbon\\Carbon::roundMilliseconds',
                'Carbon\\Carbon::roundMinute',
                'Carbon\\Carbon::roundMinutes',
                'Carbon\\Carbon::roundMonth',
                'Carbon\\Carbon::roundMonths',
                'Carbon\\Carbon::roundQuarter',
                'Carbon\\Carbon::roundQuarters',
                'Carbon\\Carbon::roundSecond',
                'Carbon\\Carbon::roundSeconds',
                'Carbon\\Carbon::roundUnit',
                'Carbon\\Carbon::roundWeek',
                'Carbon\\Carbon::roundYear',
                'Carbon\\Carbon::roundYears',
                'Carbon\\Carbon::second',
                'Carbon\\Carbon::seconds',
                'Carbon\\Carbon::secondsSinceMidnight',
                'Carbon\\Carbon::secondsUntilEndOfDay',
                'Carbon\\Carbon::serialize',
                'Carbon\\Carbon::serializeUsing',
                'Carbon\\Carbon::set',
                'Carbon\\Carbon::setDateFrom',
                'Carbon\\Carbon::setDateTime',
                'Carbon\\Carbon::setDateTimeFrom',
                'Carbon\\Carbon::setDay',
                'Carbon\\Carbon::setDays',
                'Carbon\\Carbon::setHour',
                'Carbon\\Carbon::setHours',
                'Carbon\\Carbon::setHumanDiffOptions',
                'Carbon\\Carbon::setISODate',
                'Carbon\\Carbon::setLocalTranslator',
                'Carbon\\Carbon::setLocale',
                'Carbon\\Carbon::setMicro',
                'Carbon\\Carbon::setMicros',
                'Carbon\\Carbon::setMicrosecond',
                'Carbon\\Carbon::setMicroseconds',
                'Carbon\\Carbon::setMidDayAt',
                'Carbon\\Carbon::setMinute',
                'Carbon\\Carbon::setMinutes',
                'Carbon\\Carbon::setMonth',
                'Carbon\\Carbon::setMonths',
                'Carbon\\Carbon::setSecond',
                'Carbon\\Carbon::setSeconds',
                'Carbon\\Carbon::setTestNow',
                'Carbon\\Carbon::setTime',
                'Carbon\\Carbon::setTimeFrom',
                'Carbon\\Carbon::setTimeFromTimeString',
                'Carbon\\Carbon::setTimestamp',
                'Carbon\\Carbon::setToStringFormat',
                'Carbon\\Carbon::setTranslator',
                'Carbon\\Carbon::setUnit',
                'Carbon\\Carbon::setUnitNoOverflow',
                'Carbon\\Carbon::setUtf8',
                'Carbon\\Carbon::setWeekEndsAt',
                'Carbon\\Carbon::setWeekStartsAt',
                'Carbon\\Carbon::setWeekendDays',
                'Carbon\\Carbon::setYear',
                'Carbon\\Carbon::setYears',
                'Carbon\\Carbon::shortAbsoluteDiffForHumans',
                'Carbon\\Carbon::shortRelativeDiffForHumans',
                'Carbon\\Carbon::shortRelativeToNowDiffForHumans',
                'Carbon\\Carbon::shortRelativeToOtherDiffForHumans',
                'Carbon\\Carbon::shouldOverflowMonths',
                'Carbon\\Carbon::shouldOverflowYears',
                'Carbon\\Carbon::since',
                'Carbon\\Carbon::singularUnit',
                'Carbon\\Carbon::startOf',
                'Carbon\\Carbon::startOfCentury',
                'Carbon\\Carbon::startOfDay',
                'Carbon\\Carbon::startOfDecade',
                'Carbon\\Carbon::startOfHour',
                'Carbon\\Carbon::startOfMillennium',
                'Carbon\\Carbon::startOfMinute',
                'Carbon\\Carbon::startOfMonth',
                'Carbon\\Carbon::startOfQuarter',
                'Carbon\\Carbon::startOfSecond',
                'Carbon\\Carbon::startOfWeek',
                'Carbon\\Carbon::startOfYear',
                'Carbon\\Carbon::subCenturies',
                'Carbon\\Carbon::subCenturiesNoOverflow',
                'Carbon\\Carbon::subCenturiesWithNoOverflow',
                'Carbon\\Carbon::subCenturiesWithOverflow',
                'Carbon\\Carbon::subCenturiesWithoutOverflow',
                'Carbon\\Carbon::subCentury',
                'Carbon\\Carbon::subCenturyNoOverflow',
                'Carbon\\Carbon::subCenturyWithNoOverflow',
                'Carbon\\Carbon::subCenturyWithOverflow',
                'Carbon\\Carbon::subCenturyWithoutOverflow',
                'Carbon\\Carbon::subDay',
                'Carbon\\Carbon::subDays',
                'Carbon\\Carbon::subDecade',
                'Carbon\\Carbon::subDecadeNoOverflow',
                'Carbon\\Carbon::subDecadeWithNoOverflow',
                'Carbon\\Carbon::subDecadeWithOverflow',
                'Carbon\\Carbon::subDecadeWithoutOverflow',
                'Carbon\\Carbon::subDecades',
                'Carbon\\Carbon::subDecadesNoOverflow',
                'Carbon\\Carbon::subDecadesWithNoOverflow',
                'Carbon\\Carbon::subDecadesWithOverflow',
                'Carbon\\Carbon::subDecadesWithoutOverflow',
                'Carbon\\Carbon::subHour',
                'Carbon\\Carbon::subHours',
                'Carbon\\Carbon::subMicro',
                'Carbon\\Carbon::subMicros',
                'Carbon\\Carbon::subMicrosecond',
                'Carbon\\Carbon::subMicroseconds',
                'Carbon\\Carbon::subMillennia',
                'Carbon\\Carbon::subMillenniaNoOverflow',
                'Carbon\\Carbon::subMillenniaWithNoOverflow',
                'Carbon\\Carbon::subMillenniaWithOverflow',
                'Carbon\\Carbon::subMillenniaWithoutOverflow',
                'Carbon\\Carbon::subMillennium',
                'Carbon\\Carbon::subMillenniumNoOverflow',
                'Carbon\\Carbon::subMillenniumWithNoOverflow',
                'Carbon\\Carbon::subMillenniumWithOverflow',
                'Carbon\\Carbon::subMillenniumWithoutOverflow',
                'Carbon\\Carbon::subMinute',
                'Carbon\\Carbon::subMinutes',
                'Carbon\\Carbon::subMonth',
                'Carbon\\Carbon::subMonthNoOverflow',
                'Carbon\\Carbon::subMonthWithNoOverflow',
                'Carbon\\Carbon::subMonthWithOverflow',
                'Carbon\\Carbon::subMonthWithoutOverflow',
                'Carbon\\Carbon::subMonths',
                'Carbon\\Carbon::subMonthsNoOverflow',
                'Carbon\\Carbon::subMonthsWithNoOverflow',
                'Carbon\\Carbon::subMonthsWithOverflow',
                'Carbon\\Carbon::subMonthsWithoutOverflow',
                'Carbon\\Carbon::subQuarter',
                'Carbon\\Carbon::subQuarterNoOverflow',
                'Carbon\\Carbon::subQuarterWithNoOverflow',
                'Carbon\\Carbon::subQuarterWithOverflow',
                'Carbon\\Carbon::subQuarterWithoutOverflow',
                'Carbon\\Carbon::subQuarters',
                'Carbon\\Carbon::subQuartersNoOverflow',
                'Carbon\\Carbon::subQuartersWithNoOverflow',
                'Carbon\\Carbon::subQuartersWithOverflow',
                'Carbon\\Carbon::subQuartersWithoutOverflow',
                'Carbon\\Carbon::subRealCenturies',
                'Carbon\\Carbon::subRealCentury',
                'Carbon\\Carbon::subRealDay',
                'Carbon\\Carbon::subRealDays',
                'Carbon\\Carbon::subRealDecade',
                'Carbon\\Carbon::subRealDecades',
                'Carbon\\Carbon::subRealHour',
                'Carbon\\Carbon::subRealHours',
                'Carbon\\Carbon::subRealMicrosecond',
                'Carbon\\Carbon::subRealMicroseconds',
                'Carbon\\Carbon::subRealMillennia',
                'Carbon\\Carbon::subRealMillennium',
                'Carbon\\Carbon::subRealMinute',
                'Carbon\\Carbon::subRealMinutes',
                'Carbon\\Carbon::subRealMonth',
                'Carbon\\Carbon::subRealMonths',
                'Carbon\\Carbon::subRealQuarter',
                'Carbon\\Carbon::subRealQuarters',
                'Carbon\\Carbon::subRealSecond',
                'Carbon\\Carbon::subRealSeconds',
                'Carbon\\Carbon::subRealUnit',
                'Carbon\\Carbon::subRealWeek',
                'Carbon\\Carbon::subRealWeeks',
                'Carbon\\Carbon::subRealYear',
                'Carbon\\Carbon::subRealYears',
                'Carbon\\Carbon::subSecond',
                'Carbon\\Carbon::subSeconds',
                'Carbon\\Carbon::subUnit',
                'Carbon\\Carbon::subUnitNoOverflow',
                'Carbon\\Carbon::subWeek',
                'Carbon\\Carbon::subWeekday',
                'Carbon\\Carbon::subWeekdays',
                'Carbon\\Carbon::subWeeks',
                'Carbon\\Carbon::subYear',
                'Carbon\\Carbon::subYearNoOverflow',
                'Carbon\\Carbon::subYearWithNoOverflow',
                'Carbon\\Carbon::subYearWithOverflow',
                'Carbon\\Carbon::subYearWithoutOverflow',
                'Carbon\\Carbon::subYears',
                'Carbon\\Carbon::subYearsNoOverflow',
                'Carbon\\Carbon::subYearsWithNoOverflow',
                'Carbon\\Carbon::subYearsWithOverflow',
                'Carbon\\Carbon::subYearsWithoutOverflow',
                'Carbon\\Carbon::subtract',
                'Carbon\\Carbon::timestamp',
                'Carbon\\Carbon::timezone',
                'Carbon\\Carbon::to',
                'Carbon\\Carbon::toArray',
                'Carbon\\Carbon::toAtomString',
                'Carbon\\Carbon::toCookieString',
                'Carbon\\Carbon::toDate',
                'Carbon\\Carbon::toDateString',
                'Carbon\\Carbon::toDateTime',
                'Carbon\\Carbon::toDateTimeString',
                'Carbon\\Carbon::toDayDateTimeString',
                'Carbon\\Carbon::toFormattedDateString',
                'Carbon\\Carbon::toISOString',
                'Carbon\\Carbon::toImmutable',
                'Carbon\\Carbon::toIso8601String',
                'Carbon\\Carbon::toIso8601ZuluString',
                'Carbon\\Carbon::toJSON',
                'Carbon\\Carbon::toMutable',
                'Carbon\\Carbon::toNow',
                'Carbon\\Carbon::toObject',
                'Carbon\\Carbon::toRfc1036String',
                'Carbon\\Carbon::toRfc1123String',
                'Carbon\\Carbon::toRfc2822String',
                'Carbon\\Carbon::toRfc3339String',
                'Carbon\\Carbon::toRfc7231String',
                'Carbon\\Carbon::toRfc822String',
                'Carbon\\Carbon::toRfc850String',
                'Carbon\\Carbon::toRssString',
                'Carbon\\Carbon::toString',
                'Carbon\\Carbon::toTimeString',
                'Carbon\\Carbon::toW3cString',
                'Carbon\\Carbon::today',
                'Carbon\\Carbon::tomorrow',
                'Carbon\\Carbon::translate',
                'Carbon\\Carbon::tz',
                'Carbon\\Carbon::unix',
                'Carbon\\Carbon::until',
                'Carbon\\Carbon::useMonthsOverflow',
                'Carbon\\Carbon::useStrictMode',
                'Carbon\\Carbon::useYearsOverflow',
                'Carbon\\Carbon::utc',
                'Carbon\\Carbon::utcOffset',
                'Carbon\\Carbon::valueOf',
                'Carbon\\Carbon::week',
                'Carbon\\Carbon::weekYear',
                'Carbon\\Carbon::weekday',
                'Carbon\\Carbon::weeksInYear',
                'Carbon\\Carbon::year',
                'Carbon\\Carbon::years',
                'Carbon\\Carbon::yesterday',
                'Carbon\\CarbonInterval::add',
                'Carbon\\CarbonInterval::cascade',
                'Carbon\\CarbonInterval::compare',
                'Carbon\\CarbonInterval::compareDateIntervals',
                'Carbon\\CarbonInterval::copy',
                'Carbon\\CarbonInterval::create',
                'Carbon\\CarbonInterval::disableHumanDiffOption',
                'Carbon\\CarbonInterval::enableHumanDiffOption',
                'Carbon\\CarbonInterval::executeWithLocale',
                'Carbon\\CarbonInterval::forHumans',
                'Carbon\\CarbonInterval::fromString',
                'Carbon\\CarbonInterval::getAvailableLocales',
                'Carbon\\CarbonInterval::getCascadeFactors',
                'Carbon\\CarbonInterval::getDateIntervalSpec',
                'Carbon\\CarbonInterval::getDaysPerWeek',
                'Carbon\\CarbonInterval::getFactor',
                'Carbon\\CarbonInterval::getHoursPerDay',
                'Carbon\\CarbonInterval::getHumanDiffOptions',
                'Carbon\\CarbonInterval::getLocalTranslator',
                'Carbon\\CarbonInterval::getLocale',
                'Carbon\\CarbonInterval::getMicrosecondsPerMillisecond',
                'Carbon\\CarbonInterval::getMillisecondsPerSecond',
                'Carbon\\CarbonInterval::getMinutesPerHour',
                'Carbon\\CarbonInterval::getSecondsPerMinute',
                'Carbon\\CarbonInterval::getTranslator',
                'Carbon\\CarbonInterval::hasMacro',
                'Carbon\\CarbonInterval::instance',
                'Carbon\\CarbonInterval::invert',
                'Carbon\\CarbonInterval::isEmpty',
                'Carbon\\CarbonInterval::isStrictModeEnabled',
                'Carbon\\CarbonInterval::locale',
                'Carbon\\CarbonInterval::localeHasDiffOneDayWords',
                'Carbon\\CarbonInterval::localeHasDiffSyntax',
                'Carbon\\CarbonInterval::localeHasDiffTwoDayWords',
                'Carbon\\CarbonInterval::localeHasPeriodSyntax',
                'Carbon\\CarbonInterval::localeHasShortUnits',
                'Carbon\\CarbonInterval::macro',
                'Carbon\\CarbonInterval::make',
                'Carbon\\CarbonInterval::mixin',
                'Carbon\\CarbonInterval::resetMonthsOverflow',
                'Carbon\\CarbonInterval::resetYearsOverflow',
                'Carbon\\CarbonInterval::setCascadeFactors',
                'Carbon\\CarbonInterval::setHumanDiffOptions',
                'Carbon\\CarbonInterval::setLocalTranslator',
                'Carbon\\CarbonInterval::setLocale',
                'Carbon\\CarbonInterval::setTranslator',
                'Carbon\\CarbonInterval::shouldOverflowMonths',
                'Carbon\\CarbonInterval::shouldOverflowYears',
                'Carbon\\CarbonInterval::spec',
                'Carbon\\CarbonInterval::times',
                'Carbon\\CarbonInterval::toPeriod',
                'Carbon\\CarbonInterval::total',
                'Carbon\\CarbonInterval::useMonthsOverflow',
                'Carbon\\CarbonInterval::useStrictMode',
                'Carbon\\CarbonInterval::useYearsOverflow',
                'Carbon\\CarbonInterval::weeksAndDays',
                'Carbon\\CarbonPeriod::addFilter',
                'Carbon\\CarbonPeriod::count',
                'Carbon\\CarbonPeriod::create',
                'Carbon\\CarbonPeriod::createFromArray',
                'Carbon\\CarbonPeriod::createFromIso',
                'Carbon\\CarbonPeriod::current',
                'Carbon\\CarbonPeriod::disableHumanDiffOption',
                'Carbon\\CarbonPeriod::enableHumanDiffOption',
                'Carbon\\CarbonPeriod::excludeEndDate',
                'Carbon\\CarbonPeriod::excludeStartDate',
                'Carbon\\CarbonPeriod::executeWithLocale',
                'Carbon\\CarbonPeriod::first',
                'Carbon\\CarbonPeriod::getAvailableLocales',
                'Carbon\\CarbonPeriod::getDateClass',
                'Carbon\\CarbonPeriod::getDateInterval',
                'Carbon\\CarbonPeriod::getEndDate',
                'Carbon\\CarbonPeriod::getFilters',
                'Carbon\\CarbonPeriod::getHumanDiffOptions',
                'Carbon\\CarbonPeriod::getLocalTranslator',
                'Carbon\\CarbonPeriod::getLocale',
                'Carbon\\CarbonPeriod::getOptions',
                'Carbon\\CarbonPeriod::getRecurrences',
                'Carbon\\CarbonPeriod::getStartDate',
                'Carbon\\CarbonPeriod::getTranslator',
                'Carbon\\CarbonPeriod::hasFilter',
                'Carbon\\CarbonPeriod::hasMacro',
                'Carbon\\CarbonPeriod::invertDateInterval',
                'Carbon\\CarbonPeriod::isEndExcluded',
                'Carbon\\CarbonPeriod::isStartExcluded',
                'Carbon\\CarbonPeriod::isStrictModeEnabled',
                'Carbon\\CarbonPeriod::key',
                'Carbon\\CarbonPeriod::last',
                'Carbon\\CarbonPeriod::locale',
                'Carbon\\CarbonPeriod::localeHasDiffOneDayWords',
                'Carbon\\CarbonPeriod::localeHasDiffSyntax',
                'Carbon\\CarbonPeriod::localeHasDiffTwoDayWords',
                'Carbon\\CarbonPeriod::localeHasPeriodSyntax',
                'Carbon\\CarbonPeriod::localeHasShortUnits',
                'Carbon\\CarbonPeriod::macro',
                'Carbon\\CarbonPeriod::mixin',
                'Carbon\\CarbonPeriod::next',
                'Carbon\\CarbonPeriod::prependFilter',
                'Carbon\\CarbonPeriod::removeFilter',
                'Carbon\\CarbonPeriod::resetFilters',
                'Carbon\\CarbonPeriod::resetMonthsOverflow',
                'Carbon\\CarbonPeriod::resetYearsOverflow',
                'Carbon\\CarbonPeriod::rewind',
                'Carbon\\CarbonPeriod::setDateClass',
                'Carbon\\CarbonPeriod::setDateInterval',
                'Carbon\\CarbonPeriod::setDates',
                'Carbon\\CarbonPeriod::setEndDate',
                'Carbon\\CarbonPeriod::setFilters',
                'Carbon\\CarbonPeriod::setHumanDiffOptions',
                'Carbon\\CarbonPeriod::setLocalTranslator',
                'Carbon\\CarbonPeriod::setLocale',
                'Carbon\\CarbonPeriod::setOptions',
                'Carbon\\CarbonPeriod::setRecurrences',
                'Carbon\\CarbonPeriod::setStartDate',
                'Carbon\\CarbonPeriod::setTranslator',
                'Carbon\\CarbonPeriod::shouldOverflowMonths',
                'Carbon\\CarbonPeriod::shouldOverflowYears',
                'Carbon\\CarbonPeriod::skip',
                'Carbon\\CarbonPeriod::spec',
                'Carbon\\CarbonPeriod::toArray',
                'Carbon\\CarbonPeriod::toIso8601String',
                'Carbon\\CarbonPeriod::toString',
                'Carbon\\CarbonPeriod::toggleOptions',
                'Carbon\\CarbonPeriod::useMonthsOverflow',
                'Carbon\\CarbonPeriod::useStrictMode',
                'Carbon\\CarbonPeriod::useYearsOverflow',
                'Carbon\\CarbonPeriod::valid',
                'Carbon\\Carbon::useMicrosecondsFallback',
                'Carbon\\Carbon::isMicrosecondsFallbackEnabled',
                'Carbon\\Carbon::compareYearWithMonth',
                'Carbon\\Carbon::shouldCompareYearWithMonth',
                'Carbon\\CarbonInterval::getMinutesPerHours',
                'Carbon\\CarbonInterval::getSecondsPerMinutes',
                'Carbon\\Carbon::toATOMString',
                'Carbon\\Carbon::toCOOKIEString',
                'Carbon\\Carbon::toISO8601String',
                'Carbon\\Carbon::toRFC822String',
                'Carbon\\Carbon::toRFC850String',
                'Carbon\\Carbon::toRFC1036String',
                'Carbon\\Carbon::toRFC1123String',
                'Carbon\\Carbon::toRFC2822String',
                'Carbon\\Carbon::toRFC3339String',
                'Carbon\\Carbon::toRSSString',
                'Carbon\\Carbon::toW3CString',
                'Carbon\\Carbon::getRelativeTest',
            ];

            callback(null, methods.map(function (method) {
                method = method.split('::');

                return {
                    name: method[1],
                    value: method[1],
                    score: 5000000,
                    meta: method[0],
                };
            }).filter(function (method) {
                if (prefix.length === 0 || method.name.substr(0, prefix.length) === prefix) {
                    return true;
                }

                method.score = 4000000;

                return method.name.indexOf(prefix) !== -1;
            }));
        }
    });

    if (/[&?]hide-input-gutter&/.test(location.search + '&')) {
        input.renderer.setShowGutter(false);
    }

    var output = editor('output', 'ace/mode/html', true);

    if (/[&?]hide-output-gutter&/.test(location.search + '&')) {
        output.renderer.setShowGutter(false);
    }

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
