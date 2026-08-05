=== WP ULike Pro ===
Contributors: alimir
Author: TechnoWich
Tags: like, marketing, elementor, favorite, statistics
Requires PHP: 7.3.0
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 2.3.1

Boost user engagement and SEO with WP ULike Pro. Like and dislike buttons, deep analytics, and site tools that work without code.

== Description ==

WP ULike Pro extends the free plugin when you need richer feedback, serious analytics, and more control over how voting appears on your site.

= Statistics that go deeper =

Open **WP ULike → Statistics** to turn votes into useful answers, not just totals.

**Overview** shows how your site is really doing: engagement rate (views turning into votes), button impressions, unique voters, like vs dislike sentiment, and trend charts compared to the previous 30 days. You also get growth tips, best times to publish, top categories with links to deeper reports, and your leading countries.

**Engagement reports** cover posts, comments, BuddyPress activities, bbPress topics, and more. Each report includes charts, week-over-week metrics, and top-content lists so you can spot what resonates.

**Top members** reveals your most active engagers. Drill into any popular item to see who liked it.

**Content intelligence** adds a full activity heatmap, peak hours, and category performance to help you plan content and publishing.

**WooCommerce** (under Intelligence, when WooCommerce is active) connects product and review engagement with store sales: revenue, orders, top products, engagement-vs-orders trends, opportunities where interest and sales diverge, and category correlation. Built for WooCommerce HPOS and modern order analytics tables.

**Countries** gives you an interactive world map, country rankings, growth trends, and filters by content type.

**Technology** breaks down voters by device, operating system, and browser, with date-range filters when you need to zoom in.

**Logs** let you search vote history, filter by status and date, remove records, and download CSV exports.

Dark mode and focus mode are built in. Reports adapt to your voting setup (likes only, likes + dislikes, or logged-in members).

= More Pro tools =

**Pro Version Features:**

* **Dislike buttons** and **25+ premium templates** for richer feedback and branded UI
* **WooCommerce commerce intelligence** — engagement vs orders, revenue KPIs, top products, opportunities, and trends (HPOS-compatible)
* **View tracking & engagement rates** to measure how page views convert into votes
* **Display automation & bulk actions** for posts, WooCommerce, EDD, and more. Per-rule **button appearance** options let you pick templates and control counters and likers boxes (great when shop archives and product pages need different looks)
* **Schema.org markup** for star ratings and FAQ rich results
* **User profiles, login forms, social login, and share buttons**
* **Elementor widgets** and **REST API** for custom builds and integrations
* **Enhanced Security** with rate limiting, password strength indicators, user enumeration protection, and secure OAuth
* **Advanced GDPR tools**, **email notifications**, and **priority support**
* **Accessibility & RTL**, **WPML-ready** translations, and a fast vanilla JavaScript frontend

== Installation ==

1. Download 'WP ULike Pro' from your account at [wpulike.com](https://wpulike.com/?utm_source=readme&utm_medium=wp-repo&utm_campaign=install).
2. Upload the 'WP ULike Pro' directory to your '/wp-content/plugins/' directory, using your favorite method (FTP, SFTP, SCP, etc...)
3. Activate 'WP ULike Pro' from your Plugins page.
4. Go to WP ULike Pro > License and enter your license key to activate automatic updates.

== Frequently Asked Questions ==

= How To Use this plugin? =
Documentation : [wpulike.com](https://docs.wpulike.com/?utm_source=readme&utm_medium=wp-repo&utm_campaign=documentation)

= Do I need a license key? =
Yes, WP ULike Pro requires a valid license key for automatic updates and access to all Pro features. You can purchase a license from [wpulike.com](https://wpulike.com/pricing/?utm_source=readme&utm_medium=wp-repo&utm_campaign=get-license).

= How do I activate my license? =
Go to WP ULike Pro > License in your WordPress admin panel and enter your license key. The plugin will automatically validate and activate your license.

= What happens if my license expires? =
Your plugin will continue to work, but you won't receive automatic updates or support. Renew your license to continue receiving updates and support.

= Is this plugin compatible with Elementor? =
Yes, WP ULike Pro is fully compatible with Elementor and includes dedicated Elementor widgets for easy integration.

= Does this plugin work with multilingual sites? =
Yes, WP ULike Pro has enhanced WPML compatibility and supports multiple languages with RTL (Right-to-Left) language support.

= Can I export my analytics data? =
Yes. You can search and filter vote logs in **Statistics → Logs** and download them as CSV for spreadsheets or backups.

= What stats do I get with Pro that Free does not? =
Pro adds engagement rate and view tracking, unique voter insights, sentiment breakdown, top countries with a full world map, content intelligence (heatmaps and categories), **WooCommerce commerce intelligence**, device and browser reports, top members, per-item liker lists, richer growth tips, and CSV log exports. Free covers Overview basics, engagement reports, publish-timing insights, a WooCommerce report preview, and logs without the advanced intelligence layers.

= Is the plugin accessible? =
Yes, WP ULike Pro is WCAG 2.1 Level AA compliant and includes comprehensive accessibility features.

== Changelog ==

= 2.3.1 =
* Fix: Settings could not be saved on hosts using utf8/utf8mb3 database charsets when Pro reaction emoji were included in the options payload. Emoji are now stored safely (WordPress-style HTML entities) and decoded for the UI and frontend.
* Fix: When Display Automation owns emoji/star for a content type, the global engagement template no longer bleeds onto basic auto-display placements (e.g. archives).
* Fix: Engagement AJAX (votes/ratings and engagers) now bootstraps the correct template context and fails closed when engagements are not enabled for that request.
* Security: Engagement vote and engagers AJAX nonces are bound to the engagement template, so clients cannot enable a different engagement mode by spoofing the template parameter.
* Improved: Emoji picker dismiss behavior, counter UI when counters are hidden, star rating keyboard/hover preview, customizer engagement previews, and clearer singular/plural notices.

= 2.3.0 =
* New: **Emoji Reactions & Star Rating templates**. Go beyond like/dislike — let visitors react with a set of emoji or rate with stars, chosen per rule in Display Automation. Reactions get their own engager lists, counts, and schema.org star-rating support.
* Improved: Statistics, Top Content, Top Members, and Logs now read from WP ULike's new Pulse storage engine, with faster queries and less lag on large, high-traffic sites.
* Requires: WP ULike (free) 5.2.1 or later — needed for the new Pulse storage engine that this release's statistics and reactions features read from.

= 2.2.2 =
* New: **Statistics dashboard**. Open **WP ULike → Statistics** for a full analytics workspace with dark mode and focus mode.
* New: **Overview**. Engagement rate, likes and interactions, button impressions, unique voters, sentiment breakdown, and daily trend charts for the last 30 days.
* New: **Growth tips**. Practical suggestions based on your engagement, views, categories, and store data.
* New: **Engagement reports**. Charts, metrics, and top-content lists for posts, comments, BuddyPress activities, bbPress topics, and other supported types.
* New: **Top members**. See your most active engagers and drill into who liked each piece of content.
* New: **Content intelligence**. When to publish (peak hours and time windows), activity heatmap, and category performance.
* New: **WooCommerce commerce intelligence** under **Intelligence → WooCommerce**: store snapshot KPIs, engagement vs orders trend, top products, opportunities, category correlation, and actionable tips — with date-range filters and HPOS-compatible sales data.
* New: **Countries report**. Interactive world map, country rankings, growth trends, and filters by content type.
* New: **Technology report**. Device, operating system, and browser breakdowns with date-range filters.
* New: **Logs**. Search and filter vote history, delete entries, and download CSV exports.
* New: **Button appearance options in Display Automation**. Per rule, pick a button template and control the vote counter, likers box, and likers display style. Useful when archives and product pages need different looks.
* Improved: Faster loading when switching between Overview, reports, and logs.
* Improved: Reports adapt to your voting mode (likes only, likes + dislikes, or logged-in members).
* Improved: WooCommerce sales queries use analytics lookup tables with `wc_get_orders()` fallback; optimized commerce API caching and queries.
* Fix: Stats API reliability and minor stability improvements.

= 2.2.1 =
* Performance: **Lighter plugin** — replaced heavy third-party packages with built-in schema and analytics helpers.
* Improved: **Schema.org output**, Tools page, overview dashboard, and editor display options.
* Improved: **GeoIP lookup** with an updated database.
* Fix: Various stability and translation fixes.

= 2.2.0 =
* New: **Schema Generator** in **Tools → Schema Generator** — search posts, configure star ratings, FAQ, and advanced Schema.org fields from one screen with live preview and rating estimates.
* Improved: **Redesigned Tools page** with clearer tab navigation across Maintenance, Display Automation, Schema Generator, Bulk Actions, GDPR, REST API, and Debug Info.
* New: **Per-post and per-comment display meta boxes** in the editor — override template, position, and auto-display settings without leaving the post or comment screen.
* Improved: **Comment like buttons** now respect per-comment display overrides (template, placement, and manual display when global auto-display is off).
* Improved: **Schema.org output** — more reliable date formatting and richer fields for types such as SoftwareApplication, Event, and Product.
* Improved: **Free plugin dependency checks** — clearer admin notices when WP ULike is missing, inactive, or below the required version (5.0.5+; 5.0.6 recommended).
* Fix: **Legacy schema meta** from older installs continues to work after the schema UI move.
* Fix: **Voting script reliability** — frontend like/unlike behavior aligned with the latest free plugin script.

= 2.1.3 =
* Improved: **Redesigned License page** — clearer status, simple next steps, and activate / refresh / deactivate without reloading the page.
* Improved: Renewal reminders that match how you pay (active subscription vs one-time), plus clearer help for expired, cancelled, or domain-change issues.
* New: **Copy for support** on the License page — paste safe site details into a ticket (no full license key or passwords).
* Improved: License screen works alongside the free plugin overview and quick links to your account at wpulike.com.

= 2.1.2 =
* New: **Display Automation** — place like buttons with advanced rules in **Tools → Display Automation**. Choose platform, content type, hook, and optional filters; includes a step-by-step wizard, WooCommerce and **Easy Digital Downloads** store placements, custom hooks, example rules, and per-rule control over basic Automatic Display.
* Improved: Verified compatibility with **WordPress 7.0**.
* Improved: More reliable REST API authorization on common server setups and stricter role-based access.

= 2.1.1 =
* Fix: Resolved various possible errors for improved stability.
* Improvement: Added error log information into the debug info tool for better troubleshooting and diagnostics.
* Improvement: Improved GeoIP lookup with enhanced reliability and accuracy.
* Performance: General performance improvements across the plugin.

= 2.1.0 =
* Improvement: Moved REST API settings from Settings menu to Tools menu for better organization and easier access.
* New: Added support for redesigned settings and customizer panel with enhanced functionality and improved user interface.
* Improvement: Enhanced UI improvements in stats panel with better visual design and user experience.
* Improvement: Removed deprecated shortcode generator and profile meta box for cleaner codebase and improved maintainability.
* Improvement: Enhanced social share shortcode functionality with additional features and improved reliability.
* Performance: Optimized plugin performance with code improvements and enhanced efficiency across various components.

= 2.0.0 =
* New: Introduced view tracking service for all content types with engagement rate calculation (Likes + Dislikes / Views * 100) and intelligent tracking using Intersection Observer API with batched requests.
* New: Added Growth column to WorldMap country statistics table for tracking country performance trends.
* New: Added modal for "Other Countries" in WorldMap - click row to view full list of countries.
* New: Added mobile-friendly card view for WorldMap table with responsive design.
* Improvement: Enhanced React-based statistics panel with improved UI/UX for Items and Logs pages, including mobile-friendly card views and better table designs.
* Improvement: Enhanced OTP input component with improved accessibility and smoother AJAX form interactions.
* Improvement: Enhanced mobile responsiveness across all statistics components.
* Improvement: Fixed session notices cleanup to properly unset keys instead of setting to null, preventing unnecessary database storage.
* Improvement: Added automatic daily cleanup for expired sessions via WordPress cron to prevent database bloat.
* Performance: Optimized view tracking system with efficient database indexing and batched request handling.
* Performance: Optimized React hooks and replaced heavy JavaScript animations with lightweight CSS animations.
* Performance: Added database index on session_expiry column for faster session cleanup queries.
* Accessibility: Improved form and profile styling with enhanced WCAG 2.1 Level AA compliance.
* Accessibility: Fixed RTL support for Growth column and icons with proper spacing.
* Fix: Resolved various minor bugs and UI inconsistencies.
* Fix: Added safety check for empty session keys in guest session cleanup to prevent potential errors.

= 1.9.9 =
* New: Added comprehensive GDPR tools section with user search and bulk log removal functionality for compliance with data protection regulations.
* New: Introduced advanced bulk actions feature with various filters (post type, taxonomy, category, search, and item ID) for efficient content management.
* New: Added debug information tool for troubleshooting and system diagnostics.
* Improvement: Moved optimization features into the Tools section for better organization and easier access.
* Improvement: Enhanced maintenance options with improved user interface and better functionality.
* Improvement: Updated and improved text strings throughout the plugin for better clarity and user experience.
* Improvement: Enhanced translations with updates to multiple language files.
* Fix: Resolved various minor bugs and UI inconsistencies for improved stability.

= 1.9.8 =
* New: Redesigned user profile pages with Instagram-inspired layout, featuring improved visual hierarchy and modern aesthetics.
* New: Custom stylish avatar uploader with real-time preview and enhanced user experience.
* Improvement: Redesigned notice banners with minimal, user-friendly styling that matches form design aesthetics, including full RTL and mobile support.
* Improvement: Enhanced profile action buttons with minimal, compact design for better user experience.
* Improvement: Improved badges section layout with better mobile responsiveness and centered alignment.
* Improvement: Optimized profile layout structure with bio and custom HTML sections positioned above tabs for better content flow.
* Improvement: Enhanced avatar uploader with comprehensive theme style isolation to prevent conflicts with third-party themes.
* Improvement: Strengthened security measures across all forms, database queries, and user input processing following WordPress best practices.
* Improvement: Enhanced code quality by removing deprecated functions and modernizing codebase for better PHP 7.2+ compatibility.
* Accessibility: Improved password form compatibility with password managers and enhanced WCAG compliance.
* Accessibility: Enhanced form accessibility with proper ARIA labels, screen reader support, and keyboard navigation improvements.
* Accessibility: Improved notice banner accessibility with proper semantic HTML and screen reader compatibility.
* Fix: Resolved avatar uploader theme style conflicts that were affecting image display.
* Fix: Fixed badge item layout issues on mobile devices for better alignment and spacing.
* Fix: Corrected profile layout flexbox issues that were causing sections to display incorrectly.
* Fix: Resolved various minor UI inconsistencies and styling conflicts.

= 1.9.7 =
* New: Added rate limiting protection for login (5 attempts per 15 minutes), signup (3 per hour), password reset (3 per hour), and social login (10 attempts per 15 minutes) forms using fingerprint-based identification.
* New: Added real-time password strength indicator with visual strength barfor signup and password reset forms.
* New: Added password visibility toggle button with accessibility features (WCAG 2.1 Level AA compliant) for all password fields.
* Security: Enhanced security measures to prevent user enumeration attacks in login and password reset forms.
* Security: Improved data sanitization and validation throughout all form submissions.
* Security: Enhanced social login security with provider whitelisting, comprehensive input sanitization, SQL injection prevention, open redirect protection, session validation, and email validation.
* Improvement: Enhanced license management system with improved update mechanism following WordPress standards.
* Improvement: Refactored all JavaScript code to vanilla JavaScript, removing jQuery dependencies for better performance and compatibility.
* Improvement: Refactored reCAPTCHA handling with ES6 standards and improved refresh logic, including smart skipping for 2FA prompts.
* Improvement: Enhanced form validation and user experience with better error handling and feedback.
* Improvement: Improved OTP input handling and fragment application for smoother AJAX form interactions.
* Improvement: Added comprehensive RTL (Right-to-Left) support for forms and modals.
* Improvement: Enhanced accessibility features including improved 2FA field accessibility.
* Improvement: General code optimizations and performance improvements.

= 1.9.6 =
* Enhancement: Added Japanese translation.
* Improvement: Serialize metabox is now enabled by default for better data handling.
* Improvement: General code optimizations and performance improvements.
* Fix: Resolved various minor bugs.

= 1.9.5 =
* Enhancement: Refined metrics dashboard with improved filters, charts and lists components.
* Improvement: Overview section now includes device statistics and detailed user insights.
* Improvement: Updated list and chart layouts for better responsiveness and aesthetics.
* Fix: Minor UI and layout adjustments for smoother experience.

= 1.9.4 =
* New: Added a content type filter to the World Map for more precise insights.
* Improvement: Upgraded UI of the filter panel for a smoother experience.
* Improvement: Enhanced license activation debug with more detailed information.
* Fix: Resolved minor bugs for improved stability.

= 1.9.3 =
* Improvement: Enhanced UI on the stats panel for a better experience, now fully mobile-friendly.
* Improvement: Optimized code for improved performance in the stats panel.
* Fix: Resolved minor bugs.

= 1.9.2 =
* New: Display user country data on a world map with top engagement percentages.
* New: Replace the user role engagement chart with a device type chart.
* Improvement: Enhanced stats panel UI with grouped engagement summaries.
* Improvement: Refined RTL support and fixed missing translations.
* Improvement: Optimized stats panel API queries for better performance.
* Fix: Minor bug fixes.

= 1.9.1 =
* Improvement: Enhanced license checker system for better validation and stability.
* Improvement: Redesined Stats Panel UI with a modern layout and improved data visualization.
* Fix: Various bugs and performance issues.

= 1.9.0 =
* Improvement: Enhanced responsiveness of the stats panel and optimized API performance.
* Fix: Resolved several minor issues for better stability and user experience.

= 1.8.9 =
* Improvement: Enhanced WPML compatibility to better handle multilingual setups.
* Improvement: Addressed various UI experience issues to create a more intuitive and user-friendly interface.
* Fix: Addressed several minor bugs to improve overall stability.

= 1.8.8 =
* New: Added detailed top items in the stats panel, showcasing engagement rate, likers list, and more comprehensive insights.
* Improvement: Enhanced stats panel performance for faster loading and smoother interaction.
* Security: Implemented nonce validation in the stats panel for strengthened protection.
* Fix: Addressed various minor bugs for a more stable experience.

= 1.8.7 =
* New: Added email verification support for newly registered users.
* Tweak: Enhanced stats panel datepicker with a modern, stylish design, including support for preset date ranges.
* Tweak: Improved sorting functionality on the logs page.
* Tweak: Optimized select fields for better compatibility with dark mode.
* Fix: Addressed several minor bugs and performance issues.

= 1.8.6 =
* New: New overview data sections to show monthly & daily performance, including GMGR calculations for more detailed trend analysis.
* New: User roles engagement displayed through pie charts, offering insights into user interactions.
* New: Option to download charts in CSV, PNG, or SVG formats, making it easier to export and share data.
* Tweak: Comprehensive enhancements to all stats codes, ensuring better performance and accuracy.

= 1.8.5 =
* New: Introduced an advanced, React-based statistics menu with enhanced features, including growth tracking and pagination for top items.
* Tweak: Improved the login, registration, and password reset forms, enhancing HTML markup.
* Tweak: Removed deprecated menus and scripts.
* Fix: Resolved issue with avatar upload folder creation.
* Fix: Addressed deprecated BuddyPress methods.
* Fix: Corrected various issues related to license checks.
* Fix: Implemented several minor bug fixes.

= 1.8.4 =
* Tweak: Added a net_votes meta field, calculating the difference between likes and dislikes.
* Tweak: Add autocomplete tags on login, reigster and rest password forms.
* Tweak: Update social icons. (Twitter, Google, WordPress)
* Tweak: Update third-party libraries.
* Fix: Resolved deprecated JS methods.
* Fix: Various Minor Bug Fixes.

= 1.8.3 =
* New: Connect and Login Effortlessly with Our Latest Social Integration.
* Tweak: Enhanced Reset Password Functionality.
* Tweak: Expanded WordPress REST API with Votes Details
* Tweak: Introducing a New Session Handler Class.
* Fix: Improved CSV Logs Export with "Item ID" Column
* Fix: Resolved Issues with Stats REST API
* Fix: Various Minor Bug Fixes.