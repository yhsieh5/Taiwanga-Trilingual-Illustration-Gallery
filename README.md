# Taiwanga.com: A Trilingual PHP-Based Illustration Gallery

**[ClipArTaiwAnga (台灣畫尪仔)](https://taiwanga.com)** is a trilingual illustration gallery system built with PHP and MySQL, designed for scalable cultural archiving and educational accessibility. The system is fully dynamic, modular, and supports advanced filtering via hierarchical tags and date-based queries. It is optimized for educational and cultural contexts, with special support for Chinese Zhuyin (Bopomofo) annotations.

> This project was designed and implemented independently to explore multilingual UI/UX, web architecture, and scalable filtering systems using server-side rendering.

---

## Technical Highlights

- **Modular PHP architecture**  
  - Components (e.g. `sidebar`, `lang`, `zhuyin_init`) are reusable and cleanly separated.
  - Session-based language handling and redirection logic.

- **Multilingual Support (i18n)**  
  - Language: Traditional Chinese, English, Japanese (`zh`, `en`, `jp`)
  - Translations for all UI elements and image metadata.
  - Zhuyin annotations (via `zhuyin_init.php`) rendered conditionally when `lang=zh`.

- **Dynamic Filtering Engine**  
  - Search by keyword with alias mapping.
  - Hierarchical tag system with parent-child structure.
  - Year/month-based date filters with auto-collapsing UI.
  - Combined search logic with keyword + tag + date intersection.

- **Custom Search Logic**  
  - Manual SQL query construction using `LIKE`, `JOIN`, and `IN` for multi-field search.
  - Alias table (`search_aliases.php`) for multilingual keyword normalization.
  - Safe use of `PDO` with parameterized queries.

- **Login System & Access Control**
  - Admin-only routes using `$_SESSION["is_admin"]` flag.
  - Manual access control for pages like `edit.php`, `upload.php`, `delete.php`.
  - Invalid access attempts are redirected via header and checked using GET sanitization.

- **Secure & Robust Web App Practices**
  - Input validation via `isset`, `filter_input`, and type checking.
  - Protected parameter handling using `PDO` prepared statements (SQL injection safe).
  - `_GET` parameters like `admin=true` and `setlang=xx` are validated and rejected if malformed.
  - Admin session gatekeeping: unauthorized users are redirected to `login.php`.

- **Dynamic Web App Behavior (JS)**
  - Responsive sidebar with collapsible hierarchical checkboxes (tags and date).
  - Tag selection syncs both upward and downward in the hierarchy.
  - Filter memory: selected tags and dates persist across page reloads.
  - Toggle Zhuyin mode, flip-card image descriptions, and FAQ dropdowns.
  - PWA support with Service Worker registration.

- **UI / UX Features**
- Fully responsive mobile-first design.
- Sidebar auto-collapses on mobile and toggles with overlay.
- `zhuyin_mode` supports child-friendly annotation for Mandarin.
- Pagination and dynamic search via keyword + tag + date intersection.

- **SEO & Accessibility**
  - Google Search metadata: JSON-LD, Open Graph, sitemap.xml
  - `meta_description`, `lang`, and `<image:image>` sitemap support.
  - `.visually-hidden` class used for accessibility without visual clutter.
  - Cookie Consent Management Platform (CMP) integrated for EEA compliance.

- **WebP Image Optimization**
  - Uploaded images are automatically converted to `.webp` format using PHP’s GD library.
  - WebP files are stored in a separate `webp/` folder to reduce storage duplication.
  - If WebP already exists, conversion is skipped for efficiency.
  - Frontend prioritizes displaying WebP for performance and SEO optimization.

---

## Project Files (Examples)

| File | Description |
|------|-------------|
| `index.php` | Main landing page. Displays images with pagination and top-level UI. |
| `photo.php` | Individual image viewer with multilingual metadata and tag navigation. |
| `sidebar.php` | Generates date and tag filters dynamically from the database. |
| `lang.php` | Manages session-based language switching and redirects. |
| `zhuyin_init.php` | Applies per-character Zhuyin annotation for Chinese display. |
| `script.js` | Implements dynamic UI logic including collapsible sidebar, hierarchical checkbox syncing, FAQ toggling, and PWA registration for a responsive illustration archive. |
| `style.css` | Responsive CSS layout and styling for the multilingual illustration gallery, including Zhuyin annotation and mobile support. |

> *Note: This is a partial release. Upload/admin logic and database config are excluded for privacy & security reasons. Some pages and components are not shown here.*

---

## Filtering Workflow

This project implements a **multi-dimensional search system** that combines free-text search, hierarchical tags, and date filters.

### 1. Keywords
- User enters keywords (any language) → normalized via alias mapping → searched in titles, descriptions, and file paths.

### 2. Tags
- Tags are stored hierarchically (`tags` table with `parent_id`).
- Query uses recursive rendering to build a nested checkbox UI.
- Filtering via subquery `photo_tags` join.

### 3. Date
- Upload date parsed into `YYYY-MM` for filtering.
- Filters applied via `DATE_FORMAT` in SQL.

---

## Security & Access Control Summary

| Feature | Description |
|--------|-------------|
| Login | Admin login via session; `login.php` required to access editing features |
| Access Control | `$_SESSION["is_admin"]` checked on protected routes |
| GET Parameter Validation | `admin=true`, `setlang=xx`, `zhuyin_mode` all sanitized |
| SQL Injection Protection | All SQL uses `PDO` with parameter binding |
| Misuse Prevention | Direct URL tampering is blocked or redirected |

---

## Tech Stack
- PHP (session-based, modular)
- MySQL with PDO
- HTML/CSS (Fully responsive layout)
- JavaScript (Toggle, sidebar tag checkbox tree, flip card logic)
- JSON/alias-based keyword & zhuyin mapping
- UTF-8 multilingual content rendering
- .htaccess (redirect rules, sitemap, geo blocking)

---

## Sample UI Screenshots
<table>
  <tr>
    <td>
      <img width="300" alt="image" src="https://github.com/user-attachments/assets/136cba3a-18f3-4d41-a89c-a1289b990760" />
    </td>
    <td>
      <img width="300" alt="image" src="https://github.com/user-attachments/assets/a0ee7103-4586-497d-bfaa-c39e695fd5d7" />
    </td>
  </tr>
  <tr>
    <td align="center">
      <em>Search + tag filters result of tag page (same as index page layout).</em>
    </td>
    <td align="center">
      <em>Zhuyin annotation mode of one photo page.</em>
    </td>
  </tr>
</table>
<table>
  <tr>
    <td>
      <img width="300" alt="image" src="https://github.com/user-attachments/assets/688f72c3-c88e-4a44-8b8b-b29752b0a65c" />
    </td>
    <td>
      <img width="300" alt="image" src="https://github.com/user-attachments/assets/7199f4f3-eb1a-46f7-8632-d63a208af606" />
    </td>
  </tr>
  <tr>
    <td align="center">
      <em>FAQ page in Japanese with shrunk header bar & sidebar.</em>
    </td>
    <td align="center">
      <em>About page in Chinese.</em>
    </td>
  </tr>
</table>


---

## Acknowledgments

Inspired by [いらすとや / Irasutoya](irasutoya.com).
Thanks to the Taiwanese open-source and cultural preservation community.

---

## License

This project is dual-licensed:

- **Website Code (PHP, HTML, JS, CSS)**: [MIT License](LICENSE)
- **Illustration Assets**: © Yu-Chu Hsieh.  
  These illustrations are free to use for personal, educational, and limited commercial purposes under [custom usage terms](https://taiwanga.com/about.php) outlined on the website.

Please **do not** use the illustrations in any defamatory, offensive, misleading, or unauthorized commercial ways. For high-resolution use or extended commercial use (31+ illustrations), [contact me](mailto:clipartaiwanga@gmail.com) for a licensing or support plan.
