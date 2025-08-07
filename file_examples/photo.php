<?php
/**
 * photo.php
 *
 * Displays the detail page for a single illustration in the ClipArTaiwAnga gallery.
 * Dynamically loads image metadata, language-specific titles and descriptions, SEO tags,
 * and provides navigation and related illustrations.
 *
 * Features:
 * - Loads illustration info by ID from the database.
 * - Preserves filtering/search parameters from previous queries.
 * - Shows next/previous image navigation within the filtered result.
 * - Renders multilingual title, description, tags, and metadata.
 * - Generates related image suggestions based on overlapping tags.
 * - Provides SEO metadata and JSON-LD schema for better indexing.
 * - Enables image download and displays upload date and tags.
 *
 * Used together with:
 * - lang.php: Handles language switching and translation.
 * - zhuyin_init.php: Initializes Bopomofo (Zhuyin) display.
 * - db.php: Connects to the database and retrieves photo/tag data.
 * - header.php / footer.php / sidebar.php: Shared modular components.
 *
 * This file is intended for dynamic use within the site and depends on the rest of the system.
 *
 * @author Yuchu Hsieh
 */
?>

<?php
include_once('config.php');
include("includes/db.php");
include("includes/lang.php");
$lang = $_SESSION['lang'] ?? 'zh';
include("includes/zhuyin_init.php");

$id = $_GET['id'] ?? null;
if (!$id || !ctype_digit($id)) {
    echo "Invalid photo ID.";
    exit;
}

// read the filtered parameter from tag.php to keep the search record in the link
$tags = $_GET['tags'] ?? [];
$years = $_GET['years'] ?? [];
$months = $_GET['months'] ?? [];

// get the current illustration info
$stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ?");
$stmt->execute([$id]);
$photo = $stmt->fetch();

if (!$photo) {
    echo "Photo not found.";
    exit;
}

// get the tags
$tags_stmt = $pdo->prepare("SELECT t.name FROM photo_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.photo_id = ?");
$tags_stmt->execute([$id]);
$tags_on_photo = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);

// rebuild the filter, find all
$query = "SELECT DISTINCT p.id FROM photos p";
$params = [];
$joins = [];
$wheres = [];

if (!empty($tags)) {
    $joins[] = "JOIN photo_tags pt ON p.id = pt.photo_id";
    $joins[] = "JOIN tags t ON pt.tag_id = t.id";
    $placeholders = implode(',', array_fill(0, count($tags), '?'));
    $wheres[] = "pt.tag_id IN ($placeholders)";
    $params = array_merge($params, $tags);
}
if (!empty($months)) {
    $placeholders = implode(',', array_fill(0, count($months), '?'));
    $wheres[] = "DATE_FORMAT(p.upload_date, '%Y-%m') IN ($placeholders)";
    $params = array_merge($params, $months);
}
if (!empty($years)) {
    $year_conditions = [];
    foreach ($years as $year) {
        $year_conditions[] = "YEAR(p.upload_date) = ?";
        $params[] = $year;
    }
    $wheres[] = '(' . implode(' OR ', $year_conditions) . ')';
}

if ($joins)
    $query .= ' ' . implode(' ', $joins);
if ($wheres)
    $query .= ' WHERE ' . implode(' AND ', $wheres);
$query .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$photo_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// find the path
$current_index = array_search((int) $id, array_map('intval', $photo_ids));

$prev_id = $photo_ids[$current_index - 1] ?? null;
$next_id = $photo_ids[$current_index + 1] ?? null;

// keep the parameters for seraching (exclude the id)
$preserved_params = $_GET;
unset($preserved_params['id']);
$query_str = http_build_query($preserved_params);

// function: similar illustrations
$tag_stmt = $pdo->prepare("SELECT t.id FROM photo_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.photo_id = ?");
$tag_stmt->execute([$id]);
$current_tag_ids = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);

// find other illustrations with the most overlapping tags
if (!empty($current_tag_ids)) {
    $placeholders = implode(',', array_fill(0, count($current_tag_ids), '?'));
    $sql = "
      SELECT pt.photo_id, COUNT(*) AS common_tags
      FROM photo_tags pt
      WHERE pt.tag_id IN ($placeholders)
        AND pt.photo_id != ?
      GROUP BY pt.photo_id
      ORDER BY common_tags DESC, pt.photo_id DESC
      LIMIT 6
    ";
    $related_stmt = $pdo->prepare($sql);
    $related_stmt->execute([...$current_tag_ids, $id]);
    $related_ids = $related_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// get the info of these illustrations
$related_photos = [];
if (!empty($related_ids)) {
    $placeholders = implode(',', array_fill(0, count($related_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM photos WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
    $stmt->execute(array_merge($related_ids, $related_ids)); // FIELD has the same order
    $related_photos = $stmt->fetchAll();
}

// for debugging
// echo "<pre>";
// echo "Current ID: $id\n";
// echo "Photo IDs:\n";
// print_r($photo_ids);
// echo "Current Index: $current_index\n";
// echo "</pre>";
?>

<!DOCTYPE html>
<html lang="<?= $lang_attr ?>" class="<?= $zhuyin_enabled ? 'zhuyin-mode' : '' ?>">

<head>
    <meta charset="UTF-8">
    <?php
    switch ($lang) {
        case 'en':
            $title = $photo['title_en'] ?: $photo['title'];
            $desc = $photo['description_en'] ?: $photo['description'];
            break;
        case 'jp':
            $title = $photo['title_jp'] ?: $photo['title'];
            $desc = $photo['description_jp'] ?: $photo['description'];
            break;
        default:
            $title = $photo['title'];
            $desc = $photo['description'];
    }
    $universal_keywords = [
        'zh' => ['台灣', '插畫', '插圖', '素材', '圖檔', '圖案', '手繪', '去背', '去背圖', 'PNG', 'png', '免費', '免版稅', '商用', '可愛', 'illustration'],
        'en' => ['Taiwan', 'Taiwanese', 'illustration', 'clipart', 'drawing', 'free image', 'transparent background', 'digital art', 'free-to-use', 'cute', 'PNG', 'png'],
        'jp' => ['台湾', 'タイワン', 'たいわん', 'イラスト', 'いらすと', 'かわいい', '素材', '商用', '無料', 'illustration', 'PNG', 'png']
    ];

    // prepare the translated tags
    $translated_tags = [];
    $tag_lang = include("includes/tag_lang.php");
    foreach ($tags_on_photo as $tag) {
        $translated_tags[] = $tag_lang[$tag][$lang] ?? $tag;
    }

    // add the words from search_aliases.php
    $alias_table = include ("includes/search_aliases.php") ?? [];
    $all_keywords = array_merge([$title, $desc], $translated_tags);

    foreach ($tags_on_photo as $tag) {
        $all_keywords[] = $tag; // original tag in English
        if (isset($alias_table[$tag])) {
            $all_keywords = array_merge($all_keywords, $alias_table[$tag], $universal_keywords[$lang] ?? ['illustration']);
        }
    }
    $all_keywords = array_unique(array_filter($all_keywords, fn($kw) => trim($kw) !== ''));

    // combine JSON-LD
    $image_url = BASE_URL . $photo['file_path'];
    $page_url = "https://taiwanga.com/photo.php?id=" . $photo['id'];

    $json_ld = [
        "@context" => "https://schema.org",
        "@type" => "ImageObject",
        "contentUrl" => $image_url,
        "url" => $page_url,
        "name" => $title,
        "caption" => $desc,
        "keywords" => implode(', ', $all_keywords),
        "datePublished" => $photo['upload_date'],
        "inLanguage" => $lang_attr,
        "creator" => [
            "@type" => "Person",
            "name" => "Yuchu Hsieh"
        ],
        "creditText" => "ClipArTaiwAnga（台灣畫尪仔）",
        "license" => "https://taiwanga.com/terms.php",
        "acquireLicensePage" => "https://taiwanga.com/terms.php",
        "copyrightNotice" => "© " . date('Y') . " ClipArTaiwAnga. 可免費商用，詳情請見使用條款。"
    ];
    ?>

    <title><?= htmlspecialchars($title) ?></title>
    <link rel="canonical" href="https://taiwanga.com/photo.php?id=<?= $photo['id'] ?>">

    <script type="application/ld+json">
        <?= json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <meta name="description" content="<?= $desc ?>">
    <meta name="keywords" content="<?= htmlspecialchars(implode(', ', $all_keywords)) ?>">

    <!-- OG Meta -->
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>" />
    <meta property="og:site_name" content="<?= __('title') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($desc) ?>" />
    <meta property="og:image" content="<?= BASE_URL . $photo['file_path'] ?>" />
    <meta property="og:url" content="https://taiwanga.com/photo.php?id=<?= $photo['id'] ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:locale" content="<?= $lang === 'jp' ? 'ja_JP' : ($lang === 'en' ? 'en_US' : 'zh_TW') ?>">
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <!-- <meta name="twitter:site" content="@your_twitter_handle"> "no_twitter(x)_account_for_now -->
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= $desc ?>">
    <meta name="twitter:image" content="<?= BASE_URL . $photo['file_path'] ?>" />
    <meta name="twitter:url" content="https://taiwanga.com/photo.php?id=<?= $photo['id'] ?>" />

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="<?= BASE_URL ?>includes/favicon.png" type="image/png">
    <!-- style.css sync loading -->
    <!-- <link rel="stylesheet" href="<?= BASE_URL ?>style.css"> -->
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css" media="print" onload="this.onload=null;this.media='all'">
    <noscript>
        <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    </noscript>

    <!-- footer.css delayed loading -->
    <link rel="preload" href="<?= BASE_URL ?>includes/footer.css" as="style" onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="<?= BASE_URL ?>includes/footer.css">
    </noscript>
</head>

<body>
    <?php include("includes/header.php"); ?>

    <div class='main-container'>
        <?php include("includes/sidebar.php"); ?>
        <div class="visually-hidden">
            <h1><?= htmlspecialchars($title) ?></h1>
            <p><?= $desc ?></p>
        </div>
        <div style="margin: 20px; align-items: center;">
            <div class="photo_prev_nav">
                <p>
                    <a href="<?= BASE_URL ?>" class="backhome">← <?= __z('back_home') ?></a>
                </p>
                <?php
                // if there are searching parameters, go back to the result link
                $back_query = $_GET;
                unset($back_query['id']); // remove the current id
                $back_to_search = BASE_URL . 'tag.php?' . http_build_query($back_query);
                ?>
                <?php if (!empty($back_query)): ?>
                    <p><a href="<?= $back_to_search ?>" class="backhome">← <?= __z('back_to_search') ?></a></p>
                <?php endif; ?>
            </div>
            <div class="photo_info">
                <div style="width: 100%; text-align: center;">
                    <div
                        style="max-width: 300px; width: 100%;
                                background: #ffffff; padding: 10px; border: 3px dashed #ddd; border-radius: 6px; display: inline-block;">
                        <img src="<?= BASE_URL . $photo['file_path'] ?>" alt="<?= htmlspecialchars($title) ?>"
                            style="display: block; max-width: 100%; height: auto; margin: 0 auto; border-radius: 4px;">
                    </div>
                </div>
                <div style="display: flex; flex-direction: row;">
                    <h2><?= __z($title) ?></h2>

                    <!-- download button -->
                    <p>
                        <a href="<?= BASE_URL . $photo['file_path'] ?>" download class="tag"
                            style="margin-left: 10px; font-size: 20px;">
                            ⬇️ <?= __z('download_photo') ?>
                        </a>
                    </p>
                </div>
                <div class="photo_description"><p><?= nl2br(__z($desc)) ?></p></div>
                
                <p><strong><?= __z('upload_date') ?></strong> <?= $photo['upload_date'] ?></p>

                <?php if (!empty($tags_on_photo)): ?>
                    <p><strong><?= __z('tags') ?></strong>
                        <?php foreach ($tags_on_photo as $tag): ?>
                            <?php
                            $label = __z($tag_translations[$tag][$lang] ?? $tag);
                            $url = BASE_URL . '?tags[]=' . urlencode($tag);
                            ?>
                            <a href="<?= $url ?>" class="tag"><?= $label ?></a>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <!-- 🔁 next/ prev nav button -->
                <div style="margin-top: 20px; width: 100%; text-align: center;">
                    <?php if ($prev_id): ?>
                        <a href="photo.php?id=<?= $prev_id ?>&<?= $query_str ?>" class="tag">← <?= __z('prev_photo') ?></a>
                    <?php endif; ?>
                    <?php if ($next_id): ?>
                        <a href="photo.php?id=<?= $next_id ?>&<?= $query_str ?>" class="tag"><?= __z('next_photo') ?> →</a>
                    <?php endif; ?>
                </div>

                <!-- similar illustrations -->
                <?php if (!empty($related_photos)): ?>
                    <h2><?= __z('related_illustrations') ?></h2>
                    <div class="related-gallery">
                        <?php foreach ($related_photos as $p): ?>
                            <div class="related-item">
                                <a href="photo.php?id=<?= $p['id'] ?>">
                                    <img src="<?= htmlspecialchars($p['file_path']) ?>"
                                        alt="<?= htmlspecialchars(getLocalizedTitle($p, $lang)) ?>">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
    <script src="<?= BASE_URL ?>script.js"></script>
</body>

</html>