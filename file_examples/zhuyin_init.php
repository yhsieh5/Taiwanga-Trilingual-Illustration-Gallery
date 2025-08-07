<?php
/**
 * zhuyin_init.php
 *
 * Initializes the Zhuyin (Bopomofo) display system for Traditional Chinese text
 * on the website. Loads the parsed Zhuyin dictionary from JSON, builds an internal
 * mapping (`$zhuyin_map`), and provides functions to render annotated text with Zhuyin.
 *
 * Key Features:
 * - Supports both inline display (`applyZhuyin()`) and vertical-friendly ruby-style rendering (`applyZhuyinRuby()`).
 * - Loads word-level Zhuyin data from a parsed dictionary file (e.g., `self-parse.json`).
 * - Enables `zhuyin_mode` based on session or `?zhuyin=on/off` query.
 * - Handles punctuation, English words, mixed scripts, and multi-character Zhuyin annotations.
 * - Provides wrapper functions (`__z()` and `applyZhuyinText()`) for easier integration in templates.
 * - Supports HTML-preserving conversion (`applyZhuyinPreservingHtml()`), which annotates only visible text.
 *
 * Dependencies:
 * - Must be included after `lang.php` (which sets `$lang` and session state).
 * - Assumes valid `zhuyin_map` structure: [title => zhuyin string with tone marks].
 *
 * Example Usage:
 * - `applyZhuyinRuby("早安你好", 'zh', true, $zhuyin_map)`
 * - `__z('title')` for translated keys with ruby annotations
 *
 * Developed for the TaiwanGa multilingual illustration archive website.
 *
 * @author Yuchu Hsieh
 */
?>

<?php
ini_set('memory_limit', '1024M'); // increase up to 1GB

global $lang;

// zhuyin=on/off
if (isset($_GET['zhuyin'])) {
    $_SESSION['zhuyin_mode'] = $_GET['zhuyin'] === 'on';
}
$zhuyin_enabled = $_SESSION['zhuyin_mode'] ?? false;

// only apply when lang=zh
$zhuyin_data = [];
if (($lang ?? '') === 'zh' && $zhuyin_enabled) {
    // $path = __DIR__ . "/dict_revised_parsed_fixed.json";
    $path = __DIR__ . "/self-parse.json";
    if (!file_exists($path)) {
        error_log("❌ 檔案不存在: $path");
    } else {
        error_log("✅ 檔案存在: $path");
    }

    $raw_json = @file_get_contents($path);
    if ($raw_json === false) {
        error_log("❌ 無法讀取檔案: $path");
    } else {
        $zhuyin_data = json_decode($raw_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("❌ JSON 解碼錯誤: " . json_last_error_msg());
        }
    }
    // was using $zhuyin_data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];

    if (!is_array($zhuyin_data)) {
        $zhuyin_data = []; // prevent wrong data type/ empty file
    }

    if (empty($zhuyin_data)) {
        error_log("⚠️ 無法載入注音 JSON 檔案或格式錯誤: " . $path);
    }
    $zhuyin_map = [];
    foreach ($zhuyin_data as $entry) {
        if (isset($entry['title'], $entry['zhuyin'])) {
            if (!isset($zhuyin_map[$entry['title']])) {
                $zhuyin_map[$entry['title']] = $entry['zhuyin'];
            }
        }
    }
} else {
    $zhuyin_map = [];
}

// apply the function
// old apply zhuyin function (does not work for vertical writing text)
function applyZhuyin($text, $lang, $zhuyin_enabled, $zhuyin_map)
{
    if ($lang !== 'zh' || !$zhuyin_enabled)
        return htmlspecialchars($text);

    $out = '';
    $len = mb_strlen($text);
    $i = 0;

    while ($i < $len) {
        $matched = false;

        for ($l = min(20, $len - $i); $l >= 1; $l--) {
            $substr = mb_substr($text, $i, $l);

            if (isset($zhuyin_map[$substr])) {
                $bopomofo = $zhuyin_map[$substr];

                // normal display if it's a single character
                if (isset($zhuyin_map[$substr])) {
                    $bopomofo_raw = trim($zhuyin_map[$substr]);

                    // Normalize any whitespace (e.g., full-width spaces)
                    $bopomofo_clean = preg_replace('/[\x{3000}\s]+/u', ' ', $bopomofo_raw);
                    $chars = preg_split('//u', $substr, -1, PREG_SPLIT_NO_EMPTY);
                    $zhuyins = preg_split('/\s+/u', $bopomofo_clean);

                    // Match characters to zhuyin
                    foreach ($chars as $index => $ch) {
                        $zy = $zhuyins[$index] ?? '';
                        $out .= "<span class='dict-block'><span class='zh-char'>{$ch}</span><span class='zhuyin'>{$zy}</span></span>";
                    }

                    $i += $l;
                    $matched = true;
                    break;
                }
            }


        }
        // fallback word by word if not found
        if (!$matched) {
            $ch = mb_substr($text, $i, 1);
            if (isset($zhuyin_map[$ch])) {
                $zy = $zhuyin_map[$ch];
                $out .= "<span class='dict-block'><span class='zh-char'>{$ch}</span><span class='zhuyin'>{$zhuyin_map[$ch]}</span></span>";
            } else {
                $out .= "<span class='zh-char'>{$ch}</span>";
            }
            $i++;
        }
    }
    return $out;
}

// new ruby function to deal with vertical tone issue
function applyZhuyinRuby($text, $lang, $zhuyin_enabled, $zhuyin_map)
{
    if ($lang !== 'zh' || !$zhuyin_enabled)
        return htmlspecialchars($text);

    $out = '';
    $len = mb_strlen($text);
    $i = 0;

    while ($i < $len) {
        $matched = false;

        $ch = mb_substr($text, $i, 1);

        // deal with the \n signs for large paragraphs
        if ($ch === "\n") {
            $out .= "<br>";
            $i++;
            continue;
        }

        // tabs as well
        if ($ch === "\t") {
            $out .= str_repeat("&nbsp;", 4);
            $i++;
            continue;
        }

        // english, number, and dash
        if (preg_match('/[a-zA-ZÀ-ÿ0-9\-–—−]/u', $ch)) {
            $half = $ch;
            $j = $i + 1;
            while ($j < $len) {
                $next = mb_substr($text, $j, 1);
                if (preg_match('/[a-zA-ZÀ-ÿ0-9\-–—−]/u', $next)) {
                    $half .= $next;
                    $j++;
                } else {
                    break;
                }
            }
            $out .= '<span class="zhuyin-box halfwidth"><span class="zhuyin-char">' .
                htmlspecialchars($half) . '</span></span>';
            $i = $j;
            continue;
        }

        // non Chinese characters (punctuations, signs, spaces)
        if (!preg_match("/\p{Han}/u", $ch) || preg_match('/[。，、「」？！：；（）［］]（）「」]/u', $ch)) {
            $out .= '<span class="zhuyin-box no-zhuyin"><span class="zhuyin-char">' .
                htmlspecialchars($ch) . '</span></span>';
            $i++;
            continue;
        }

        // if it's Chinese Characters, start finding words (prioritize longest)
        for ($l = min(20, $len - $i); $l >= 1; $l--) {
            $substr = mb_substr($text, $i, $l);
            if (!preg_match("/\p{Han}/u", $substr) || preg_match('/[。，、「」？！：；（）［］｛｝｛｝‵「」]/u', $substr))
                continue;

            if (isset($zhuyin_map[$substr])) {
                $zy_raw = trim($zhuyin_map[$substr]);
                $chars = preg_split('//u', $substr, -1, PREG_SPLIT_NO_EMPTY);
                $zy_parts = preg_split('/\s+/u', $zy_raw);

                if (count($chars) === count($zy_parts)) {
                    foreach ($chars as $j => $char) {
                        $rt = implode('', preg_split('//u', preg_replace('/\s+/u', '', $zy_parts[$j]), -1, PREG_SPLIT_NO_EMPTY));
                        $out .= "<span class='zhuyin-box'>
                                    <span class='zhuyin-rt'>{$rt}</span>
                                    <span class='zhuyin-char'>" . htmlspecialchars($char) . "</span>
                                 </span>";
                    }
                } else {
                    foreach ($chars as $ch2) {
                        if (!preg_match("/\p{Han}/u", $ch2) || !isset($zhuyin_map[$ch2])) {
                            $out .= htmlspecialchars($ch2);
                            continue;
                        }
                        $rt = implode('', preg_split('//u', preg_replace('/\s+/u', '', $zhuyin_map[$ch2]), -1, PREG_SPLIT_NO_EMPTY));
                        $out .= "<span class='zhuyin-box'>
                                    <span class='zhuyin-rt'>{$rt}</span>
                                    <span class='zhuyin-char'>" . htmlspecialchars($ch2) . "</span>
                                 </span>";
                    }
                }

                $i += $l;
                $matched = true;
                break;
            }
        }

        // fallback
        if (!$matched) {
            if (isset($zhuyin_map[$ch])) {
                $rt = implode('', preg_split('//u', preg_replace('/\s+/u', '', $zhuyin_map[$ch]), -1, PREG_SPLIT_NO_EMPTY));
                $out .= "<span class='zhuyin-box'>
                            <span class='zhuyin-rt'>{$rt}</span>
                            <span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span>
                         </span>";
            } else {
                // $out .= htmlspecialchars($ch);
                $out .= '<span class="zhuyin-box no-zhuyin"><span class="zhuyin-char">' .
                    htmlspecialchars($ch) . '</span></span>';
            }
            $i++;
        }
    }

    return $out;
}




// global helper function that automatically supplies the missing arguments using global.
// for translated keys
function __z($key)
{
    global $lang, $zhuyin_enabled, $zhuyin_map;
    return applyZhuyinRuby(__($key), $lang, $zhuyin_enabled, $zhuyin_map);
}

// for other texts
function applyZhuyinText($text)
{
    global $lang, $zhuyin_enabled, $zhuyin_map;
    return applyZhuyinRuby($text, $lang, $zhuyin_enabled, $zhuyin_map);
}

function applyZhuyinPreservingHtml($text, $lang, $zhuyin_enabled, $zhuyin_map)
{
    if (!$zhuyin_enabled || $lang !== 'zh')
        return $text;

    // keep the HTML tags
    return preg_replace_callback('/(<[^>]+>|[^<]+)/u', function ($match) use ($zhuyin_map) {
        $part = $match[0];

        // skip the HTML tags, do not process them
        if (preg_match('/^<[^>]+>$/', $part)) {
            return $part;
        }

        $output = '';
        $i = 0;
        $len = mb_strlen($part);

        while ($i < $len) {
            $matched = false;

            for ($l = min(6, $len - $i); $l >= 1; $l--) {
                $substr = mb_substr($part, $i, $l);
                if (isset($zhuyin_map[$substr])) {
                    $zy_raw = trim($zhuyin_map[$substr]);
                    $chars = preg_split('//u', $substr, -1, PREG_SPLIT_NO_EMPTY);
                    $zy_parts = preg_split('/\s+/u', $zy_raw);

                    if (count($chars) === count($zy_parts)) {
                        foreach ($chars as $j => $ch) {
                            $bpmf = htmlspecialchars($zy_parts[$j]);
                            $output .= "<span class='zhuyin-box'><span class='zhuyin-rt'>{$bpmf}</span><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                        }
                    } else {
                        // fallback
                        foreach ($chars as $ch) {
                            $bpmf = htmlspecialchars($zhuyin_map[$ch] ?? '');
                            if ($bpmf) {
                                $output .= "<span class='zhuyin-box'><span class='zhuyin-rt'>{$bpmf}</span><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                            } elseif (preg_match('/\p{Han}/u', $ch) && isset($zhuyin_map[$ch])) {
                                // characters without zhuyin
                                $output .= "<span class='zhuyin-box'><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                            } else {
                                // other punctuations
                                $output .= "<span class='zhuyin-box no-zhuyin'><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                            }
                        }
                    }

                    $i += $l;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $ch = mb_substr($part, $i, 1);
                if (isset($zhuyin_map[$ch])) {
                    $bpmf = htmlspecialchars($zhuyin_map[$ch]);
                    $output .= "<span class='zhuyin-box'><span class='zhuyin-rt'>{$bpmf}</span><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                } elseif (preg_match('/\p{Han}/u', $ch) && isset($zhuyin_map[$ch])) {
                    $output .= "<span class='zhuyin-box'><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                }
                // Collect a run of halfwidth chars
                elseif ($ch === "\n") {
                    $output .= "<br>";
                    $i++;
                    continue;
                } elseif ($ch === "\t") {
                    $output .= "<span class='zhuyin-box no-zhuyin'><span class='zhuyin-char'>&emsp;</span></span>";
                    $i++;
                    continue;
                } elseif (preg_match('/[a-zA-ZÀ-ÿ0-9\-–—−]/u', $ch)) {
                    $half = $ch;
                    $j = $i + 1;
                    while ($j < $len) {
                        $next = mb_substr($part, $j, 1);
                        if (preg_match('/[a-zA-ZÀ-ÿ0-9\-–—−]/u', $next)) {
                            $half .= $next;
                            $j++;
                        } else {
                            break;
                        }
                    }
                    $output .= "<span class='zhuyin-box halfwidth'><span class='zhuyin-char'>" . htmlspecialchars($half) . "</span></span>";
                    $i = $j;
                    continue;
                } else {
                    $output .= "<span class='zhuyin-box no-zhuyin'><span class='zhuyin-char'>" . htmlspecialchars($ch) . "</span></span>";
                }
                $i++;
            }
        }

        return $output;
    }, $text);
}

?>