# Changelog

All notable changes to the UFSC Gestion Club plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [20.8.2] - 2025-08-27

### Changed
- Bump plugin version to 20.8.2.

## [1.3.1] - 2025-08-26

### Added
- Documentation and admin setting for `ufsc_club_form_page_id` with `[ufsc_formulaire_club]` shortcode.

## [1.3.0] - 2025-01-21

### Added
- **Frontend UX Improvements**: Major frontend enhancements with unified stylesheet and new UX features
- **Login/Register shortcode**: New `[ufsc_login_register]` shortcode with user registration and automatic login
- **Recent licences widget**: New `[ufsc_recent_licences]` shortcode displaying latest club licences
- **Enhanced club menu**: Improved `[ufsc_club_menu]` with logout link and inactive badge
- **Auto-page creation**: Automatic creation of login/register page in addition to existing pages
- **Conditional CSS enqueuing**: Frontend styles only loaded on plugin pages or when shortcodes are detected
- **Unified stylesheet**: Enhanced `ufsc-frontend.css` with new component classes and responsive design

### Enhanced
- **Club menu functionality**: Added `show_logout` and `show_buy` attributes with intelligent defaults
- **Inactive club indication**: Badge display when club status is not active
- **Product link control**: Auto-hide "Demander Licence" when no license product is available
- **Page detection system**: Smart detection of plugin pages for asset loading
- **Status badges**: Standardized status badges for licences (validated, refused, pending, draft)

### Technical Details
- New file: `includes/frontend/shortcodes/login-register-shortcode.php` - Complete login/register functionality
- New file: `includes/frontend/shortcodes/recent-licences-shortcode.php` - Recent licences widget
- Enhanced: `includes/admin/ufsc-page-creator.php` - Added login page creation
- Enhanced: `includes/frontend/shortcodes/club-menu-shortcode.php` - Logout and inactive badges
- Enhanced: `Plugin_UFSC_GESTION_CLUB_13072025.php` - Asset enqueuing and page detection functions
- Enhanced: `assets/css/ufsc-frontend.css` - New CSS classes and responsive improvements

### Changed
- Updated page auto-creation to include login/register page
- Enhanced menu rendering with badge support and special item classes
- Improved asset loading strategy for better performance

### For Developers
- Use `ufsc_frontend_required_pages` filter to modify default pages
- Use `ufsc_club_menu_pages` filter to customize menu items
- All pages are created with proper slugs and shortcode content
- Idempotent page creation (checks for existing pages)