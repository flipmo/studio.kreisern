# Picture Gallery v2 - Complete Deployment Guide

## What's New in v2

✓ **Three image sizes** (thumbnail 300px, medium 1200px, original 2400px compressed)  
✓ **Description field** for detailed picture information  
✓ **View full-size** images in modal  
✓ **Edit metadata** without replacing image  
✓ **Space-efficient** storage (~77% space savings)  
✓ **Better UX** - clearer edit functionality

---

## Files Checklist

### New Files (v2)
- [ ] `api/image_helper.php` - Image processing functions

### Updated Files
- [ ] `api/init.php` - New database schema with description field
- [ ] `api/get_pictures.php` - Returns three image URLs
- [ ] `api/add_picture.php` - Creates three image sizes
- [ ] `api/update_picture.php` - Supports description, optional image replacement
- [ ] `api/delete_picture.php` - Deletes all three image files
- [ ] `index.html` - New UI with description and full-size view

### Unchanged Files
- [ ] `api/config.php` - Same database configuration
- [ ] `.htaccess` - Same security settings

---

## Step-by-Step Deployment

### 1. Backup Current System (if upgrading)

```bash
# Backup database
mysqldump -u your_username -p studio_gallery > backup_before_v2.sql

# Backup files
cp -r uploads uploads_backup
cp -r api api_backup
cp index.html index_backup.html
```

### 2. Upload New Files via FTP

Upload to your `studio.kreisern.de` folder:

```
/api/image_helper.php          → NEW FILE
/api/init.php                  → REPLACE (if fresh install)
/api/get_pictures.php          → REPLACE
/api/add_picture.php           → REPLACE
/api/update_picture.php        → REPLACE
/api/delete_picture.php        → REPLACE
/index.html                    → REPLACE
```

### 3. Create New Folder Structure

```
/uploads/
  ├── thumb/      → CREATE (for 300px thumbnails)
  ├── medium/     → CREATE (for 1200px display)
  └── original/   → CREATE (for 2400px compressed originals)
```

Set permissions:
```bash
chmod 755 uploads/thumb
chmod 755 uploads/medium
chmod 755 uploads/original
```

### 4. Database Migration

**Option A: Fresh Install**
1. Run `https://studio.kreisern.de/api/init.php`
2. See "Table 'pictures' created successfully"
3. Delete init.php

**Option B: Upgrade Existing Database**
Run this SQL in phpMyAdmin or MySQL console:

```sql
-- Add new columns to existing table
ALTER TABLE pictures
ADD COLUMN filename_medium VARCHAR(255) AFTER filename_thumb,
ADD COLUMN filename_original VARCHAR(255) AFTER filename_medium,
ADD COLUMN description TEXT AFTER color,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Rename old filename column
ALTER TABLE pictures
CHANGE COLUMN filename filename_thumb VARCHAR(255) NOT NULL;
```

### 5. Migrate Existing Images (if upgrading)

If you have existing pictures, run this PHP script once:

```php
<?php
// migrate_images.php - Run once to create multiple sizes from existing images
require_once 'api/config.php';
require_once 'api/image_helper.php';

$result = $conn->query("SELECT id, filename_thumb FROM pictures");

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $oldFile = 'uploads/' . $row['filename_thumb'];
    
    if (!file_exists($oldFile)) continue;
    
    $baseFilename = pathinfo($row['filename_thumb'], PATHINFO_FILENAME);
    $ext = pathinfo($row['filename_thumb'], PATHINFO_EXTENSION);
    
    $filename_thumb = $baseFilename . '_thumb.' . $ext;
    $filename_medium = $baseFilename . '_medium.' . $ext;
    $filename_original = $baseFilename . '_original.' . $ext;
    
    // Create medium
    resizeImage($oldFile, 'uploads/medium/' . $filename_medium, 1200, 1200, 85);
    
    // Create original
    resizeImage($oldFile, 'uploads/original/' . $filename_original, 2400, 2400, 85);
    
    // Move old file to thumb folder
    rename($oldFile, 'uploads/thumb/' . $filename_thumb);
    
    // Update database
    $stmt = $conn->prepare("UPDATE pictures SET filename_thumb = ?, filename_medium = ?, filename_original = ? WHERE id = ?");
    $stmt->bind_param("sssi", $filename_thumb, $filename_medium, $filename_original, $id);
    $stmt->execute();
    
    echo "Migrated picture ID: $id<br>";
}

echo "Migration complete!";
$conn->close();
?>
```

Upload this as `migrate_images.php`, run it once at `https://studio.kreisern.de/migrate_images.php`, then delete it.

### 6. Test Everything

- [ ] Gallery loads: `https://studio.kreisern.de`
- [ ] Add new picture with description
- [ ] View full-size image
- [ ] Edit picture metadata (without changing image)
- [ ] Edit picture and replace image
- [ ] Delete picture
- [ ] All filters work
- [ ] Check all three image sizes exist in folders

---

## Troubleshooting

### "Failed to create thumbnail/medium/original"
- Check GD library: `php -i | grep -i gd`
- Check folder permissions: `chmod 755 uploads/*`
- Check memory_limit in php.ini (recommend 256M+)

### "Column 'filename_medium' doesn't exist"
- Database not migrated
- Run the ALTER TABLE commands above

### Images don't display
- Check file paths in browser console (F12)
- Verify images exist in correct folders
- Check folder permissions

### Large file upload fails
- Increase in hosting control panel:
  - `upload_max_filesize = 20M`
  - `post_max_size = 20M`
  - `max_execution_time = 300`

---

## Performance Expectations

### Image Processing Time (approximate)
- Small image (1MB): 1-2 seconds
- Medium image (3MB): 2-4 seconds
- Large image (8MB): 4-8 seconds

### Storage Space Examples
| Original Size | After Processing | Savings |
|--------------|------------------|---------|
| 5MB | ~1.15MB | 77% |
| 10MB | ~2MB | 80% |
| 3MB | ~800KB | 73% |

### 40GB Capacity
- **Without processing:** ~8,000 photos (5MB avg)
- **With v2 system:** ~35,000 photos (~1.15MB avg per photo set)

---

## Post-Deployment Tasks

### 1. Security
- [ ] Delete `init.php` or rename to `init.php.bak`
- [ ] Delete `migrate_images.php` (if used)
- [ ] Verify `.htaccess` is active

### 2. Backup Schedule
- **Database:** Weekly
- **Images:** Monthly
- **Complete backup:** Quarterly

### 3. Monitoring
- [ ] Check disk usage monthly
- [ ] Review PHP error logs
- [ ] Test upload functionality regularly

---

## Feature Comparison

| Feature | v1 | v2 |
|---------|----|----|
| Image sizes | 1 (original) | 3 (thumb, medium, original) |
| Description | ❌ | ✅ |
| Full-size view | ❌ | ✅ |
| Edit without replacing image | ❌ | ✅ |
| Space efficiency | Low | High (77% savings) |
| Edit clarity | Unclear | Clear (metadata vs image) |
| Performance | Good | Better (smaller thumbs) |

---

## Support & Maintenance

### Regular Maintenance
1. **Weekly:** Check error logs
2. **Monthly:** Review disk space, backup database
3. **Quarterly:** Full system backup, test restore process
4. **Yearly:** Review PHP version, update if needed

### Common Tasks

**Add more creators:**
- Just add pictures with new creator names
- Filter automatically updates

**Change image quality:**
- Edit quality parameter in `add_picture.php` and `update_picture.php`
- Default is 85 (good balance of quality/size)

**Increase image sizes:**
- Edit maxWidth/maxHeight in resize calls
- Example: Change 1200 to 1800 for medium size

---

## Success Criteria

Your deployment is successful when:

✅ All pictures load with thumbnails  
✅ Can add new pictures with all fields  
✅ Can view full-size images  
✅ Can edit metadata without image  
✅ Can replace images when editing  
✅ Can delete pictures  
✅ All filters work correctly  
✅ Three image files created per upload  
✅ Description displays properly  

---

## Getting Help

If you encounter issues:

1. **Check browser console** (F12 → Console) for JavaScript errors
2. **Check PHP error log** in hosting control panel
3. **Verify all files uploaded** correctly
4. **Test with a small image** first (< 1MB)
5. **Check folder permissions** are 755

---

## Congratulations!

Your picture gallery v2 is now deployed with:
- Professional image management
- Space-efficient storage
- Great user experience
- Scalable architecture

Enjoy organizing your pictures! 🎨📸