# Database Schema Fix Summary

## Issues Fixed

This update addresses critical database schema issues that were causing SQL errors during plugin activation:

### 1. Foreign Key Constraint Incompatibility
**Problem:** Referencing column 'club_id' and referenced column 'id' in foreign key constraint 'fk_licence_club' are incompatible.

**Solution:** 
- Ensured both `wp_ufsc_clubs.id` and `wp_ufsc_licences.club_id` use exactly the same data type: `MEDIUMINT(9) UNSIGNED`
- Added database patching logic to fix existing installations with incompatible column types
- Improved foreign key constraint management to drop and recreate constraints when column types are fixed

### 2. Missing responsable_id Column
**Problem:** Column 'responsable_id' missing from wp_ufsc_clubs table, causing SELECT and INSERT errors.

**Solution:**
- Added `responsable_id INT(11) NULL` column to the clubs table schema
- Updated database patching to add the column to existing installations
- Updated data format handling to properly process the responsable_id field

## Database Schema Changes

### wp_ufsc_clubs Table
```sql
-- New column added:
responsable_id int(11) NULL

-- Column type ensured:
id mediumint(9) unsigned NOT NULL AUTO_INCREMENT
```

### wp_ufsc_licences Table
```sql
-- Column type ensured:
club_id mediumint(9) unsigned NOT NULL
id mediumint(9) unsigned NOT NULL AUTO_INCREMENT

-- Foreign key constraint:
CONSTRAINT fk_licence_club FOREIGN KEY (club_id) REFERENCES wp_ufsc_clubs (id) ON DELETE CASCADE
```

## Backward Compatibility

The fixes are fully backward compatible:
- Existing data is preserved
- Column type changes are handled gracefully
- Missing columns are added automatically
- No manual intervention required

## Testing

A comprehensive test suite (`includes/tests/database-schema-test.php`) has been added to validate:
- Table structure correctness
- Column type compatibility
- Foreign key constraint functionality
- responsable_id column presence

## Usage

The `responsable_id` field is used to:
- Link clubs to WordPress users who manage them
- Control access to club documents and data
- Enable user-specific club operations

This field is automatically populated when clubs are created from the frontend by logged-in users.