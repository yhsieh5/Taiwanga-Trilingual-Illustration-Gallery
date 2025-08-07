<?php
/**
 * lang.php
 *
 * Manages multilingual support for the website, including session-based language preference,
 * Zhuyin (Bopomofo) mode toggle, and dynamic translation retrieval.
 *
 * Features:
 * - Detects and stores the current language preference in the session (`zh`, `en`, or `jp`).
 * - Automatically disables Zhuyin mode for non-Chinese languages.
 * - Defines `lang_attr` for proper <html lang=""> rendering.
 * - Allows URL-based language switching using `?setlang=...`.
 * - Provides translation lookup via the `__()` function.
 * - Constructs language switch links with preserved query parameters.
 *
 * To be included on all main pages to ensure consistent language behavior.
 * This file does not produce output but controls language logic and flow.
 *
 * Used together with:
 * - zhuyin_init.php: Enables annotated Bopomofo display when appropriate.
 * - lang.php’s `$translations` array: Stores the multilingual content.
 * - Header/footer/sidebar and dynamic content generators.
 *
 * @author Yuchu Hsieh
 */
?>

<?php
session_start();

$lang = $_SESSION['lang'] ?? 'zh'; // default in Chinese
// Always check if zhuyin should be disabled for non-Chinese languages
// This should run on EVERY page load, not just when setlang is set
if ($lang !== 'zh') {
  $_SESSION['zhuyin_mode'] = false;
}

$zhuyin_enabled = $_SESSION['zhuyin_mode'] ?? false;

// standardize HTML language attribute
$lang_map = [
  'zh' => 'zh-TW',
  'en' => 'en-US',
  'jp' => 'ja-JP'
];
$lang_attr = $lang_map[$lang] ?? 'zh-TW';

// language content
$translations = [
  'zh' => [
    'home' => '首頁',
    'about' => '關於',
    'terms' => '使用說明',
    'faq' => '常見問題',
    'dark_mode' => '🌙深色模式',

    'back_home' => '回首頁',
    'page_title' => '台灣畫尪仔',
    'title' => '台灣畫尪仔素材庫',

    'about_text' => "哈囉，這是一個由我個人建立與維護的小型插畫圖庫網站，主要用來收藏並分享我創作的插圖。網站的構想受到日本插畫家 Mifune老師所架設的 %s啟發，並在取得老師的同意後參考其使用規範設計而成。不過，本網站所收錄的圖片皆由我本人繪製上傳，與 Irasutoya網站本身並無直接關聯。\n
    「ang-á（尪仔）」來自台語，泛指所有的人偶，「畫尪仔」則引申為畫圖的意思，剛好日語的「ga（画）」是畫的音讀，加上英文的Taiwan（台灣）借我拿來三重諧音義😆。插畫靈感來自生活、網路、和大家分享的台灣的一切，希望藉由這裡記錄下我們熟悉的台灣。\n
    本網站提供的插畫可免費用於個人、商業、法人或非營利用途，但必須遵守網站所列之%s及%s。\n
    目前收錄的插畫主題包含日常生活、節慶活動、人物角色與其他創作主題。每張圖皆附有標題、描述與多語標籤，您可透過標籤進行分類搜尋與篩選。本網站支援繁體中文、英文與日文介面，您可透過右上角語言切換選單調整瀏覽語言。標籤系統亦支援同義詞查找，例如搜尋「cat」時會自動顯示「貓」標籤的結果。\n
    在此由衷感謝 華盛頓大學的教授們和 Mifune老師的啟發與支持。沒有老師的教學和啟發就不會有這個網站。如果您喜歡這些插畫，歡迎分享給朋友，也歡迎透過%s、%s、或%s與我交流建議與感想！",

    'email' => 'email',
    'instagram' => 'IG',
    'threads' => 'Threads',

    'terms_title' => '使用說明',
    'usage_policy' => '使用條款/免責聲明',
    'terms_intro' => "本網站發佈的素材可供個人、法人、商業或非商業使用者在條款和條件的範圍內免費使用於網站、影片、簡報、廣告、出版品等商業與非商業用途。使用前請參閱此頁面與%s以了解詳情。\n
    網站的構想受到日本插畫家 Mifune老師所架設的 %s啟發，並在取得老師的同意後參考其使用規範設計而成。不過，本網站所收錄的圖片皆由我本人繪製上傳，與 Irasutoya網站本身並無直接關聯。",

    'allowed_usage' => '允許使用',
    'allowed1' => "每個專案最多可使用 30 張圖片。（重複元素併計為一個項目）",
    'allowed2' => '可自由縮放、裁切或轉為灰階使用。',

    'restrictions' => '禁止事項',
    'restriction1' => '不得用於誹謗、仇恨、冒犯性、歧視性、違法等不當用途，損害素材形象。',
    'restriction2' => '不得作為產品的主要視覺主體使用。',
    'restriction3' => '不得大幅改造或誤導為自行創作。',
    'restriction4' => '不得單獨轉售圖片（如貼圖、周邊、T-shirt）。',
    'restriction5' => '其他本人認為不適當，或明顯違反本網站宗旨的用途（例如涉及宗教、政治爭議、詐騙、成人內容等）。',

    'cost_policy' => '商業委託/付費支援方案',
    'cost_intro' => '以下情況將提供有償服務。請透過%s與我聯繫，我會視情況評估並提供委託、授權或支援方案，謝謝您的支持！',
    'cost1' => '使用 31 個或更多元素的商業設計（重複元素併計為一個項目）。',
    'cost2' => '網站上展示的圖片解析度大部分約為寬／高 600px（約15公分），適合網頁與簡報使用。若需高解析度版本（如用於印刷、出版、產品包裝等），請透過%s與我聯繫。',

    'copyright' => '著作權聲明',
    'copy_intro' => "您可以免費使用本網站的插畫，但此不等同本人放棄著作權。所有插畫之著作權仍歸本人所有。\n
    只要符合使用條款，您可以自由縮放、裁切或灰階修改這些插畫。但是，無論插畫是否被修改，或修改程度如何，著作權均不會轉移或更動。",

    'privacy' => '隱私權政策',
    'privacy_intro' => "本網站使用Google AdSense提供的第三方廣告服務（目前審查中）。Google可能會使用cookie或類似技術，根據使用者的瀏覽紀錄顯示個人化廣告。\n
    本網站亦使用Google提供的 Cookie 同意管理平台 (CMP)，向歐洲經濟區（EEA）、英國與瑞士的訪客顯示同意訊息，以符合法規要求。使用者可透過該平台選擇是否同意使用廣告Cookie，或管理其偏好設定。使用者可前往Google的%s或%s了解更多關於廣告個人化與資料使用的資訊。\n
    本網站不會直接收集、儲存或分享任何使用者的個人資料，也不使用其他追蹤或分析工具。若您主動聯絡網站管理者，您的電子郵件地址僅用於回覆用途，事後不會予以保留。",

    'google_ad_setting' => '廣告設定頁面',
    'google_privacy_policy' => '隱私權政策',

    'others' => '其他',
    'others_intro' => '本人在其他網站或平台上發布的作品，除另有說明者外，均不包含在本網站的免費使用範圍中。
                      用本網站所提供之資訊與內容所造成之任何結果，本人概不負責。本站保留隨時變更條款內容、網站設計、素材上架或下架之權利。
                      所有條款和內容如有更改，恕不另行通知。',

    'cat_disclaimer' => '貓咪亂入聲明',
    'cat_disclaimer_intro' => '本網站的開發與維護過程中，有時貓咪站長小海苔會跳上鍵盤，可能導致不明亂碼輸入。若您在網站中發現莫名其妙的字串，請放心，這只是海苔站長想參與開發的一點小貢獻🐱感謝您的包容與理解！',

    'faq_q1' => '可以用於商業用途嗎？',
    'faq_a1' => '可以，只要符合使用條款中所列的限制。若使用圖片數量超過 30 張或需要高解析度版本，可參考付費支援方案。',

    'faq_q2' => '可以修改圖片嗎？',
    'faq_a2' => '可以自由裁切、縮放或轉為灰階使用，但請勿大幅改造或誤導為自行創作。',

    'faq_q3' => '可以將圖片用於影片、網站或出版品嗎？',
    'faq_a3' => '可以，這些屬於允許用途。但請避免將圖片作為主要視覺主體，並遵守其他使用限制。',

    'faq_q4' => '有提供高解析度圖片嗎？',
    'faq_a4' => '目前網站圖片約為600px（約15公分），如需更高解析度，可透過聯絡方式與我洽詢付費支援方案。',

    'faq_q5' => '可以將圖片用於商品設計或販售嗎？',
    'faq_a5' => '不行，禁止將圖片單獨用於販售用途（如貼圖、周邊、T-shirt 等）。如有特殊需求請事先聯絡。',

    'faq_q6' => '需要標示出處嗎？',
    'faq_a6' => '無需標註出處，但若願意註明「來自台灣畫尪仔」將不勝感激。',

    'faq_q7' => '網站使用的字體？',
    'faq_a7' => 'JustFonts Huninn 台灣人為繁體字與注音設計的粉圓體！注音模式則是BopomofoRuby！',

    'old_to_new' => '舊到新',
    'new_to_old' => '新到舊',
    'upload' => '上傳新圖片',
    'search' => '確認並搜尋',
    'search_placeholder' => '搜尋名稱或描述...',
    'searching_for' => '搜尋中：',
    'filter_by_date' => '以日期篩選',
    'filter_by_tag' => '以標籤篩選',

    'edit' => '編輯',
    'tags' => '標籤：',
    'upload_date' => '上傳日期：',
    'category' => '分類：',
    'language' => '語言：',
    'page_of' => '第%s頁，共%s頁',
    'prev_page' => '上一頁',
    'next_page' => '下一頁',
    'back_to_search' => '回到搜尋結果',
    'download_photo' => '下載這張圖片',
    'prev_photo' => '上一張',
    'next_photo' => '下一張',
    'related_illustrations' => '類似插圖',
    'all_rights_reserved' => 'All Rights Reserved'
  ],
  'en' => [
    'home' => 'Home',
    'about' => 'About',
    'terms' => 'Terms of Use',
    'faq' => 'FAQ',
    'back_home' => 'Back to Homepage',

    'page_title' => 'Taiwan-ga',
    'title' => 'ClipArTaiwAnga',

    'about_text' => "Hello! This is a small Taiwanese illustration archive website that I created and maintain, primarily for collecting and sharing my own illustrations. The idea was inspired by the Japanese illustrator Mifune sensei’s website, %s, and I built this site with their permission, referencing their usage policies. However, all illustrations here are drawn and uploaded by me, and this site is not directly affiliated with Irasutoya.

    All illustrations on this site are free to use for personal, commercial, corporate, or non-profit purposes, provided they follow the %s and %s listed on the website.
    
    \"Ang-á（尪仔）\" comes from Taiwanese and refers to various kinds of figures or characters. The phrase \"drawing ang-á（uē-ang-á / 畫尪仔）\" extends this meaning to refer to drawing manga or illustrations. The sound \"ga\" also could mean \"painting / to draw（画）\" in Japanese, and when combined with “Taiwan” in English, I created a triple pun—Taiwanga—as the name of this site 😆. The inspiration for the illustrations comes from daily life, the internet, and everything people share about Taiwan. I hope to record the Taiwan we all know and love, right here.

    The collection includes themes such as daily life, festivals, characters, and various other topics. Each image includes a title, description, and multilingual tags, which can be searched and filtered by category. The site supports Traditional Chinese, English, and Japanese interfaces, and you can switch the language using the selector at the top right. The tagging system also supports alias matching — for example, searching “cat” will also show results for the “貓” tag.

    Special thanks to my Professors from University of Washington and Mifune sensei again for the inspiration and support. I couldn't make this site without your teaching and inspiration. If you enjoy these illustrations, feel free to share them with your friends, or contact me through %s, %s, or %s to share your thoughts and feedback!",

    'email' => 'email',
    'instagram' => 'Instagram',
    'threads' => 'Threads',

    'terms_title' => 'Usage Terms',
    'usage_policy' => 'Usage Policy / Disclaimer',
    'terms_intro' => 'The illustrations on this website may be used for free by individuals, businesses, or organizations for both commercial and non-commercial purposes within the scope of the terms and conditions. This includes websites, videos, presentations, advertisements, and publications. Please read this page and %s for details before use.
    
    The idea of this website was inspired by the Japanese illustrator Mifune’s website %s, and it was created with their permission and by referencing their usage policies. However, all illustrations on this site are created and uploaded by me personally and are not directly related to Irasutoya.',
    'allowed_usage' => 'Permitted Usage',
    'allowed1' => 'You may use up to 30 illustrations per project. (Repeated elements count as one item.)',
    'allowed2' => 'You may freely resize, crop, or convert the illustrations to grayscale.',

    'restrictions' => 'Prohibited Uses',
    'restriction1' => 'Do not use the illustrations for defamatory, hateful, offensive, discriminatory, or illegal purposes that damage the image of the content.',
    'restriction2' => 'Do not use the illustrations as the main visual element of a product.',
    'restriction3' => 'Do not heavily modify or misrepresent the illustrations as your own creation.',
    'restriction4' => 'Do not resell the illustrations individually (e.g., as stickers, merchandise, T-shirts).',
    'restriction5' => 'Other situations deemed inappropriate by the creator.',

    'cost_policy' => 'Commercial Commission / Paid Support Plans',
    'cost_intro' => 'Paid services are available in the following situations. Please contact me via %s to discuss possible commissions, licenses, or support plans. Thank you for your support!',
    'cost1' => 'Commercial designs using 31 or more elements (repeated items count as one).',
    'cost2' => 'The images displayed on this site are mostly around 600px in width/height (approx. 15cm). For high-resolution versions, please contact me via %s.',

    'copyright' => 'Copyright Notice',
    'copy_intro' => 'You may use the illustrations on this website for free, but this does not mean the copyright is waived. All copyrights remain with me.
    As long as the terms are followed, you may edit or modify the illustrations freely. However, copyright does not transfer regardless of the degree of modification.',

    'privacy' => 'Privacy Policy',
    'privacy_intro' => 'This website uses third-party advertising services provided by Google AdSense (under approving). Google may use cookies or similar technologies to display personalized ads based on users\' browsing history.

    The website also uses Google’s Cookie Consent Management Platform (CMP) to display consent messages to users in the European Economic Area (EEA), the United Kingdom, and Switzerland, in compliance with relevant regulations. Users can choose whether to allow ad-related cookies or manage their preferences through the provided interface. Users may visit Google’s %s or %s pages for more information and control over personalized advertising.

    This website does not directly collect, store, or share any personal user data, nor does it use any tracking or analytics tools. If you contact the site owner, your email address will be used solely for responding and will not be retained afterward.',

    'google_ad_setting' => 'Ads Settings',
    'google_privacy_policy' => 'Privacy Policy',

    'others' => 'Others',
    'others_intro' => 'Works I publish on other websites are not free to use.
    I am not responsible for any consequences arising from the use of content on this website.
    I reserve the right to modify or remove content at any time.
    Terms and conditions are subject to change without notice. Please be aware in advance.',

    'cat_disclaimer' => 'Cat Interference Disclaimer',
    'cat_disclaimer_intro' => 'During the development and maintenance of this website, the Kitty Webmaster Nori occasionally walks across the keyboard. As a result, you may encounter random characters or unintended inputs. If you notice mysterious text on the site, rest assured—it\'s just Nori trying to help with the coding 😸. Thanks for your understanding!',

    'faq_q1' => 'Can I use the images for commercial purposes?',
    'faq_a1' => 'Yes, as long as you follow the usage terms. If you need more than 30 images or high-resolution files, please see the paid support options.',

    'faq_q2' => 'Can I modify the images?',
    'faq_a2' => 'Yes, you may crop, scale, or convert to grayscale. However, do not heavily alter or falsely claim them as your own creations.',

    'faq_q3' => 'Can I use the images in videos, websites, or publications?',
    'faq_a3' => 'Yes, these are allowed. Just avoid using the images as the main visual focus and follow other restrictions.',

    'faq_q4' => 'Are high-resolution versions available?',
    'faq_a4' => 'Images on the site are around 600px (approx. 15 cm). For higher resolution files, please contact me for a support plan.',

    'faq_q5' => 'Can I use the images for merchandise or sales?',
    'faq_a5' => 'No. You may not sell the images as-is (e.g., stickers, goods, T-shirts). Please contact me for special requests.',

    'faq_q6' => 'Do I need to give credit?',
    'faq_a6' => 'Credit is not required, but mentioning “TaiwAnga” would be appreciated.',

    'faq_q7' => 'What\'s the font of the website?',
    'faq_a7' => 'JustFonts Huninn 粉圓體 designed by Taiwanese for our Traditional Mandarin Chinese and Bo-Po-Mo-Fo Spelling System!',

    'old_to_new' => 'Oldest to Newest',
    'new_to_old' => 'Newest to Oldest',
    'upload' => 'Upload New Image',
    'search' => 'Apply & Search',
    'search_placeholder' => 'Search by Title or Description...',
    'searching_for' => 'Searching for: ',
    'filter_by_date' => 'Filter by Date',
    'filter_by_tag' => 'Filter by Tag',

    'edit' => 'Edit',
    'tags' => 'Tags:',
    'upload_date' => 'Upload Date:',
    'category' => 'Category:',
    'language' => 'Language: ',
    'page_of' => '%s page of %s',
    'prev_page' => 'prev',
    'next_page' => 'next',
    'back_to_search' => 'Back to Search',
    'download_photo' => 'Download This Photo',
    'related_illustrations' => 'Related Illustrations',
    'prev_photo' => 'previous one',
    'next_photo' => 'next one',
    'all_rights_reserved' => 'All Rights Reserved'
  ],
  'jp' => [
    'home' => 'ホームページ',
    'about' => 'サイトについて',
    'terms' => 'ご利用について',
    'faq' => 'よくある質問',
    'dark_mode' => '🌙ダークモード',

    'back_home' => 'ホームページに戻る',
    'page_title' => '台湾アンアート',
    'title' => '台湾アンアート素材集',

    'about_text' => 'こんにちは！このサイトは、私が個人的に制作・管理している小さなイラスト素材サイトです。自分で描いたイラストや写真をまとめて公開・共有する目的で作成しました。この構想は、日本のイラストレーター・みふね先生のサイト%sにインスピレーションを受けており、ご本人の許可を得たうえで、使用条件などを参考にして構築しています。ただし、掲載されているイラストはすべて私自身の作品であり、Irasutoya 公式サイトとは直接的な関係はありません。

    当サイトのイラストは、%sや%sに記載された条件のもとであれば、個人利用・商用利用・法人利用・非営利利用を問わず、無料でご利用いただけます。

    「尪仔ang-á（アンア）」は台湾語で色んな人偶の意味であり、「畫尪仔uē-ang-á（ウェーアンア）」は本来の意味から派生して、マンガやイラストを描く意味になります。日本語の「ga（画）」がちょうど「畫（絵．描く）」の音読みで、英語のタイワン（Taiwan）に加えて、三つの言葉を語呂合わせにしてサイトの名前を付けました😆。イラストのインスピレーションは、日常生活やネット、そして皆さんがシェアしてくれる台湾のすべてから来ています。私は、みんなが知っている台湾の姿を、ここに記録していきたいと思います。

    現在は、日常生活、季節のイベント、キャラクター、その他のテーマを扱ったイラストを掲載しています。各イラストにはタイトル、説明、多言語対応のタグが付けられており、カテゴリやキーワードでの検索・絞り込みが可能です。サイトのインターフェースは繁体字中国語・英語・日本語に対応しており、右上のメニューから切り替えることができます。タグ検索は別名や同義語にも対応しており、例えば「cat」と検索すると「貓」のタグも表示されます。

    ここでもう一度ワシントン大学の先生達とみふね先生のインスピレーションとサポートに心から感謝いたします。先生達に教わらなかったら、このサイトがありません。イラストを気に入っていただけたら、ぜひお友達にもシェアしてください。また、ご意見・ご感想などがあれば、%s、%s、または%sを通じてご連絡いただけると嬉しいです。',

    'instagram' => 'インスタ',
    'email' => 'Ｅメール',
    'threads' => 'スレッズ',

    'terms_title' => '利用規約',
    'usage_policy' => '利用条件 / 免責事項',
    'terms_intro' => 'このウェブサイトで公開されているイラストは、個人、法人、商業、または非営利目的で、定められた条件の範囲内で無料で使用できます。使用例としては、ウェブサイト、動画、プレゼンテーション、広告、出版物などが含まれます。ご使用の前に、本ページおよび%sをご確認ください。
    
    本サイトの構想は、日本のイラストレーターみふね先生のサイト%sに触発されたもので、先生の同意を得たうえで、使用規定を参考にして作成されました。ただし、本サイトのイラストはすべて私個人が制作・投稿したものであり、Irasutoya サイトとは直接的な関係はありません。',
    'allowed_usage' => '許可されている使用',
    'allowed1' => '1つのプロジェクトにつき、最大30点までのイラストを使用できます（同じ素材は1点と数えます）。',
    'allowed2' => '拡大・縮小・トリミング・白黒変換などは自由に行えます。',

    'restrictions' => '禁止事項',
    'restriction1' => '誹謗中傷、ヘイト、差別、違法行為、その他素材のイメージを損なう用途には使用できません。',
    'restriction2' => '商品デザインの主なビジュアル要素として使用することはできません。',
    'restriction3' => '大幅な改変、または自作として誤認させるような使用は禁止します。',
    'restriction4' => 'スタンプ、グッズ、Tシャツなど、素材単体での再販売は禁止します。',
    'restriction5' => 'その他、作者が不適切と判断する使用。',

    'cost_policy' => '商用依頼・有料サポートについて',
    'cost_intro' => '以下のケースでは、有償での対応を行います。%sよりご連絡ください。内容を確認のうえ、委託やライセンス支援などをご提案させていただきます。',
    'cost1' => '31点以上の素材を使用した商業デザイン（同じ素材は1点と数えます）。',
    'cost2' => 'サイト上のイラストは基本的に 600px（約15cm）程度のサイズで表示されています。高解像度画像をご希望の場合は、%sよりお問い合わせください。',

    'copyright' => '著作権について',
    'copy_intro' => '当サイトのイラストは無料で使用可能ですが、著作権は放棄されていません。すべての著作権は私本人に帰属します。
    利用規定の範囲内であれば、自由に編集・加工していただけますが、編集の有無や加工の程度に関わらず、著作権の移転はありません。',

    'privacy' => 'プライバシーポリシー',
    'privacy_intro' => "本ウェブサイトでは、Google AdSense による第三者の広告配信サービスを利用しています（今は審査中）。Google は、ユーザーの閲覧履歴に基づいてパーソナライズされた広告を表示するために、Cookie や類似の技術を使用する場合があります。\n
    また、本ウェブサイトは、Google のCookie 同意管理プラットフォーム（CMP）を利用して、欧州経済領域（EEA）・イギリス・スイスの訪問者に対し、広告Cookieの使用に関する同意メッセージを表示しています。ユーザーは、このメッセージを通じてCookie の使用に同意するか、または設定を管理することができます。\n
    パーソナライズ広告やデータ使用に関する詳細は、Googleの%sや%sをご覧ください。\n
    本ウェブサイトでは、ユーザーの個人情報を直接収集・保存・共有することはありません。また、トラッキングやアクセス解析ツールも使用しておりません。サイト管理者にご連絡いただいた場合、メールアドレスは返信のみに使用され、保存は行いません。",

    'google_ad_setting' => '広告設定ページ',
    'google_privacy_policy' => 'プライバシーポリシー',

    'others' => 'その他',
    'others_intro' => '他のサイトで公開している作品については、無料使用の対象外となります。
    当サイトの内容を使用したことによって生じた結果については、一切責任を負いかねます。
    本サイトの内容は予告なく変更・削除される場合があります。
    利用条件は変更されることがありますので、事前にご確認ください。',

    'cat_disclaimer' => '猫による乱入について',
    'cat_disclaimer_intro' => '本ウェブサイトの開発・更新作業中、サイト管理猫のノリちゃんが時折キーボードの上を歩くことがあります。そのため、サイト内に意味不明な文字列や入力ミスが発生する可能性があります。もし不思議なテキストを見かけた場合は、ノリちゃんが開発に協力しようとした結果かもしれません😺。ご理解いただきありがとうございます！',

    'faq_q1' => '商用利用は可能ですか？',
    'faq_a1' => 'はい、利用規約を守っていただければ可能です。30点以上の使用や高解像度画像が必要な場合は、有償プランをご検討ください。',

    'faq_q2' => '画像を加工してもいいですか？',
    'faq_a2' => 'はい、トリミングやリサイズ、グレースケール化は自由です。ただし、大きな改変や自作と誤認させる使用はご遠慮ください。',

    'faq_q3' => '動画やウェブサイト、出版物に使えますか？',
    'faq_a3' => 'はい、問題ありません。ただし、画像をメインのビジュアルとして使用することは避けてください。',

    'faq_q4' => '高解像度版はありますか？',
    'faq_a4' => 'サイト上の画像はおおよそ 600px（約15cm）です。高解像度が必要な場合はご連絡ください。',

    'faq_q5' => 'グッズや販売用に使えますか？',
    'faq_a5' => 'いいえ。スタンプやTシャツなど画像単体での販売は禁止です。必要な場合は事前にご相談ください。',

    'faq_q6' => 'クレジットの表記は必要ですか？',
    'faq_a6' => '必須ではありませんが、「台湾アンアート」と表記いただけると嬉しいです。',

    'faq_q7' => 'サイトのフォントはなんですか？',
    'faq_a7' => '台湾人が作った JustFonts Huninn 粉圓體です！繫体字のために作ったので、日本語を使う皆さん、すみません。',

    'old_to_new' => '古い順',
    'new_to_old' => '新着順',
    'upload' => 'アップロード',
    'search' => '確認＆検索',
    'search_placeholder' => 'タイトルや内容で検索...',
    'searching_for' => 'イラストを検索：',
    'filter_by_date' => '日付で検索',
    'filter_by_tag' => 'カテゴリーで検索',

    'edit' => '編集',
    'tags' => 'カテゴリー：',
    'upload_date' => '公開日：',
    'category' => 'カテゴリー：',
    'language' => '言語：',
    'page_of' => '第%s，共%s頁',
    'prev_page' => '前のページへ',
    'next_page' => '次のページへ',
    'prev_photo' => '前のイラストへ',
    'next_photo' => '次のイラストへ',
    'related_illustrations' => '関連イラスト',
    'back_to_search' => '検索結果に戻る',
    'download_photo' => '画像ダウンロード',
    'all_rights_reserved' => 'All Rights Reserved'
  ]
];

// Handle language switching
if (isset($_GET['setlang'])) {
  $lang = $_GET['setlang'];
  $_SESSION['lang'] = $lang;

  // This is now redundant since we check above, but keeping for clarity
  $zhuyin_enabled = $lang === 'zh';
  $_SESSION['zhuyin_mode'] = $zhuyin_enabled;

  // Redirect logic...
  $query = $_GET;
  unset($query['setlang']);
  $currentPage = basename($_SERVER['PHP_SELF']);
  if (basename($_SERVER['PHP_SELF']) === 'index.php') {
    $currentPage = '';
  }
  $redirect_url = $currentPage;

  if (!empty($query)) {
    $redirect_url .= '?' . http_build_query($query);
  }
  header("Location: $redirect_url");
  exit;
}


function __($key)
{
  global $translations, $lang;
  return $translations[$lang][$key] ?? $key;
}

function langSwitchLink($targetLang)
{
  $query = $_GET;
  // remove unneeded zhuyin_mode parameter
  unset($query['zhuyin_mode']);
  $query['setlang'] = $targetLang;
  $query['zhuyin'] = ($targetLang === 'zh' && ($_SESSION['zhuyin_mode'] ?? false)) ? 'on' : 'off';

  $currentPage = basename($_SERVER['PHP_SELF']);
  if (basename($_SERVER['PHP_SELF']) === 'index.php') {
    $currentPage = '';
  }
  $redirect_url = $currentPage;

  if (!empty($query)) {
    $redirect_url .= '?' . http_build_query($query);
  }

  return $redirect_url . '?' . http_build_query($query);
}
