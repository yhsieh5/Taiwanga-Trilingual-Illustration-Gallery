<?php
/**
 * sidebar.php
 *
 * This script renders the sidebar interface for filtering photos by tags, dates, and search terms.
 * It processes and loads relevant data from the database (such as tag hierarchy, photo counts by month/year),
 * builds filter forms, and manages localization (multi-language support).
 *
 * Features:
 * - Multi-language search and filter UI (Chinese, English, Japanese)
 * - Tag filtering using hierarchical tree with checkboxes
 * - Date filtering by year and month (auto-expanded tree view)
 * - Search input parsing and alias keyword support
 * - Integrates with zhuyin annotation mode for Traditional Chinese
 *
 * Dependencies:
 * - Requires `db.php` for database access
 * - Uses `$tag_translations`, `$alias_table` from `tag_lang.php` and `search_aliases.php`
 * - Assumes integration with main pages like `index.php`, `photo.php`, `tag.php`
 * 
 * This file is part of the Taiwanga Illustration Archive frontend (public-facing only).
 *
 * Author: Yu-Chu Hsieh
 */
?>

<?php
include_once('config.php');
include("includes/db.php");

$alias_table = include("includes/search_aliases.php");
$tag_translations = include("includes/tag_lang.php");


// month and year lists
$month_filters = [];
$year_months = [];

// pull from database（in YYYY-MM）
$stmt = $pdo->query("SELECT DISTINCT DATE_FORMAT(upload_date, '%Y-%m') AS ym FROM photos ORDER BY ym DESC");
$ym_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($ym_list as $ym) {
    [$year, $month] = explode('-', $ym);
    $year_months[$year][] = $ym;
    $month_filters[$ym] = (int) $month; // month in num 1~12
}

// search parameters
$selected_tags = array_filter($_GET['tags'] ?? [], fn($t) => trim($t) !== '');
$selected_months = array_filter($_GET['months'] ?? [], fn($m) => preg_match('/^\d{4}-\d{2}$/', $m));
$where = [];
$params = [];
$search_input = $_GET['q'] ?? '';
$search_terms = [];
$keywords = preg_split('/\s+/', trim($search_input)); // 拆字詞（用空白分隔）

// search alias list
$reverse_alias = [];
foreach ($alias_table as $canonical => $aliases) {
    foreach ($aliases as $alias) {
        $reverse_alias[$alias] = $canonical;
    }
}

// keywords
$subconds = [];
$params = [];

foreach ($keywords as $word) {
    if ($word === '')
        continue;
    $search_terms[] = $word;
    if (isset($reverse_alias[$word])) {
        $search_terms[] = $reverse_alias[$word];
    }
}

// all search_terms conditions
foreach ($search_terms as $term) {
    $subconds[] = "(title LIKE ? OR title_en LIKE ? OR title_jp LIKE ?
    OR description LIKE ? OR description_en LIKE ?  OR description_jp LIKE ?
    OR file_path LIKE ? OR photos.id IN (
    SELECT pt.photo_id FROM photo_tags pt
    JOIN tags t ON pt.tag_id = t.id
    WHERE t.name LIKE ?
  ))";
    $params[] = "%$term%";
    $params[] = "%$term%";
    $params[] = "%$term%";
    $params[] = "%$term%";
    $params[] = "%$term%";
    $params[] = "%$term%";
    $params[] = "%$term%";
    $params[] = "%$term%";
}

if ($subconds) {
    $where[] = '(' . implode(' OR ', $subconds) . ')';
}

// tags
if (!empty($selected_tags)) {
    $placeholders = implode(',', array_fill(0, count($selected_tags), '?'));
    $where[] = "photos.id IN (
        SELECT pt.photo_id FROM photo_tags pt
        JOIN tags t ON pt.tag_id = t.id
        WHERE t.name IN ($placeholders)
    )";
    foreach ($selected_tags as $t)
        $params[] = $t;
}

// months
if (!empty($selected_months)) {
    $or_months = [];
    foreach ($selected_months as $month) {
        $or_months[] = "DATE_FORMAT(upload_date, '%Y-%m') = ?";
        $params[] = $month;
    }
    $where[] = '(' . implode(' OR ', $or_months) . ')';
}

// number of each tags
$month_counts = [];
$stmt = $pdo->query("SELECT DATE_FORMAT(upload_date, '%Y-%m') AS ym, COUNT(*) AS count
                     FROM photos
                     GROUP BY ym");
foreach ($stmt as $row) {
    $month_counts[$row['ym']] = $row['count'];
}
// month tags adding up as year tags
$year_counts = [];
foreach ($month_counts as $ym => $count) {
    $year = substr($ym, 0, 4);
    if (!isset($year_counts[$year]))
        $year_counts[$year] = 0;
    $year_counts[$year] += $count;
}

function getTagHierarchy($pdo)
{
    $stmt = $pdo->query("SELECT id, name, parent_id FROM tags ORDER BY name");
    $tags = [];
    $tree = [];
    while ($row = $stmt->fetch()) {
        $tags[$row['id']] = $row;
        $tags[$row['id']]['children'] = [];
    }
    foreach ($tags as $id => &$tag) {
        if ($tag['parent_id']) {
            $tags[$tag['parent_id']]['children'][] = &$tag;
        } else {
            $tree[] = &$tag;
        }
    }
    return $tree;
}

$tag_tree = getTagHierarchy($pdo);

$tag_counts = [];
$stmt = $pdo->query("SELECT t.name, COUNT(pt.photo_id) AS count
                     FROM photo_tags pt
                     JOIN tags t ON pt.tag_id = t.id
                     GROUP BY t.name");
foreach ($stmt as $row) {
    $tag_counts[$row['name']] = $row['count'];
}

// tag checkbox tree
function renderTagCheckboxTree($tags, $selected_tags, $tag_translations, $lang, $tag_counts, $level = 0, $zhuyin_enabled = false, $zhuyin_map = [])
{
    foreach ($tags as $tag) {
        $checked = in_array($tag['name'], $selected_tags) ? 'checked' : '';
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $has_children = !empty($tag['children']);
        $toggle_icon = $has_children ? '<span class="toggle-icon collapsed">🔽</span>' : '';

        $count = $tag_counts[$tag['name']] ?? 0;
        $label = $tag_translations[$tag['name']][$lang] ?? $tag['name'];

        // add zhuyin process in
        if ($zhuyin_enabled && $lang === 'zh') {
            $label = applyZhuyinRuby($label, $lang, $zhuyin_enabled, $zhuyin_map);
        } else {
            $label = htmlspecialchars($label);
        }

        echo "<div class='tag-item' data-level='$level'>";
        echo "$indent<label><input type='checkbox' name='tags[]' value='" . htmlspecialchars($tag['name']) . "' $checked> ";
        echo "$label ($count) </label> $toggle_icon</div>";

        if ($has_children) {
            if (!$zhuyin_enabled) {
                echo "<div class='tag-children' style='display: none; margin-left: " . (5 * ($level + 1)) . "px;'>";
                renderTagCheckboxTree($tag['children'], $selected_tags, $tag_translations, $lang, $tag_counts, $level + 1, $zhuyin_enabled, $zhuyin_map);
                echo "</div>";

            } else {
                echo "<div class='tag-children' style='display: none; margin-left: 0.2px;'>";
                renderTagCheckboxTree($tag['children'], $selected_tags, $tag_translations, $lang, $tag_counts, $level + 1, $zhuyin_enabled, $zhuyin_map);
                echo "</div>";
            }

            // for debugging
            // echo "<div class='tag-children' style='display: none; margin-left: " . (5 * ($level + 1)) . "px;'>";
            // renderTagCheckboxTree($tag['children'], $selected_tags, $tag_translations, $lang, $tag_counts, $level + 1, $zhuyin_enabled, $zhuyin_map);
            // echo "</div>";
        }
    }
}
?>

<div class="search module">
    <form method="GET" action="tag.php">
        <button type="button" id="toggle-sidebar" class="sidebar-toggle">☰</button>
        <div id="sidebar-overlay"></div>
        <div id="sidebar-wrapper">
            <div id="sidebar-container" class="sidebar">
                <div class="sidebar-inner">
                    <div class="sort-toggle">
                        <?php
                        $current_sort = $_GET['sort'] ?? 'desc';
                        $new_sort = $current_sort === 'asc' ? 'desc' : 'asc';
                        $sort_label = $current_sort === 'desc' ? __z('new_to_old') : __z('old_to_new');

                        $query_params = $_GET;
                        $query_params['sort'] = $new_sort;
                        $toggle_url = 'tag.php?' . http_build_query($query_params);
                        ?>
                        <a href="<?= $toggle_url ?>" class="sort-button">
                            <?= $sort_label ?> 🔁
                        </a>
                    </div>

                    <div class="search-box">
                        <input type="text" name="q" placeholder="<?= __('search_placeholder') ?>"
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

                        <button type="submit"><?= __z('search') ?></button>
                    </div>

                    <?php if ($_GET['admin'] ?? '' === 'true'): ?>
                        <p><a href="<?= BASE_URL ?>upload.php">➕ <?= __('upload') ?></a></p>
                    <?php endif; ?>

                    <?php if (!empty($search_terms)): ?>
                        <p style="color: #666; font-size: 14px;">🔍
                            <?= __z('searching_for') ?>     <?= implode(', ', array_map('htmlspecialchars', $search_terms)) ?>
                        </p>
                    <?php endif; ?>

                    <h3><?= __z('filter_by_date') ?></h3>

                    <?php foreach ($year_months as $year => $months): ?>
                        <?php if (!isset($year_counts[$year]))
                            continue; ?>
                        <div class="year-item">
                            <label><input type="checkbox" class="year-checkbox" data-year="<?= $year ?>"> <?= $year ?>
                                (<?= $year_counts[$year] ?>)</label>
                            <span class="toggle-icon collapsed">🔽</span>
                            <div class="month-children" style="display: none; margin-left: 20px;">
                                <?php foreach ($months as $month_key): ?>
                                    <?php if (!isset($month_counts[$month_key]))
                                        continue; ?>
                                    <?php
                                    $label = $month_filters[$month_key];
                                    $checked = in_array($month_key, $selected_months) ? 'checked' : '';
                                    $month_count = $month_counts[$month_key] ?? 0;
                                    $month_names = [
                                        'zh' => function ($m) {
                                            return $m . '月';
                                        },
                                        'jp' => function ($m) {
                                            return $m . '月';
                                        },
                                        'en' => function ($m) {
                                            $names = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                            return $names[intval($m)] ?? $m;
                                        }
                                    ];
                                    ?>
                                    <label>
                                        <input type="checkbox" class="month-checkbox" name="months[]" value="<?= $month_key ?>"
                                            data-year="<?= $year ?>" <?= $checked ?>>
                                        <?= $month_names[$lang]($label) ?> (<?= $month_count ?>)
                                    </label><br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <h3><?= __z('filter_by_tag') ?></h3>
                    <?php renderTagCheckboxTree($tag_tree, $selected_tags, $tag_translations, $lang, $tag_counts, 0, $zhuyin_enabled, $zhuyin_map); ?>

                    <br>
                    <div class="apply-button-wrapper">
                        <button type="submit" class="apply-button"><?= __z('search') ?></button>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>