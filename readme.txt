=== WP ULike Pro ===
Contributors: alimir
Author: TechnoWich
Tags: like, marketing, elementor, favorite, statistics
Requires PHP: 7.2.5
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 2.0.0

Boost user engagement and SEO with WP ULike Pro. Easily add "like" and "favorite" buttons and gain insights with advanced analytics—no coding needed.

== Description ==

WP ULike PRO boosts engagement with voting, user profiles, schema, and analytics—optimizing your site's performance effortlessly.

**Pro Version Features:**

* **Advanced Analytics Dashboard** - Comprehensive statistics panel with real-time insights, world map visualization, device analytics, and exportable reports (CSV, PNG, SVG)
* **Enhanced Security** - Rate limiting (login, signup, password reset, and social login), password strength indicators, user enumeration protection, secure form validation, and comprehensive OAuth security measures
* **Professional License Management** - WordPress standards-compliant update system with secure license validation and automatic updates
* **Social Integration** - Seamless social login and registration with multiple provider support, rate limiting protection, and enhanced OAuth security
* **User Management** - Advanced user profiles, email verification, 2FA support, and OTP authentication
* **Performance Optimized** - Vanilla JavaScript implementation, no jQuery dependencies, optimized API queries, and enhanced caching
* **Accessibility & RTL** - WCAG 2.1 Level AA compliant, comprehensive RTL support, and improved accessibility features
* **Multilingual Ready** - Enhanced WPML compatibility and extensive translation support
* **Modern UI/UX** - React-based statistics interface, responsive design, dark mode support, and intuitive user experience

== Installation ==

1. Download 'WP ULike Pro' from your account at [wpulike.com](https://wpulike.com).
2. Upload the 'WP ULike Pro' directory to your '/wp-content/plugins/' directory, using your favorite method (FTP, SFTP, SCP, etc...)
3. Activate 'WP ULike Pro' from your Plugins page.
4. Go to WP ULike Pro > License and enter your license key to activate automatic updates.

== Frequently Asked Questions ==

= How To Use this plugin? =
Documentation : [wpulike.com](https://docs.wpulike.com)

= Do I need a license key? =
Yes, WP ULike Pro requires a valid license key for automatic updates and access to all Pro features. You can purchase a license from [wpulike.com](https://wpulike.com).

= How do I activate my license? =
Go to WP ULike Pro > License in your WordPress admin panel and enter your license key. The plugin will automatically validate and activate your license.

= What happens if my license expires? =
Your plugin will continue to work, but you won't receive automatic updates or support. Renew your license to continue receiving updates and support.

= Is this plugin compatible with Elementor? =
Yes, WP ULike Pro is fully compatible with Elementor and includes dedicated Elementor widgets for easy integration.

= Does this plugin work with multilingual sites? =
Yes, WP ULike Pro has enhanced WPML compatibility and supports multiple languages with RTL (Right-to-Left) language support.

= Can I export my analytics data? =
Yes, the Pro version includes comprehensive export options. You can export charts and reports in CSV, PNG, or SVG formats.

= Is the plugin accessible? =
Yes, WP ULike Pro is WCAG 2.1 Level AA compliant and includes comprehensive accessibility features.

== Changelog ==

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