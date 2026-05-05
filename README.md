# Custom Usage Statistics Plugin for OMP

## Overview

This plugin allows editors to add custom offset values to usage statistics for publications in OMP. It's designed for situations where you've migrated from a previous website and want to preserve historical statistics.

## Features

- **Custom Statistics Tab**: Adds a new tab in the publication workflow where editors can input legacy statistics
- **Separate Offset Storage**: Stores custom offsets in a dedicated database table, keeping OMP's native metrics intact
- **Combined Display**: Automatically adds offsets to displayed statistics on the frontend
- **Real-time Calculation**: Shows combined totals (OMP stats + offsets) as you type
- **Role-based Access**: Only managers, site admins, and sub-editors can modify custom statistics

## Installation

1. Extract the plugin to `plugins/generic/customStats/`
2. Log in to OMP as an administrator
3. Navigate to Settings > Website > Plugins
4. Find "Custom Usage Statistics" in the plugin list
5. Click "Enable" to activate the plugin
6. The plugin will automatically create the required database table

## Usage

### Adding Custom Statistics Offsets

1. Go to a submission's workflow
2. Click on the "Publication" tab
3. Look for the "Custom Statistics" tab
4. You'll see:
   - **Current OMP Statistics**: The actual statistics tracked by OMP
   - **Custom Offsets**: Input fields for legacy statistics
   - **Combined Total**: The total that will be displayed to users

5. Enter your legacy statistics:
   - **Abstract Views Offset**: Number of abstract views from your old system
   - **File Downloads Offset**: Number of file downloads from your old system

6. Click "Save Offsets"

### Example

If your old website had:
- 5000 Abstract Views
- 1000 Downloads

And OMP currently shows:
- 40 Abstract Views
- 10 Downloads

Enter 5000 and 1000 as offsets, and the frontend will display:
- 5040 Abstract Views
- 1010 Downloads

## How It Works

### Backend (Workflow)

- The plugin adds a custom tab to the publication workflow
- Editors can input offset values which are saved to the `custom_stats_offsets` table
- The actual OMP metrics in the `metrics_submission` table remain unchanged

### Frontend (Display)

- When statistics are displayed, the plugin intercepts the template rendering
- It retrieves the custom offsets from the database
- The offsets are added to OMP's native statistics
- The combined total is shown to users

## Database Schema

The plugin creates a table called `custom_stats_offsets`:

```sql
CREATE TABLE custom_stats_offsets (
    custom_stats_offset_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    submission_id BIGINT NOT NULL,
    context_id BIGINT NOT NULL,
    abstract_views_offset INT DEFAULT 0,
    file_downloads_offset INT DEFAULT 0,
    date_created DATETIME NOT NULL,
    date_modified DATETIME NULL,
    UNIQUE KEY (submission_id, context_id),
    FOREIGN KEY (submission_id) REFERENCES submissions(submission_id) ON DELETE CASCADE,
    FOREIGN KEY (context_id) REFERENCES presses(press_id) ON DELETE CASCADE
);
```

## Important Notes

> **Warning**: This plugin does NOT modify OMP's actual usage statistics. It only adds offset values for display purposes. The native OMP metrics continue to track real usage accurately.

> **Caution**: Be careful not to double-count statistics. If you add legacy statistics as offsets, don't also import them through other means.

## Permissions

Only users with the following roles can modify custom statistics:
- Site Administrator
- Press Manager
- Sub-Editor

Authors and reviewers cannot access or modify custom statistics.

## Uninstallation

If you disable or uninstall the plugin:
1. The `custom_stats_offsets` table will remain in the database
2. Statistics will revert to showing only OMP's native metrics
3. To completely remove the plugin, manually drop the table:
   ```sql
   DROP TABLE custom_stats_offsets;
   ```

## Support

For issues or questions, please refer to the OMP Plugin Development Guide or contact your system administrator.

## Version

- **Version**: 1.0.0.0
- **Release Date**: 2026-01-02
- **Compatible with**: OMP 3.4.x

## License

This plugin follows the same license as OMP (GNU GPL v3).
