<?php
/**
 * index.php
 *
 * Main homepage for ClipArTaiwAnga, a free Taiwan-themed illustration gallery.
 * This page displays the main gallery view with thumbnails, titles, tags, and pagination.
 * Supports multilingual interface (Traditional Chinese, English, Japanese) and Zhuyin (Bopomofo) display mode.
 *
 * Integrates the following components:
 * - config.php: Global configuration constants
 * - db.php: Database connection (PDO)
 * - lang.php: Language session handling and translation
 * - zhuyin_init.php: Initialization for Zhuyin display mode
 * - header.php / footer.php / sidebar.php: Modular page components
 *
 * Dynamically loads illustrations from the database and applies sorting, filtering, and pagination.
 * Includes SEO meta tags and JSON-LD schema for better search engine indexing.
 *
 * @author Yuchu Hsieh
 */
?>

<?php
// for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include_once('config.php');
include("includes/db.php");
include("includes/lang.php");
$lang = $_SESSION['lang'] ?? 'zh';
include("includes/zhuyin_init.php");

if (preg_match('#^/index\.php($|\?)#', $_SERVER['REQUEST_URI'])) {
  $query = $_SERVER['QUERY_STRING'] ?? '';
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: /" . ($query ? "?$query" : ""));
  exit;
}

if (isset($_GET['admin']) && $_GET['admin'] === 'true') {
  if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
  }
}
if (isset($_GET['admin']) && $_GET['admin'] !== 'true' && $_GET['admin'] !== 'false') {
  // Bad format: block or redirect
  header("Location: /");
  exit;
}
function build_query_url($new_page)
{
  $query = $_GET;
  $query['page'] = $new_page;
  return BASE_URL . '?' . http_build_query($query);
}

// page setting
$per_page = $zhuyin_enabled ? 8 : 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// total page count
$total_stmt = $pdo->query("SELECT COUNT(*) FROM photos");
$total_photos = $total_stmt->fetchColumn();
$total_pages = ceil($total_photos / $per_page);

// main searching: old to new ordering, with LIMIT and OFFSET
$order = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$stmt = $pdo->prepare("SELECT * FROM photos ORDER BY id $order LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$photos = $stmt->fetchAll();

// get all the tags of each picture
$photo_ids = array_column($photos, 'id');
$photo_tags_map = [];
if (!empty($photo_ids)) {
  $placeholders = implode(',', array_fill(0, count($photo_ids), '?'));
  $tag_stmt = $pdo->prepare("SELECT pt.photo_id, t.name FROM photo_tags pt JOIN tags t ON pt.tag_id = t.id WHERE pt.photo_id IN ($placeholders)");
  $tag_stmt->execute($photo_ids);
  foreach ($tag_stmt->fetchAll() as $row) {
    $photo_tags_map[$row['photo_id']][] = $row['name'];
  }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang_attr ?>" class="<?= $zhuyin_enabled ? 'zhuyin-mode' : '' ?>">

<head>
  <?php
  $lang = $_SESSION['lang'] ?? 'zh';
  $descriptions = [
    'zh' => '台灣畫尪仔是一個免費可商用插畫圖庫，收錄台灣主題的插圖，支援多語言與標籤搜尋。',
    'en' => 'ClipArTaiwAnga is a free illustration gallery offering Taiwan-themed illustration for personal and commercial use, with multilingual and tag-based search.',
    'jp' => '台湾アンアート素材集は台湾をテーマにしたイラスト素材を無料で提供するギャラリーです。多言語とタグ検索に対応しています。'
  ];
  $desc = htmlspecialchars($descriptions[$lang] ?? '');

  $universal_keywords = [
    'zh' => ['台灣', '插畫', '插圖', '素材', '圖檔', '圖案', '手繪', '去背', '去背圖', 'PNG', 'png', '免費', '免版稅', '商用', '可愛', 'illustration'],
    'en' => ['Taiwan', 'Taiwanese', 'illustration', 'clipart', 'drawing', 'free image', 'transparent background', 'digital art', 'free-to-use', 'cute', 'PNG', 'png'],
    'jp' => ['台湾', 'タイワン', 'たいわん', 'イラスト', 'いらすと', 'かわいい', '素材', '商用', '無料', 'illustration', 'PNG', 'png']
  ];
  $keywords = $universal_keywords[$lang] ?? [];

  $json_ld = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "url" => "https://taiwanga.com/",
    "name" => "台灣畫尪仔 ClipArTaiwAnga",
    "description" => $desc,
    "keywords" => implode(', ', $keywords),
    "inLanguage" => $lang_attr,
    "creator" => [
      "@type" => "Person",
      "name" => "Yuchu Hsieh"
    ],
    "mascot" => [
      "@type" => "Cat",
      "name" => "Nori",
      "alternateName" => "小海苔",
      "description" => "Official Kitty Webmaster of ClipArTaiwAnga",
      "image" => "https://taiwanga.com/includes/Nori_meowing.png"
    ]
  ];

  ?>
  <meta charset="UTF-8">
  <title><?= __('page_title') ?></title>
  <meta name="description" content="<?= $desc ?>">
  <meta name="keywords" content="<?= htmlspecialchars(implode(', ', $keywords)) ?>">

  <!-- OG Meta -->
  <meta property="og:title" content="<?= __('page_title') ?>">
  <meta property="og:site_name" content="<?= __('page_title') ?>">
  <meta property="og:description" content="<?= $desc ?>">
  <meta property="og:image" content="https://taiwanga.com/includes/favicon_512.png"> <!-- 可自訂一張代表性的圖 -->
  <meta property="og:url" content="https://taiwanga.com<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="<?= $lang === 'jp' ? 'ja_JP' : ($lang === 'en' ? 'en_US' : 'zh_TW') ?>">
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <!-- <meta name="twitter:site" content="@your_twitter_handle"> 可填你的官方帳號 -->
  <meta name="twitter:title" content="<?= __('page_title') ?>">
  <meta name="twitter:description" content="<?= $desc ?>">
  <meta name="twitter:image" content="https://taiwanga.com/includes/favicon_512.png">
  <meta name="twitter:url" content="https://taiwanga.com<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

  <link rel="canonical" href="https://taiwanga.com/">

  <link rel="manifest" href="/manifest.json">
  <script type="application/ld+json">
    <?= json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>
  <link rel="icon" href="<?= BASE_URL ?>includes/favicon.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($_GET['admin'] ?? '' === 'true'): ?>
    <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>

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
  <?php
  $title_link = BASE_URL;
  if ($_GET['admin'] ?? '' === 'true')
    $title_link .= '?admin=true';
  ?>

  <?php include("includes/header.php"); ?>

  <!-- <div class="page-wrapper"> -->
  <div class='main-container'>
    <!-- left sidebar -->
    <?php include("includes/sidebar.php"); ?>

    <!-- main gallery part of illustrations -->
    <div class="gallery">
      <?php foreach ($photos as $photo): ?>
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

        $webp_path = "webp/" . pathinfo($photo['file_path'], PATHINFO_FILENAME) . ".webp";
        $has_webp = file_exists($webp_path);
        ?>

        <div class="photo">
          <div class="flip-inner">
            <!-- Front: original thumbnail + title + tags -->
            <div class="flip-front">
              <!-- <div class="thumbnail">
                <a href="<?= BASE_URL ?>photo.php?id=<?= $photo['id'] ?>">
                  <img src="<?= $photo['file_path'] ?>" alt="<?= htmlspecialchars($title) ?>">
                </a>
              </div> -->
              <div class="thumbnail">
                <a href="<?= BASE_URL ?>photo.php?id=<?= $photo['id'] ?>">
                  <picture>
                    <?php if ($has_webp): ?>
                      <!-- generate an alt path for <picture> -->
                      <source srcset="<?= BASE_URL ?>webp/<?= pathinfo($photo['file_path'], PATHINFO_FILENAME) ?>.webp"
                        type="image/webp">
                    <?php endif; ?>
                    <img src="<?= BASE_URL . $photo['file_path'] ?>" alt="<?= htmlspecialchars($title) ?>">
                  </picture>
                </a>
              </div>

              <div class="photo-content">
                <h3>
                  <a href="<?= BASE_URL ?>photo.php?id=<?= $photo['id'] ?>">
                    <?= __z($title) ?>
                  </a>
                </h3>

                <p><strong><?= __z('upload_date') ?></strong> <?= $photo['upload_date'] ?></p>

                <?php if (!empty($photo_tags_map[$photo['id']])): ?>
                  <p><strong><?= __z('tags') ?></strong></p>
                  <?php foreach ($photo_tags_map[$photo['id']] as $tag): ?>
                    <?php
                    $query = ['tags[]' => $tag];
                    if ($_GET['admin'] ?? '' === 'true')
                      $query['admin'] = 'true';
                    $tag_url = BASE_URL . http_build_query($query);
                    $label = $tag_translations[$tag][$lang] ?? $tag;
                    ?>
                    <a class="tag" href="<?= $tag_url ?>"><?= __z($label) ?></a>
                  <?php endforeach; ?>

                <?php endif; ?>

                <?php if ($_GET['admin'] ?? '' === 'true'): ?>
                  <p><a href="<?= BASE_URL ?>edit.php?id=<?= $photo['id'] ?>">✏️ <?= __('edit') ?></a></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Back: show description -->
            <div class="flip-back">
              <div class="photo-desc">
                <p><?= nl2br(__z($desc)) ?></p>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- page button -->
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>" class="page_text">&laquo; <?= __('prev_page') ?></a>
    <?php endif; ?>

    <?php
    $range = 2; // how many pages to the left and right
    $ellipsis_shown_left = false;
    $ellipsis_shown_right = false;
    for ($i = 1; $i <= $total_pages; $i++):
      if (
        $i == 1 || $i == $total_pages || // first and last page
        ($i >= $page - $range && $i <= $page + $range) // around current page
      ):
        ?>
        <?php if ($i == $page): ?>
          <span class="current-page"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= build_query_url($i) ?>"><?= $i ?></a>
        <?php endif; ?>
        <?php
      elseif ($i < $page && !$ellipsis_shown_left):
        echo '<span class="ellipsis">...</span>';
        $ellipsis_shown_left = true;
      elseif ($i > $page && !$ellipsis_shown_right):
        echo '<span class="ellipsis">...</span>';
        $ellipsis_shown_right = true;
      endif;
    endfor;
    ?>

    <?php if ($page < $total_pages): ?>
      <a href="<?= build_query_url($page + 1) ?>" class="page_text"><?= __z('next_page') ?> &raquo;</a>
    <?php endif; ?>
  </div>

  <?php include("includes/footer.php"); ?>
  <a href="<?= BASE_URL ?>about.php" class="visually-hidden">About us</a>
  <!-- </div> -->
  <script src="<?= BASE_URL ?>script.js"></script>
</body>

</html>