# Picture Gallery v2 - Complete File Structure

## 📁 Final File Structure

```
studio.kreisern.de/
├── index.html                  ← Main HTML (clean, short)
├── styles.css                  ← All CSS styles
├── script.js                   ← All JavaScript
├── .htaccess                   ← Security settings
├── api/
│   ├── config.php             ← Database configuration
│   ├── init.php               ← Database setup (run once, then delete)
│   ├── image_helper.php       ← Image processing functions
│   ├── get_pictures.php       ← Get all pictures
│   ├── add_picture.php        ← Add new picture
│   ├── update_picture.php     ← Update picture
│   └── delete_picture.php     ← Delete picture
└── uploads/
    ├── thumb/                  ← Thumbnails (300px)
    ├── medium/                 ← Medium size (1200px)
    └── original/               ← Original compressed (2400px)
```

---

## ✅ Benefits of Separated Files

### Easy Updates
- Change styles: Edit only `styles.css`
- Fix JavaScript: Edit only `script.js`
- Update HTML structure: Edit only `index.html`

### Better Organization
- Each file has a single purpose
- Easier to find and fix issues
- Cleaner code management

### Easier Development
- Test CSS changes without touching JavaScript
- Debug JavaScript without HTML clutter
- Share files with others more easily

---

## 📋 Upload Checklist

### Step 1: Create Folders via FTP
```
/api/
/uploads/
/uploads/thumb/
/uploads/medium/
/uploads/original/
```

### Step 2: Upload Frontend Files (Root Folder)
- [ ] `index.html` (4 KB)
- [ ] `styles.css` (8 KB)
- [ ] `script.js` (10 KB)
- [ ] `.htaccess` (1 KB)

### Step 3: Upload API Files (/api/ Folder)
- [ ] `config.php` (1 KB) - **Edit database credentials first!**
- [ ] `init.php` (2 KB)
- [ ] `image_helper.php` (4 KB)
- [ ] `get_pictures.php` (1 KB)
- [ ] `add_picture.php` (4 KB)
- [ ] `update_picture.php` (5 KB)
- [ ] `delete_picture.php` (2 KB)

### Step 4: Set Permissions
```
chmod 755 /uploads/
chmod 755 /uploads/thumb/
chmod 755 /uploads/medium/
chmod 755 /uploads/original/
chmod 755 /api/
chmod 644 /api/*.php
chmod 644 index.html
chmod 644 styles.css
chmod 644 script.js
```

---

## 🔧 Configuration Steps

### 1. Edit config.php
```php
$servername = "localhost";           // Usually localhost
$username = "your_mysql_username";   // From hosting panel
$password = "your_mysql_password";   // From hosting panel
$dbname = "studio_gallery";          // Your database name
```

### 2. Run Database Setup
Visit: `https://studio.kreisern.de/api/init.php`

You should see:
```
✓ Table 'pictures' created successfully
...
IMPORTANT: Please DELETE or RENAME this file (init.php) now for security!
```

### 3. Secure the Installation
- Delete or rename `api/init.php`

### 4. Test
Visit: `https://studio.kreisern.de`

You should see:
- Clean gallery interface
- "No pictures yet. Add your first picture to get started!"
- "Add Picture" button working

---

## 📝 Quick Reference: What Each File Does

### Frontend Files

**index.html** (123 lines)
- Main HTML structure
- Contains only markup, no styles or scripts
- Links to external CSS and JavaScript

**styles.css** (481 lines)
- All visual styling
- Responsive design rules
- Modal and gallery layouts

**script.js** (360 lines)
- All interactive functionality
- API communication
- Gallery rendering and filtering

### Backend Files

**config.php**
- Database connection
- CORS headers for API
- Used by all other PHP files

**init.php**
- Creates database table
- Run once, then delete
- Includes detailed schema

**image_helper.php**
- Image resizing function
- Handles JPEG, PNG, GIF, WebP
- Maintains aspect ratio

**get_pictures.php**
- Returns all pictures as JSON
- Includes all three image URLs
- Ordered by date (newest first)

**add_picture.php**
- Accepts image upload
- Creates 3 sizes (thumb, medium, original)
- Stores metadata in database

**update_picture.php**
- Updates metadata (always)
- Optionally replaces image
- Deletes old images when replacing

**delete_picture.php**
- Removes database record
- Deletes all 3 image files
- Requires confirmation

---

## 🎯 Testing Checklist

After upload, test each feature:

### Basic Functionality
- [ ] Page loads without errors (check browser console F12)
- [ ] CSS loads (page is styled, not plain HTML)
- [ ] JavaScript loads (buttons are clickable)

### Add Picture
- [ ] Click "Add Picture" opens modal
- [ ] Can select image file
- [ ] Image preview appears
- [ ] Can fill in all fields
- [ ] Save creates picture successfully
- [ ] Three files created in uploads folders

### View Pictures
- [ ] Gallery displays thumbnails
- [ ] Click thumbnail shows full-size modal
- [ ] Full-size image loads
- [ ] Description displays if present

### Edit Picture
- [ ] Click edit opens modal with current data
- [ ] Can edit metadata without changing image
- [ ] "Update" saves changes
- [ ] Can upload new image to replace old
- [ ] Old images deleted when replacing

### Delete Picture
- [ ] Click delete shows confirmation
- [ ] Confirms deletion
- [ ] Picture removed from gallery
- [ ] All three files deleted

### Filters
- [ ] Filter buttons appear after adding pictures
- [ ] Clicking filter shows only matching pictures
- [ ] Multiple filters work together (AND logic)
- [ ] Clear all removes filters
- [ ] Picture count updates correctly

---

## 🐛 Common Issues & Solutions

### "styles.css not found"
- Check file uploaded to root folder
- Check filename is exactly `styles.css` (case-sensitive)
- Check `<link>` tag in index.html

### "script.js not found"
- Check file uploaded to root folder
- Check filename is exactly `script.js`
- Check `<script>` tag at bottom of index.html

### "API_BASE not defined"
- JavaScript not loading
- Check browser console for errors
- Verify script.js uploaded correctly

### "Gallery not loading"
- Check browser console (F12)
- Check API files uploaded to /api/ folder
- Check database credentials in config.php
- Verify init.php was run

### Images don't display
- Check folder permissions (755 for uploads)
- Verify images exist: check /uploads/thumb/, /medium/, /original/
- Check browser console for 404 errors

---

## 📊 File Sizes & Browser Loading

### What Gets Loaded
**First page visit:**
- index.html: ~4 KB
- styles.css: ~8 KB
- script.js: ~10 KB
- **Total: ~22 KB** (very fast!)

**After adding pictures:**
- Each thumbnail: ~50 KB
- 20 thumbnails: ~1 MB total
- Still fast and responsive

**Viewing full-size:**
- Original image: ~800 KB (only when requested)
- Not loaded until user clicks

### Performance
- Fast initial load (22 KB)
- Lazy loading of images
- Efficient filtering (client-side)
- Responsive on mobile

---

## 🔄 Updating the Gallery

### To Change Styles
1. Edit `styles.css` on your computer
2. Upload via FTP (overwrites old file)
3. Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)

### To Fix JavaScript
1. Edit `script.js` on your computer
2. Upload via FTP
3. Hard refresh browser

### To Update API
1. Edit specific PHP file (e.g., `add_picture.php`)
2. Upload to `/api/` folder
3. No browser refresh needed (server-side)

### To Add Features
- CSS changes: Add to `styles.css`
- New functions: Add to `script.js`
- New API endpoints: Add new PHP file in `/api/`

---

## 🎓 Learning Resources

### Understanding the Code

**HTML (index.html):**
- Semantic structure
- Accessibility features
- Modal patterns

**CSS (styles.css):**
- CSS Grid for gallery layout
- Flexbox for filters
- Media queries for mobile
- Transitions and hover effects

**JavaScript (script.js):**
- Fetch API for HTTP requests
- DOM manipulation
- Event handling
- FormData for file uploads

**PHP (API files):**
- MySQLi for database
- Prepared statements (security)
- File handling
- Image processing with GD library

---

## ✨ You're All Set!

With separated files, you have:
- ✅ Clean, maintainable code
- ✅ Easy to update and debug
- ✅ Professional file structure
- ✅ Scalable architecture

Start uploading your pictures and enjoy your gallery! 🎨📸