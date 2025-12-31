# Picture Gallery v2 - Complete Documentation

## Overview

This picture gallery system stores images in three sizes (thumbnail, medium, original) with metadata and supports full CRUD operations with an intuitive interface.

---

## Database Schema

### Table: pictures

```sql
CREATE TABLE pictures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename_thumb VARCHAR(255) NOT NULL,     -- Thumbnail (300px max)
    filename_medium VARCHAR(255) NOT NULL,    -- Medium display (1200px max)
    filename_original VARCHAR(255) NOT NULL,  -- Original (compressed 85% quality)
    creator VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    project VARCHAR(100) NOT NULL,
    color VARCHAR(50),
    description TEXT,                          -- New: detailed description
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_creator (creator),
    INDEX idx_date (date),
    INDEX idx_project (project),
    INDEX idx_color (color)
)
```

---

## Folder Structure

```
studio.kreisern.de/
├── index.html
├── api/
│   ├── config.php
│   ├── init.php
│   ├── get_pictures.php
│   ├── add_picture.php
│   ├── update_picture.php
│   ├── delete_picture.php
│   └── image_helper.php          ← New: image processing functions
├── uploads/
│   ├── thumb/                     ← Thumbnails (gallery view)
│   ├── medium/                    ← Medium size (default view)
│   └── original/                  ← Full size (download/zoom)
└── .htaccess
```

---

## API Documentation

### 1. config.php

**Purpose:** Database connection and CORS configuration

**Configuration Variables:**
- `$servername`: Database host (usually "localhost")
- `$username`: MySQL username
- `$password`: MySQL password
- `$dbname`: Database name ("studio_gallery")

**Functions:**
- Establishes MySQLi connection
- Sets UTF-8 character encoding
- Configures CORS headers for API access

**Usage:** Required by all API endpoints

---

### 2. image_helper.php

**Purpose:** Image processing utilities for resizing and compression

#### Function: `resizeImage($source, $destination, $maxWidth, $maxHeight, $quality = 85)`

**Parameters:**
- `$source` (string): Path to source image file
- `$destination` (string): Path where resized image will be saved
- `$maxWidth` (int): Maximum width in pixels
- `$maxHeight` (int): Maximum height in pixels
- `$quality` (int): JPEG quality 1-100 (default: 85)

**Returns:** boolean - true on success, false on failure

**Behavior:**
- Maintains aspect ratio
- Supports JPEG, PNG, GIF, WebP
- Creates directories if they don't exist
- Uses GD library for image manipulation

**Example:**
```php
resizeImage(
    '/tmp/upload.jpg',
    'uploads/thumb/image_123.jpg',
    300,
    300,
    85
);
```

---

### 3. get_pictures.php

**Purpose:** Retrieve all pictures with metadata

**Method:** GET

**Parameters:** None

**Response Format:**
```json
[
    {
        "id": 1,
        "url_thumb": "uploads/thumb/abc123.jpg",
        "url_medium": "uploads/medium/abc123.jpg",
        "url_original": "uploads/original/abc123.jpg",
        "creator": "Alice",
        "date": "2024-03-15",
        "project": "Summer",
        "color": "blue",
        "description": "Beautiful sunset at the beach"
    }
]
```

**SQL Query:**
```sql
SELECT id, filename_thumb, filename_medium, filename_original,
       creator, date, project, color, description
FROM pictures
ORDER BY date DESC
```

**Error Handling:**
- Returns empty array if no pictures
- Returns JSON error on database failure

---

### 4. add_picture.php

**Purpose:** Upload new picture with metadata

**Method:** POST (multipart/form-data)

**Parameters:**
- `image` (file): Image file (JPEG, PNG, GIF, WebP)
- `creator` (string): Artist name (required)
- `date` (string): Date in YYYY-MM-DD format (required)
- `project` (string): Project name (required)
- `color` (string): Dominant color (optional)
- `description` (text): Detailed description (optional)

**Process Flow:**
1. Validates required fields
2. Validates file upload and type
3. Generates unique filename: `uniqid()_timestamp.ext`
4. Creates three versions:
   - Thumbnail: 300x300px max, 85% quality
   - Medium: 1200x1200px max, 85% quality
   - Original: 2400x2400px max, 85% quality (space-saving)
5. Inserts metadata into database
6. Returns success with new ID

**Response (Success):**
```json
{
    "success": true,
    "id": 42,
    "filenames": {
        "thumb": "abc123_thumb.jpg",
        "medium": "abc123_medium.jpg",
        "original": "abc123_original.jpg"
    }
}
```

**Response (Error):**
```json
{
    "error": "Missing required fields"
}
```

**Error Codes:**
- 400: Invalid input or file type
- 500: Server error (file save or database failure)

**File Size Limits:**
- Controlled by PHP settings: `upload_max_filesize`, `post_max_size`
- Recommended: 20MB+ for high-res photos

---

### 5. update_picture.php

**Purpose:** Update picture metadata and optionally replace image

**Method:** POST (multipart/form-data)

**Parameters:**
- `id` (int): Picture ID (required)
- `creator` (string): Updated creator name (required)
- `date` (string): Updated date (required)
- `project` (string): Updated project (required)
- `color` (string): Updated color (optional)
- `description` (text): Updated description (optional)
- `image` (file): New image file (optional - only if replacing image)

**Process Flow:**

**If NO new image:**
1. Validates ID and required fields
2. Updates only metadata in database
3. Original images remain unchanged

**If new image uploaded:**
1. Retrieves old filenames from database
2. Processes new image (creates thumb, medium, original)
3. Deletes old image files
4. Updates database with new filenames and metadata

**Response (Success):**
```json
{
    "success": true,
    "updated": {
        "metadata": true,
        "image": false
    }
}
```

**Important:** The edit function updates METADATA (creator, date, project, color, description). The image itself is only replaced if a new file is uploaded.

---

### 6. delete_picture.php

**Purpose:** Delete picture and all associated files

**Method:** POST (JSON)

**Parameters:**
```json
{
    "id": 42
}
```

**Process Flow:**
1. Validates picture ID
2. Retrieves filenames from database
3. Deletes database record
4. Deletes all three image files (thumb, medium, original)
5. Returns success status

**Response (Success):**
```json
{
    "success": true,
    "deleted_files": 3
}
```

**Response (Error):**
```json
{
    "error": "Picture not found"
}
```

**Error Codes:**
- 400: Missing picture ID
- 404: Picture not found
- 500: Database or file deletion error

**Safety:** Always deletes database record first, then files. If file deletion fails, record is still removed (prevents orphaned database entries).

---

## Frontend JavaScript Documentation (index.html)

### Global Variables

```javascript
const API_BASE = 'api/';                    // API endpoint base path
let pictures = [];                          // Array of all pictures
let filters = {                             // Current filter state
    creator: null,
    year: null,
    project: null,
    color: null
};
let editingId = null;                       // ID of picture being edited (null = adding new)
let selectedFile = null;                    // File object for new/replacement image
```

---

### Core Functions

#### `loadPictures()`

**Purpose:** Fetch all pictures from API and render gallery

**Returns:** Promise\<void\>

**Process:**
1. Calls `GET api/get_pictures.php`
2. Updates global `pictures` array
3. Calls `renderGallery()`
4. Calls `renderFilters()`
5. Updates picture count
6. Hides loading indicator

**Error Handling:**
- Catches fetch errors
- Displays error message to user
- Logs to console for debugging

**Called:**
- On page load (DOMContentLoaded)
- After add/edit/delete operations

---

#### `renderGallery()`

**Purpose:** Display filtered pictures in grid layout

**Parameters:** None (uses global `pictures` and `filters`)

**Logic:**
1. Filters pictures based on active filters
2. Shows/hides filters section based on picture count
3. Renders empty state if no pictures
4. Renders "no results" if filters return nothing
5. Creates picture cards with:
   - Thumbnail image
   - Action buttons (view full, edit, delete)
   - Metadata display
   - Description (if present)

**DOM Elements Updated:**
- `#gallery`: Main picture grid
- `#empty-state`: Empty/no results message
- `#filters`: Filter section visibility

**Picture Card Structure:**
```html
<div class="picture-card">
    <img src="thumb">
    <div class="picture-actions">
        <button onclick="viewFullImage()">🔍</button>
        <button onclick="editPicture()">✏️</button>
        <button onclick="deletePicture()">🗑️</button>
    </div>
    <div class="picture-info">
        <div class="creator">...</div>
        <div class="project">...</div>
        <div class="date">...</div>
        <div class="description">...</div>
        <span class="color-tag">...</span>
    </div>
</div>
```

---

#### `renderFilters()`

**Purpose:** Generate filter buttons for each category

**Process:**
1. Extracts unique values from pictures array:
   - Creators: sorted alphabetically
   - Years: sorted descending (newest first)
   - Projects: sorted alphabetically
   - Colors: sorted alphabetically (excludes empty)
2. Generates button elements for each value
3. Applies "active" class to selected filters
4. Updates clear filters button visibility

**Filter Extraction Examples:**
```javascript
// Creators
const creators = [...new Set(pictures.map(p => p.creator))].sort();

// Years (from full date)
const years = [...new Set(pictures.map(p => p.date.split('-')[0]))].sort().reverse();
```

**DOM Elements Updated:**
- `#filter-creator`
- `#filter-year`
- `#filter-project`
- `#filter-color`
- `#clear-filters` button visibility

---

#### `toggleFilter(category, value)`

**Purpose:** Toggle filter selection on/off

**Parameters:**
- `category` (string): 'creator', 'year', 'project', or 'color'
- `value` (string): The value to filter by

**Behavior:**
- If filter is already active: deactivates it (sets to null)
- If filter is inactive: activates it (sets to value)

**Side Effects:**
1. Updates global `filters` object
2. Re-renders gallery with new filter
3. Re-renders filter buttons (updates active states)
4. Updates picture count

**Example:**
```javascript
toggleFilter('creator', 'Alice');  // Show only Alice's pictures
toggleFilter('creator', 'Alice');  // Show all creators again
```

---

#### `getFilteredPictures()`

**Purpose:** Apply all active filters to pictures array

**Returns:** Array of filtered pictures

**Filter Logic (AND operation):**
```javascript
return pictures.filter(pic => {
    if (filters.creator && pic.creator !== filters.creator) return false;
    if (filters.year && pic.date.split('-')[0] !== filters.year) return false;
    if (filters.project && pic.project !== filters.project) return false;
    if (filters.color && pic.color !== filters.color) return false;
    return true;  // Include if passes all active filters
});
```

**Example:**
- Active filters: creator="Alice", year="2024"
- Result: Only pictures by Alice from 2024

---

#### `clearFilters()`

**Purpose:** Reset all filters to show all pictures

**Process:**
1. Resets global `filters` object to null values
2. Re-renders gallery (shows all pictures)
3. Re-renders filters (removes active states)
4. Updates picture count

---

#### `openAddModal()`

**Purpose:** Open modal for adding new picture

**Process:**
1. Resets form state:
   - Sets `editingId = null`
   - Clears `selectedFile`
   - Sets modal title to "Add Picture"
   - Clears all input fields
   - Sets date to today
   - Shows upload placeholder
2. Opens modal (adds 'show' class)

---

#### `editPicture(id)`

**Purpose:** Open modal for editing existing picture

**Parameters:**
- `id` (int): Picture ID to edit

**Process:**
1. Finds picture in global array
2. Sets `editingId = id`
3. Populates form with existing data:
   - Creator, date, project, color, description
   - Shows current thumbnail image
4. Sets modal title to "Edit Picture"
5. Changes button text to "Update"
6. Opens modal

**Image Handling:**
- Displays current thumbnail with remove button
- User can keep existing image or upload replacement
- If no new image selected, only metadata is updated

---

#### `viewFullImage(id)`

**Purpose:** Display original full-resolution image in modal

**Parameters:**
- `id` (int): Picture ID to view

**Process:**
1. Finds picture in global array
2. Creates full-screen modal overlay
3. Displays original image (not medium or thumb)
4. Shows metadata overlay
5. Provides close button and click-outside-to-close

**Features:**
- Shows full resolution for detail viewing
- Displays complete metadata
- Shows description if available
- Mobile-responsive

---

#### `handleImageSelect(event)`

**Purpose:** Process file selection from input

**Parameters:**
- `event` (Event): File input change event

**Process:**
1. Gets selected file from event
2. Stores in global `selectedFile`
3. Uses FileReader to create data URL preview
4. Updates upload area to show preview
5. Adds remove button to preview

**File Reader:**
```javascript
const reader = new FileReader();
reader.onload = (e) => {
    // e.target.result contains base64 data URL
    // Used only for preview, actual file uploaded separately
};
reader.readAsDataURL(file);
```

---

#### `savePicture()`

**Purpose:** Submit add or update request

**Returns:** Promise\<void\>

**Validation:**
- Required fields: creator, date, project
- For new pictures: image file required
- For updates: image file optional

**Process:**

**For New Picture (editingId === null):**
1. Creates FormData object
2. Appends all fields including image file
3. POSTs to `api/add_picture.php`
4. On success: closes modal, reloads gallery

**For Update (editingId !== null):**
1. Creates FormData with ID and fields
2. Only appends image if new file selected
3. POSTs to `api/update_picture.php`
4. On success: closes modal, reloads gallery

**FormData Structure:**
```javascript
formData.append('creator', 'Alice');
formData.append('date', '2024-03-15');
formData.append('project', 'Summer');
formData.append('color', 'blue');
formData.append('description', 'Sunset view...');
formData.append('image', fileObject);  // Only if new/replacement image
```

**Error Handling:**
- Shows alert on validation failure
- Shows alert on server error
- Logs errors to console

---

#### `deletePicture(id)`

**Purpose:** Delete picture after confirmation

**Parameters:**
- `id` (int): Picture ID to delete

**Process:**
1. Shows browser confirmation dialog
2. If confirmed:
   - POSTs to `api/delete_picture.php`
   - Sends JSON: `{"id": 42}`
   - On success: reloads gallery
3. If cancelled: no action

**Safety:** Requires explicit user confirmation

---

### Event Handlers

```javascript
// Page load
window.addEventListener('DOMContentLoaded', loadPictures);

// File input
document.getElementById('file-input').addEventListener('change', handleImageSelect);

// Modal close (click outside)
document.getElementById('modal').addEventListener('click', (e) => {
    if (e.target.id === 'modal') closeModal();
});
```

---

## Image Size Strategy

### Three-Tier System

1. **Thumbnail (300px)**: Gallery grid view
   - Fast loading
   - Minimal bandwidth
   - Quality: 85%

2. **Medium (1200px)**: Default detail view
   - Good quality for screen viewing
   - Reasonable file size
   - Quality: 85%

3. **Original (2400px)**: Full resolution
   - Maximum detail
   - Print quality
   - Quality: 85% (compressed to save space)
   - Only loaded on explicit user request

### Space Savings

**Example: 4000x3000px original photo (5MB)**

After processing:
- Thumbnail: 300x225px (~50KB)
- Medium: 1200x900px (~300KB)
- Original: 2400x1800px (~800KB, compressed from 5MB)

**Total per picture:** ~1.15MB (vs 5MB unprocessed)
**Space savings:** ~77%

**40GB capacity:**
- Original uploads only: ~8,000 photos
- With three-tier system: ~35,000 photos

---

## Edit Functionality Clarification

### What Can Be Edited?

**Always Editable (without re-uploading image):**
- Creator name
- Date
- Project name
- Color
- Description

**Image Replacement (optional):**
- User can upload new image to replace existing
- If no new image selected, existing images stay unchanged
- When replaced, all three sizes regenerated

### UI Flow

1. Click edit button on picture
2. Modal opens with current metadata filled in
3. Current thumbnail shown with note: "Upload new image to replace, or keep current"
4. User can:
   - **Option A:** Just edit metadata, click Update
   - **Option B:** Upload new image AND edit metadata, click Update
5. System handles accordingly

---

## Security Considerations

### File Upload Security

1. **Type Validation:**
   ```php
   $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
   ```

2. **Filename Sanitization:**
   - Generated names: `uniqid()_timestamp.ext`
   - Never use user-provided filenames

3. **Directory Protection:**
   - `.htaccess` prevents PHP execution in uploads/
   - Prevents directory listing

4. **File Size Limits:**
   - Set in PHP configuration
   - Validated before processing

### Database Security

1. **Prepared Statements:**
   ```php
   $stmt = $conn->prepare("INSERT INTO pictures (...) VALUES (?, ?, ?)");
   $stmt->bind_param("sss", $var1, $var2, $var3);
   ```
   - Prevents SQL injection

2. **Input Validation:**
   - Required field checks
   - Date format validation
   - String length limits

### Access Control

- No authentication in current version
- Recommendation: Add user login for production
- Consider IP whitelisting for admin functions

---

## Performance Optimization

### Database Indexes

```sql
INDEX idx_creator (creator)    -- Fast filtering by creator
INDEX idx_date (date)          -- Fast filtering by date/year
INDEX idx_project (project)    -- Fast filtering by project
INDEX idx_color (color)        -- Fast filtering by color
```

### Image Loading Strategy

1. **Gallery view:** Loads only thumbnails (300px)
2. **Hover/Click:** No preloading (on-demand)
3. **Detail view:** Loads medium (1200px)
4. **Full view:** Loads original only when requested

### Lazy Loading (Future Enhancement)

Consider implementing for large galleries:
```javascript
<img loading="lazy" src="...">
```

---

## Browser Compatibility

### Supported Browsers

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Required Features

- JavaScript ES6+
- Fetch API
- FileReader API
- FormData API
- CSS Grid
- CSS Flexbox

### Graceful Degradation

- Loading indicator for slow connections
- Error messages for failed uploads
- Empty state guidance

---

## Deployment Checklist

- [ ] Database created
- [ ] All API files uploaded
- [ ] config.php configured with credentials
- [ ] init.php run once and removed
- [ ] All three upload folders created (thumb/, medium/, original/)
- [ ] Folder permissions set (755/775)
- [ ] .htaccess uploaded
- [ ] PHP 8.2 selected
- [ ] Test image upload
- [ ] Test image edit (metadata only)
- [ ] Test image replacement (new file)
- [ ] Test view full image
- [ ] Test delete
- [ ] Test all filters

---

## Maintenance

### Regular Tasks

1. **Backup database weekly:**
   ```bash
   mysqldump -u user -p studio_gallery > backup.sql
   ```

2. **Backup uploads folder monthly:**
   ```bash
   tar -czf uploads_backup.tar.gz uploads/
   ```

3. **Monitor disk space:**
   - Check hosting panel
   - Set alerts at 80% capacity

4. **Check PHP error logs:**
   - Look for upload failures
   - Check for database errors

### Troubleshooting

**Images not resizing:**
- Check GD library installed: `php -i | grep -i gd`
- Check memory_limit in php.ini

**Upload fails:**
- Check upload_max_filesize
- Check post_max_size
- Check tmp directory permissions

**Database errors:**
- Check connection credentials
- Verify user has proper permissions (SELECT, INSERT, UPDATE, DELETE)

---

## Future Enhancements

1. **User Authentication**
   - Login system
   - Multi-user support
   - Per-user galleries

2. **Advanced Search**
   - Full-text search in descriptions
   - Date range filters
   - Tag system

3. **Batch Operations**
   - Multi-select pictures
   - Bulk delete
   - Bulk edit metadata

4. **Export Functions**
   - Download selected pictures
   - Generate PDF catalog
   - Export metadata to CSV

5. **Social Features**
   - Share links
   - Embed codes
   - Public/private toggle

6. **Analytics**
   - View counts
   - Popular pictures
   - Upload statistics