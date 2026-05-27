=== B2B Product Catalog – No E-Commerce, Global RFQ & Bulk Quote ===
Contributors: naibabiji
Tags: b2b, product catalog, rfq, bulk inquiry, no e-commerce
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 5.1.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight B2B product catalog without e-commerce. Showcase products, collect global RFQs and bulk quotes with multilingual support.

== Description ==

Ditch the shopping cart. B2B Product Catalog is built for manufacturers, exporters, and wholesalers who need a clean product catalog with global RFQ and bulk quote collection — no e-commerce complexity, no payment gateways, no bloat. Multilingual-ready, lightweight, and focused on one thing: turning product browsers into qualified leads.

= ✨ Key Features =

* **Bulk RFQ Inquiry Cart** — Multi-spec product selection with persistent inquiry cart. Buyers pick SKU variants with quantities and submit a single combined quote request — no account required
* **Three Inquiry Modes** — Each product supports None / Standard / Bulk inquiry type. Choose the right lead capture strategy per product: simple contact, external link, or full spec-based RFQ
* **AI-Powered Lead Capture** — Built-in AI chatbot (SPEC v2 protocol) for automated customer engagement and lead qualification, with seamless fallback to inquiry forms
* **Visual Shortcode Builder** — Create product grids and category displays with real-time admin preview. No coding needed
* **Multilingual Support** — Ready for international trade with translation files and locale-aware formatting
* **SEO Content Management** — Customize top and bottom content for archive, category, and tag pages independently
* **Widget & Sidebar Support** — Add product showcases anywhere with smart CSS loading that won't affect page speed
* **Enterprise Architecture** — Model-driven design, version-controlled database migrations, and clean code for custom integrations
* **Security First** — Strict nonce verification, rate limiting, and input sanitization across all AJAX operations

= ✅ Why Choose B2B Product Catalog? =

* **🚫 Zero E-Commerce Overhead** — No shopping cart, no checkout, no payment processing. Just products and inquiry forms — exactly what B2B businesses need
* **🌐 Global RFQ Ready** — International-friendly quote forms with multilingual support. Capture inquiries from buyers worldwide, no matter the language
* **⚡ Lightweight & Fast** — Purpose-built codebase without e-commerce bloat. Optimized for Core Web Vitals, won't slow down your site
* **🔍 SEO Optimized** — JSON-LD structured data, breadcrumb navigation, and clean URLs for better search rankings out of the box
* **📱 Fully Responsive** — Seamless display on all devices from desktop to mobile, with adaptive product grid layouts
* **🛠️ Easy Setup & Highly Customizable** — Intuitive admin with visual shortcode generator gets you online in minutes. Rich hooks and template overrides for unlimited flexibility

**🚀 Core Functions**

### 📦 Product Management
- **Complete Product Profiles**: Title, detailed description, featured images, and multi-image galleries
- **Organized Categorization**: Hierarchical categories and flexible tagging system
- **Inquiry Management**: Customizable inquiry buttons with AI-powered customer service integration
- **SEO Optimization**: Built-in meta fields for better search engine visibility

### 🎨 Frontend Display
- **Beautiful Archive Pages**: Grid layouts with responsive design
- **Detailed Product Pages**: Comprehensive product information with image galleries
- **Category & Tag Pages**: Organized browsing experience
- **Powerful Shortcodes**: `[naibabiji_b2b_products]` and `[naibabiji_b2b_product_categories]` for flexible display
- **Sidebar Widgets**: Add product showcases to any sidebar with automatic CSS loading

### ⚙️ Admin Dashboard
- **Intuitive Interface**: Clean, organized settings with tab-based navigation
- **Visual Shortcode Generator**: Create custom product displays with real-time preview
- **SEO Content Management**: Customize top and bottom content for archive, category, and tag pages
- **Customizable Styles**: Button colors, layout options, and display controls

### 🔧 Developer Friendly
- **Rich Hooks & Filters**: Extensive customization options
- **Template Override Support**: Easy integration with any WordPress theme
- **AJAX Interfaces**: Modern, interactive functionality
- **Clean Code Structure**: Well-documented, maintainable codebase
- **Enterprise Architecture**: Model-driven design for scalability

**🎯 Who This Plugin Is For**

B2B Product Catalog is perfect for:
- **Manufacturers** — Showcase production lines with multi-specs selection and collect RFQs from global buyers
- **Exporters & Trading Companies** — Display products in multiple languages, capture inquiries from international markets
- **Distributors & Wholesalers** — Present product ranges to retailers and partners without exposing pricing logic
- **Industrial Suppliers** — List hardware, components, or standard parts with model codes and quantity selection tables
- **Service Providers** — Highlight service offerings with detailed profiles and custom inquiry forms
- **Corporate Websites** — Create professional product galleries without e-commerce overhead, payment gateways, or cart abandonment
- **Developers** — Extend functionality with rich hooks, template overrides, and clean model-driven architecture

**💡 Common Use Cases**

1. **Replace WooCommerce Catalogs** — Already using WooCommerce just for product display? Switch to a lightweight catalog without cart/checkout bloat
2. **Global RFQ & Lead Generation** — Capture structured quote requests from international buyers with multilingual forms and product specs
3. **Multi-Specs Product Catalogs** — Display products with variants (sizes, models, grades) in an interactive selection table with bulk inquiry cart
4. **Trade Show & Virtual Exhibits** — Launch product showcases online for virtual trade shows and international exhibitions
5. **Partner & Dealer Portals** — Share product information with authorized partners and distributors via password-protected pages
6. **Sales Team Enablement** — Give sales teams an always-up-to-date product catalog with built-in inquiry tracking and lead management

== Installation ==

**🚀 Quick Start Guide**

1. **Upload & Activate**: Install the plugin through WordPress admin or upload files to `/wp-content/plugins/naibabiji-b2b-product-showcase/`
2. **Configure Settings**: Go to `Settings -> Product Showcase` to customize your display options
3. **Add Products**: Create new products using the custom "Products" post type
4. **Display Products**: Use shortcodes or widgets to showcase products on your site
5. **Optimize SEO**: Add custom content to archive, category, and tag pages for better search rankings

**💡 Pro Tip**: Use the visual shortcode generator in the admin dashboard to create beautiful product displays with real-time preview!

== Screenshots ==

1. Admin Settings Interface - Intuitive tab-based navigation with rich customization options
2. Frontend Product Showcase - Responsive grid layout that adapts perfectly to all devices
3. AI Chat Functionality - Smart customer service integration to enhance user experience
4. Shortcode Generator - Visual product display creation with real-time preview functionality

== Frequently Asked Questions ==

= Does this plugin support shopping cart functionality? =

No. This plugin is designed for B2B businesses, focusing on product display and inquiry functionality, without e-commerce features like shopping carts or payments.

= How can I customize product templates? =

You can create the following template files in your theme directory to override the default templates:
- `single-naibb2pr_products.php` - Single product page
- `archive-naibb2pr_products.php` - Product archive page
- `taxonomy-naibb2pr_product_category.php` - Product category page
- `taxonomy-naibb2pr_product_tag.php` - Product tag page

= What shortcodes are supported? =

The plugin provides the following shortcodes:
- `[naibabiji_b2b_products]` - Display product grid
- `[naibabiji_b2b_product_categories]` - Display product categories
- `[naibabiji_b2b_contact_form]` - Display an inline contact form for collecting messages

= Does the plugin support sidebar widgets? =

Yes! You can use the plugin's shortcodes in any text widget with the following features:

**Key Features:**
* **Smart Style Loading** - Automatically detects shortcodes in sidebar and loads CSS styles only when needed
* **Flexible Configuration** - Supports product count, column layout, category filtering and other parameter settings
* **Responsive Design** - Automatically adjusts to single column layout on mobile devices
* **Theme Compatibility** - Works seamlessly with most WordPress themes

**Usage Steps:**
1. Go to WordPress admin `Appearance → Widgets`
2. Add a "Text" widget to your sidebar
3. Paste the product shortcode into the text widget
4. Customize the shortcode parameters as needed
5. Save settings

**Recommended Configuration:**
* For sidebars: `[naibabiji_b2b_products limit="3" columns="1" show_category="false" show_excerpt="false"]`
* This configuration works well in sidebar spaces, showing 3 products in a single column layout without categories or excerpts

= Why don't product styles work in the sidebar? =

This issue has been fixed in v1.0.13. If you still encounter style problems, please try:

1. **Clear Cache** - If using a cache plugin, clear all caches
2. **Refresh Page** - Use Ctrl+F5 to force refresh the browser
3. **Check Widget Configuration** - Ensure the widget is properly saved
4. **Theme Compatibility** - Some themes may require additional CSS adjustments

If the problem persists, please contact technical support and provide the theme name for further troubleshooting.

= How do I set up bulk (multi-spec) inquiry for a product? =

1. Edit the product and set **Inquiry Type** to **Bulk**.
2. In the **Specs Management** meta box, add at least two model codes with descriptions. You can also import via CSV (first row: "Model Code,Spec Description", max 1000 rows).
3. Save the product. On the frontend, visitors will see a specs selection table where they can add items to an inquiry cart and submit a combined batch inquiry.

= What is the difference between Inquiry Mode and Inquiry Type? =

**Inquiry Type** is set per product (None / Standard / Bulk) and defines what kind of inquiry experience that single product offers. **Inquiry Mode** is a site-wide setting (External Link or Built-in Form) that only affects Standard-type products — it controls whether the inquiry button links to an external URL or opens a popup. Bulk-type products always use the built-in bulk inquiry form regardless of this setting.

= Why do I see "Too many submissions" when testing bulk inquiry? =

The plugin enforces a rate limit of 3 submissions per IP address within 5 minutes to prevent spam. If you need to test frequently or want a higher threshold, install a captcha plugin that integrates through the `naibabiji_contact_form_validate` filter.

== Changelog ==

= 5.1.2 =
* **Fixed**: Related products on single product pages now correctly exclude the current product from the random query via `post__not_in`, preventing the displayed count from dropping below the expected 4 when the current product appears in the random results.
* **New Hook**: `naibabiji_b2b_product_after_related_products` — fires after the related products section on single product pages, allowing custom content injection below the related products grid.

= 5.1.1 =
* **Fixed**: Plugin action links "Settings" URL now correctly points to the new B2B Showcase → Settings page instead of the old options-general.php URL.
* **Fixed**: Categories-only archive display mode no longer limits taxonomy (category/tag) pages to a single product; the limit now correctly applies only to the main archive page.

= 5.1.0 =
* **New Feature: Archive Display Mode** — Choose between default (filters + products), categories-only (category card grid), or products-only on the archive page.
* **New Feature: Category Images** — Upload a category image via the term editor; displayed as cards in categories-only archive mode.
* **New Feature: Default Product Sorting** — Set a default sort order (newest, oldest, title A-Z, title Z-A) for all product listing pages from the settings.
* **Improved: Category Button Styles** — Archive and category page category navigation now uses consistent button styling with product counts.
* **Improved: Container Width CSS** — Added `width: 100%` and `box-sizing: border-box` to archive/taxonomy containers for more predictable cross-theme width behavior.

= 5.0.2 =
* **New Feature**: Post-submission redirect — optionally redirect users to a custom thank-you page after successful inquiry/contact/bulk form submission, enabling Google Ads conversion tracking.
* **New Setting**: "Redirect After Submission" toggle and "Redirect URL" field added to Settings → Inquiry tab (visible when Built-in Form mode is active).
* **Improved**: Redirect URL supports both absolute URLs and relative paths (e.g. `/thank-you`).

= 5.0.1 =
* **Fixed**: Product gallery thumbnails now display as uniform squares with consistent borders across all themes, eliminating misalignment and height variance issues caused by theme-specific image sizes and aspect ratios.

= 5.0.0 =
* **New Feature: Bulk Inquiry Mode** — Products can now offer a multi-specs selection table (e.g. model codes, sizes, variants) so B2B buyers add multiple SKUs with quantities to an inquiry cart and submit one combined quote request.
* **New Feature: Inquiry Cart** — Persistent localStorage-based cart with floating sidebar, quantity editing, and per-spec removal. Customers browse across products and submit all at once.
* **New Feature: Specs Management** — Admin meta box for managing product specifications (code + description), including drag-and-drop sorting, CSV bulk import/export, and duplicate detection.
* **New: Three-Mode Inquiry System** — Each product now supports None / Standard / Bulk inquiry type via radio toggle, replacing the old binary checkbox.
* **New: Job Title Field** — Added Job Title to inquiry form fields (configurable in settings, synced across bulk/standard/contact forms).
* **New: AJAX Lead Detail** — Admin leads list now loads bulk inquiry details on demand via `naib_get_lead_detail` endpoint, avoiding large JSON in HTML data attributes.
* **New: Database Upgrade** — Version-controlled schema migration adds `inquiry_type` and `inquiry_data` columns to the leads table with idempotent checks.
* **New: Theme Compatibility Detection** — Automatically checks if the active Linghang theme meets minimum version requirements and displays a non-dismissible admin notice when outdated, preventing template-level fatal errors from version mismatches.
* **Improved**: Enhanced form field sanitization (CSV content, bulk specs JSON, lead_id validation) to meet WordPress Plugin Check standards.
* **Improved**: Bulk inquiry JS now loads on all frontend pages instead of only bulk-type product pages, ensuring the inquiry form modal and hash-based navigation always work.
* **Improved**: Bulk inquiry form modal correctly applies `.active` CSS class on overlay/panel for compatibility with plugin's shared Glassmorphism style system.
* **Fixed**: Inquiry Note (textarea) now spans full width in the 2-column form grid via `naib-field-full` class.
* **Fixed**: Cart sidebar "Submit Inquiry" button handler rewritten from fragile inline onclick to robust DOM event listener with graceful fallback chain.
* **Fixed**: Inquiry Mode settings (Built-in Form vs External Link) — Form Fields and Success Message rows are now fully hidden (including their `<tr>` label headers) when External Link mode is selected, eliminating orphaned section titles on the settings page.

= 4.2.1 =
* **Improved**: Changed product tag URL slug from `naibb2pr-product-tag` to `product-tag` for cleaner, more SEO-friendly URLs.
* **New Feature**: Added Template Override guide to the Help tab, documenting page templates and partial template overrides.

= 4.2.0 =
* **New Feature**: Inline contact form shortcode `[naibabiji_b2b_contact_form]` — embed a standalone contact form on any page without product association.
* **New Feature**: Contact form email notifications display page title instead of product name.
* **New Feature**: `Contact Form` source filter in Customer Inquiries admin list.
* **New Feature**: Captcha extension hooks (`naibabiji_contact_form_validate` filter, `naibabiji_contact_form_before_submit` action).
* **Improved**: Customer Inquiries admin page fully redesigned to support all source types (AI Chat, Inquiry Form, Contact Form).
* **Improved**: Inquiries list now shows Context column (page title for contact forms, product link for others), parsed contact names, and 30-word message preview.
* **Improved**: Detail modal for form submission inquiries with full contact info, message, and context display.
* **Improved**: Email validation on contact form (client-side regex check).
* **Improved**: Automatic nonce refresh for cached pages — contact form resubmits transparently on nonce expiry.

= 4.1.0 =
* **New Feature**: Built-in Inquiry Form Mode with unified design.
* **New Feature**: Premium UI System featuring Glassmorphism and responsive 2-column layout.
* **New Feature**: Intelligent AI Fallback system that automatically switches to the inquiry form on technical errors.
* **Improved**: Standardized form controls across all plugin components.
* **Improved**: Optimized database query security and satisfied WordPress coding standards.
* **Fixed**: Resolved various output escaping and linting warnings in templates and settings.

= 4.0.1 =
* **Fixed**: Action hook callback issue in `class-leads-handler.php` - added `run_cleanup_for_cron()` wrapper method to prevent return values in cron callbacks.
* **Improved**: Code quality validated with PHPStan Level 3 static analysis.
* **Changed**: Plugin name updated to comply with WordPress.org repository guidelines (removed "Plugin" from name).

= 4.0.0 =
* **New Feature: Industrial AI Support**: Integrated AI customer service system with SPEC v2 protocol for automated inquiry handling.
* **New Feature: Archive Page SEO Content**: Added ability to add custom content to the top and bottom of product archive/category/tag pages.
* **New Option: Hide Title**: Added option to hide default titles on archive, category, and tag pages for flexible header design.
* **Improved: Settings Page Layout**: Reorganized settings page with tab-based navigation for better user experience.
* **AI Context Awareness**: Automatic product data extraction (SKU, Price, Description) for precise AI responses.
* **Shortcode Generator**: Visual tool in admin dashboard for easy shortcode creation with real-time preview.
* **CSS Variable Optimization**: Enhanced `:root` CSS variables for better theme synchronization.
* **Production Security**: Enhanced all AJAX and save operations with strict nonce verification.

= 3.1.0 =
* Simplified: Removed the confusing "Enable Inquiry Button Globally" switch from the settings page.
* Improved: Inquiry button logic now defaults to "Enabled" for all products unless explicitly disabled individually.
* Fixed: Resolved a bug where the inquiry button was not checked by default when publishing new products.

= 3.0.2 =
* Fixed: Short description content with inline HTML elements (e.g. `<strong>`) was rendered on a single line instead of separate paragraphs due to `wpautop()` not handling single line breaks between inline elements correctly.
* Improved: Line endings are now normalized and single newlines are converted to double newlines before `wpautop()` processing, ensuring each line gets its own `<p>` tag.

= 3.0.1 =
* Fixed: Resolved Google Structured Data "Missing price" and "Missing SKU" errors by adding an optional price field and optimizing JSON-LD generation.
* Improved: Schema.org "Offer" data is now only generated if a price is specified, ensuring B2B compatibility.
* Improved: Enhanced the description in the admin settings for Schema.org markup with important usage tips.

= 3.0.0 =
* **Major Architectural Rebirth**: Transitioned to a professional model-driven architecture for enterprise-grade scalability.
* **New Product Model**: Centralized all data access through the `Naibabiji_B2B_Product` class, ensuring consistent logic and cleaner templates.
* **Efficient Grouped Meta**: All product-specific metadata is now stored in a single, high-performance array, reducing database bloat.
* **One-Click Migration Tool**: Added an automated utility in the settings page to safely upgrade legacy product data to the new v3.0.0 format.
* **Shortcode Generator**: Introduced a brand new visual tool in the admin dashboard for easy shortcode creation with real-time preview.
* **Production Security**: Enhanced all internal AJAX and save operations with strict nonce verification and context-aware escaping.
* **Modular SEO**: Decoupled Schema.org logic into a standalone builder for improved rich result precision.

= 2.0.3 =
* Added smooth transition animation when switching product detail page images
* Added loading spinner animation during image switch, with color synced to admin button color setting
* Added opacity transition effect during image loading for better user experience
* Thumbnail active border color now follows the admin button color setting
* Added language file direct access protection for zh_CN translation

= 2.0.2 =
* Changed subcategory display from vertical list to horizontal buttons for better visual balance
* Synchronized subcategory button styles with global plugin button settings (colors, hover effects, border radius)
* Improved responsive layout for subcategory navigation on mobile devices

= 2.0.1 =
* Fixed button color settings not taking effect due to CSS specificity conflicts with themes
* Changed CSS `background` shorthand to `background-color` to allow dynamic inline styles to override defaults
* Added product card hover border color to follow the admin button color setting

= 2.0.0 =
* **Major Feature Update**: Separated SEO content area into independent top and bottom sections
* Added ability to set different SEO content for page top and bottom positions
* Each position has independent enable/disable toggle
* Automatic data migration from old single content field to new dual-field structure
* Improved flexibility and control over category/tag page SEO optimization
* Breaking change: Removed position dropdown, now using separate editors for top/bottom content
* New backend product display column count setting for frontend products

= 1.0.13 =
* Fixed foreach() warning error in sidebar widget processing
* Added type checking for widget_ids to prevent type mismatch errors
* Improved data validation for WordPress sidebar widgets configuration
* Enhanced plugin stability and error handling

= 1.0.12 =
* Enhanced CSS loading logic with sidebar widget shortcode detection
* Fixed CSS styles not working in sidebar widgets
* Smart detection of shortcode content on pages, loading CSS resources only when needed
* Improved responsive design for better product card display on mobile devices

= 1.0.11 =
* Added Elementor shortcode support in SEO content area
* Standardized function and variable prefixes to comply with WordPress plugin best development practices

= 1.0.10 =
* Added toggle to enable/disable product meta information display
* Improved translations and updated localization files

= 1.0.9 =
* Added button color customization entry for front and back, managers can quickly adjust styles to match brand vision
* Replace the product short description with the fill-in area, use a rich text editor to optimize the input experience and improve Gutenberg compatibility

= 1.0.8 =
* Changed product category URL slug from 'naibb2pr-product-category' to 'product-category'
* Improved URL structure for better SEO and user experience
* Note: After updating, please deactivate and reactivate the plugin or visit Settings > Permalinks and save to refresh rewrite rules

= 1.0.7 =
* Changed product archive page URL from 'naibb2pr-products' to 'products'
* Fixed PHP syntax error in admin settings page

= 1.0.6 =
* Fixed an issue with WordPress official review feedback
* Optimized internal connection styles and script loading to comply with WordPress best practices
* Improved sanitize_callback parameter configuration of register_setting () function
* Make sure all custom article types and taxonomies are named with unique prefixes
* Removed the direct < style > and < script > tags and replaced them with wp_add_inline_style () and wp_add_inline_script () functions
* Enhanced code security and WordPress compatibility
* Fixed a PHP syntax error

= 1.0.5 =
* Optimize the display of search results page
* Removed deprecated load_plugin_textdomain() function call for WordPress 4.6+ compatibility
* Enhanced security with improved nonce verification in form processing
* Fixed input sanitization in POST and GET request handlers
* Improved security logging mechanism to follow WordPress best practices
* Replaced direct database query with WordPress API functions in meta fields handling

= 1.0.4 =
* Enhanced security in AJAX handlers
* Improved nonce verification and input sanitization
* Added recursive array sanitization
* Added GET parameter sanitization method
* Fixed permission checks in admin functions
* Improved security event logging

= 1.0.3 =
* Front-end display bug fixes

= 1.0.2 =
* Multi-language support

= 1.0.1 =
* Modify the code to meet the requirements for submitting to the official WordPress repository

= 1.0.0 =
* Initial release
* Basic product showcase functionality
* Custom post types and taxonomies
* Frontend templates and shortcodes
* Admin dashboard settings page
* AJAX functionality support
* Developer hook system


== Support ==

For technical support, please visit the plugin's page or WordPress.org support forum.

== License ==

This plugin is released under the GPL v2 or later license.