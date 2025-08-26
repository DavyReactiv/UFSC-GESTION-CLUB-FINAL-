# Attestation Upload Feature Implementation

## Overview
This feature allows admins to upload attestations (affiliation and insurance) for validated clubs using the WordPress media library. These attestations are then downloadable by the respective club owners on the frontend.

## Features Implemented

### Admin (Back-office)
- **Upload Interface**: Added two upload fields in the club edit page for attestations
  - Attestation d'affiliation (Affiliation Attestation)  
  - Attestation d'assurance (Insurance Attestation)
- **Media Library Integration**: Uses WordPress `wp.media` uploader instead of direct file uploads
- **File Validation**: Accepts PDF and image files (JPG, PNG) only
- **Metadata Storage**: Stores media IDs in club meta fields:
  - `_ufsc_attestation_affiliation`
  - `_ufsc_attestation_assurance`
- **Access Control**: Only available for clubs with status "valide"
- **File Management**: Replace/remove existing attestations functionality

### Frontend 
- **Secure Downloads**: Attestations are only visible/downloadable by the club owner
- **Integration**: Seamlessly integrated into existing documents section
- **Fallback Support**: Maintains compatibility with legacy attestation system
- **Access Verification**: Uses existing `ufsc_verify_club_access()` security function

### Security Features
- **Admin-only Upload**: Only users with `manage_options` capability can upload attestations
- **Club Owner Access**: Only the associated club can download their attestations
- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **File Type Validation**: Restricts uploads to safe file types (PDF, JPG, PNG)
- **Status Validation**: Only validated clubs (status = "valide") can have attestations

## Files Modified

### 1. `/includes/admin/class-menu.php`
- Added attestation upload section to `render_edit_club_page()` method
- Implemented media uploader form fields with preview functionality
- Added JavaScript for WordPress media library integration
- Added AJAX handling for attestation uploads and removal
- Enhanced success message handling

### 2. `/Plugin_UFSC_GESTION_CLUB_13072025.php`
- Added `ufsc_handle_remove_attestation()` AJAX handler
- Registered `wp_ajax_ufsc_remove_attestation` action
- Added WordPress media scripts enqueue for edit club page

### 3. `/includes/frontend/club/documents.php`
- Added `ufsc_club_has_admin_attestation()` helper function
- Modified official documents availability logic
- Enhanced `ufsc_download_attestation_affiliation()` function
- Enhanced `ufsc_download_attestation_assurance()` function
- Added support for admin-uploaded media files

### 4. `/includes/tests/attestation-functionality-test.php` (New)
- Created comprehensive test suite for verification
- Tests helper functions, AJAX handlers, and frontend logic
- Provides HTML output for admin testing

## Technical Implementation Details

### Database Schema
- Uses WordPress post meta table for storage
- Meta keys follow WordPress conventions with `_` prefix
- Stores WordPress attachment IDs, not file paths
- Leverages existing WordPress media management

### File Handling
- Files are managed through WordPress media library
- Automatic file type detection and content-type headers
- Secure file serving with proper headers
- Support for both PDF and image formats

### JavaScript Integration
- Uses WordPress media uploader API (`wp.media`)
- File type validation on client side
- Real-time preview of selected files
- AJAX removal with confirmation dialogs

## Usage Instructions

### For Admins
1. Navigate to club list in admin dashboard
2. Click "Modifier" on a validated club (status = "valide")
3. Scroll to "Attestations administratives" section
4. Click upload buttons to select files from media library
5. Submit form to save attestations

### For Club Owners
1. Login to club dashboard
2. Navigate to "Documents" section
3. Download available attestations from "Documents officiels UFSC"
4. Files will be served with appropriate content-type headers

## Security Considerations

### Access Control
- Attestation uploads: Admin only (`manage_options` capability)
- Attestation downloads: Club owner only (verified via `ufsc_verify_club_access()`)
- Status requirement: Only validated clubs can have attestations

### File Security
- Files stored in WordPress uploads directory with standard protections
- Media IDs used instead of direct file paths
- File type validation prevents dangerous uploads
- WordPress nonce protection on all AJAX requests

### Data Integrity
- Uses WordPress attachment system for reliable file management
- Meta fields follow WordPress standards
- Proper database relationships through attachment IDs

## Testing

A comprehensive test suite is included in `includes/tests/attestation-functionality-test.php` that verifies:
- Helper function existence and logic
- Meta field handling
- AJAX handler registration  
- Frontend display logic
- File extension handling

## Backward Compatibility

The implementation maintains full backward compatibility:
- Legacy `doc_attestation_affiliation` field still supported
- Existing download functionality preserved
- No database schema changes required
- Graceful fallback for missing files

## Performance Considerations

- Uses WordPress built-in caching for attachment metadata
- Minimal database queries (leverages WordPress optimization)
- Efficient file serving with proper HTTP headers
- Client-side file validation reduces server load

This implementation provides a complete, secure, and user-friendly attestation management system that integrates seamlessly with the existing plugin architecture.