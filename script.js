/**
 * Picture Gallery JavaScript
 * Handles all gallery functionality
 */

const API_BASE = 'api/';
let pictures = [];
let filters = { creator: null, year: null, project: null, color: null };
let editingId = null;
let selectedFile = null;

// Load pictures on page load
window.addEventListener('DOMContentLoaded', loadPictures);

/**
 * Load all pictures from API
 */
async function loadPictures() {
    try {
        const response = await fetch(API_BASE + 'get_pictures.php');
        pictures = await response.json();
        renderGallery();
        renderFilters();
        updateCount();
        document.getElementById('loading').style.display = 'none';
    } catch (error) {
        console.error('Error loading pictures:', error);
        document.getElementById('loading').innerHTML = 'Error loading pictures. Please refresh the page.';
    }
}

/**
 * Render gallery grid with filtered pictures
 */
function renderGallery() {
    const gallery = document.getElementById('gallery');
    const emptyState = document.getElementById('empty-state');
    const filtersSection = document.getElementById('filters');
    
    const filtered = getFilteredPictures();

    if (pictures.length === 0) {
        gallery.style.display = 'none';
        filtersSection.style.display = 'none';
        emptyState.style.display = 'block';
        emptyState.innerHTML = `
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <p>No pictures yet. Add your first picture to get started!</p>
            <button class="btn-primary" onclick="openAddModal()">+ Add Picture</button>
        `;
        return;
    }

    filtersSection.style.display = 'block';

    if (filtered.length === 0) {
        gallery.style.display = 'none';
        emptyState.style.display = 'block';
        emptyState.innerHTML = '<p>No pictures match your filters</p>';
        return;
    }

    gallery.style.display = 'grid';
    emptyState.style.display = 'none';

    gallery.innerHTML = filtered.map(pic => `
        <div class="picture-card">
            <img src="${pic.url_thumb}" alt="${pic.creator} - ${pic.project}" onclick="viewFullImage(${pic.id})">
            <div class="picture-actions">
                <button class="icon-btn" onclick="viewFullImage(${pic.id})" title="View full size">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
                <button class="icon-btn" onclick="editPicture(${pic.id})" title="Edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="icon-btn delete" onclick="deletePicture(${pic.id})" title="Delete">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
            <div class="picture-info">
                <div class="creator">${pic.creator}</div>
                <div class="project">${pic.project}</div>
                <div class="date">${pic.date}</div>
                ${pic.description ? `<div class="description">${escapeHtml(pic.description)}</div>` : ''}
                ${pic.color ? `<span class="color-tag">${pic.color}</span>` : ''}
            </div>
        </div>
    `).join('');
}

/**
 * Render filter buttons for each category
 */
function renderFilters() {
    if (pictures.length === 0) return;

    const creators = [...new Set(pictures.map(p => p.creator))].sort();
    const years = [...new Set(pictures.map(p => p.date.split('-')[0]))].sort().reverse();
    const projects = [...new Set(pictures.map(p => p.project))].sort();
    const colors = [...new Set(pictures.map(p => p.color).filter(Boolean))].sort();

    document.getElementById('filter-creator').innerHTML = creators.map(c => 
        `<button class="filter-btn ${filters.creator === c ? 'active' : ''}" onclick="toggleFilter('creator', '${escapeHtml(c)}')">${escapeHtml(c)}</button>`
    ).join('');

    document.getElementById('filter-year').innerHTML = years.map(y => 
        `<button class="filter-btn ${filters.year === y ? 'active' : ''}" onclick="toggleFilter('year', '${y}')">${y}</button>`
    ).join('');

    document.getElementById('filter-project').innerHTML = projects.map(p => 
        `<button class="filter-btn ${filters.project === p ? 'active' : ''}" onclick="toggleFilter('project', '${escapeHtml(p)}')">${escapeHtml(p)}</button>`
    ).join('');

    document.getElementById('filter-color').innerHTML = colors.map(c => 
        `<button class="filter-btn ${filters.color === c ? 'active' : ''}" onclick="toggleFilter('color', '${escapeHtml(c)}')">${escapeHtml(c)}</button>`
    ).join('');

    updateClearButton();
}

/**
 * Toggle filter on/off
 */
function toggleFilter(category, value) {
    filters[category] = filters[category] === value ? null : value;
    renderGallery();
    renderFilters();
    updateCount();
}

/**
 * Clear all active filters
 */
function clearFilters() {
    filters = { creator: null, year: null, project: null, color: null };
    renderGallery();
    renderFilters();
    updateCount();
}

/**
 * Update visibility of clear filters button
 */
function updateClearButton() {
    const hasActiveFilters = Object.values(filters).some(f => f !== null);
    document.getElementById('clear-filters').style.display = hasActiveFilters ? 'block' : 'none';
}

/**
 * Get filtered pictures based on active filters
 */
function getFilteredPictures() {
    return pictures.filter(pic => {
        if (filters.creator && pic.creator !== filters.creator) return false;
        if (filters.year && pic.date.split('-')[0] !== filters.year) return false;
        if (filters.project && pic.project !== filters.project) return false;
        if (filters.color && pic.color !== filters.color) return false;
        return true;
    });
}

/**
 * Update picture count display
 */
function updateCount() {
    const filtered = getFilteredPictures();
    document.getElementById('picture-count').textContent = 
        `Showing ${filtered.length} of ${pictures.length} pictures`;
}

/**
 * Open modal for adding new picture
 */
function openAddModal() {
    editingId = null;
    selectedFile = null;
    document.getElementById('modal-title').textContent = 'Add Picture';
    document.getElementById('save-text').textContent = 'Add';
    document.getElementById('input-creator').value = '';
    document.getElementById('input-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('input-project').value = '';
    document.getElementById('input-color').value = '';
    document.getElementById('input-description').value = '';
    document.getElementById('upload-area').className = 'upload-area';
    document.getElementById('upload-area').innerHTML = `
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
        <p>Click to upload image</p>
    `;
    document.getElementById('upload-note').style.display = 'none';
    document.getElementById('modal').classList.add('show');
}

/**
 * Open modal for editing existing picture
 */
function editPicture(id) {
    const picture = pictures.find(p => p.id === id);
    if (!picture) return;

    editingId = id;
    selectedFile = null;
    document.getElementById('modal-title').textContent = 'Edit Picture';
    document.getElementById('save-text').textContent = 'Update';
    document.getElementById('input-creator').value = picture.creator;
    document.getElementById('input-date').value = picture.date;
    document.getElementById('input-project').value = picture.project;
    document.getElementById('input-color').value = picture.color || '';
    document.getElementById('input-description').value = picture.description || '';
    
    const uploadArea = document.getElementById('upload-area');
    uploadArea.className = 'upload-area has-image';
    uploadArea.innerHTML = `
        <div class="preview-container">
            <img src="${picture.url_thumb}" alt="Current image">
            <button class="btn-remove-image" onclick="event.stopPropagation(); removeImage()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.getElementById('upload-note').style.display = 'block';
    document.getElementById('modal').classList.add('show');
}

/**
 * Close add/edit modal
 */
function closeModal() {
    document.getElementById('modal').classList.remove('show');
}

/**
 * Handle image file selection
 */
function handleImageSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedFile = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        const uploadArea = document.getElementById('upload-area');
        uploadArea.className = 'upload-area has-image';
        uploadArea.innerHTML = `
            <div class="preview-container">
                <img src="${e.target.result}" alt="Preview">
                <button class="btn-remove-image" onclick="event.stopPropagation(); removeImage()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        `;
    };
    reader.readAsDataURL(file);
}

/**
 * Remove selected image
 */
function removeImage() {
    selectedFile = null;
    const uploadArea = document.getElementById('upload-area');
    uploadArea.className = 'upload-area';
    uploadArea.innerHTML = `
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
        <p>Click to upload image</p>
    `;
    document.getElementById('file-input').value = '';
}

/**
 * Save picture (add or update)
 */
async function savePicture() {
    const creator = document.getElementById('input-creator').value.trim();
    const date = document.getElementById('input-date').value;
    const project = document.getElementById('input-project').value.trim();
    const color = document.getElementById('input-color').value.trim();
    const description = document.getElementById('input-description').value.trim();

    if (!creator || !date || !project) {
        alert('Please fill in all required fields (Creator, Date, Project)');
        return;
    }

    if (!editingId && !selectedFile) {
        alert('Please select an image');
        return;
    }

    const formData = new FormData();
    formData.append('creator', creator);
    formData.append('date', date);
    formData.append('project', project);
    formData.append('color', color);
    formData.append('description', description);

    if (selectedFile) {
        formData.append('image', selectedFile);
    }

    try {
        const url = editingId 
            ? API_BASE + 'update_picture.php' 
            : API_BASE + 'add_picture.php';
        
        if (editingId) {
            formData.append('id', editingId);
        }

        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success || result.id) {
            closeModal();
            await loadPictures();
        } else {
            alert('Error: ' + (result.error || 'Failed to save picture'));
        }
    } catch (error) {
        console.error('Error saving picture:', error);
        alert('Error saving picture. Please try again.');
    }
}

/**
 * Delete picture after confirmation
 */
async function deletePicture(id) {
    if (!confirm('Are you sure you want to delete this picture?')) return;

    try {
        const response = await fetch(API_BASE + 'delete_picture.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const result = await response.json();
        
        if (result.success) {
            await loadPictures();
        } else {
            alert('Error: ' + (result.error || 'Failed to delete picture'));
        }
    } catch (error) {
        console.error('Error deleting picture:', error);
        alert('Error deleting picture. Please try again.');
    }
}

/**
 * View full-size image in modal
 */
function viewFullImage(id) {
    const picture = pictures.find(p => p.id === id);
    if (!picture) return;
    
    document.getElementById('fullsize-image').src = picture.url_original;
    document.getElementById('fullsize-info').innerHTML = `
        <strong>${escapeHtml(picture.creator)}</strong> - ${escapeHtml(picture.project)}<br>
        ${picture.date}${picture.color ? ' • ' + escapeHtml(picture.color) : ''}<br>
        ${picture.description ? '<br>' + escapeHtml(picture.description) : ''}
    `;
    document.getElementById('fullsize-modal').classList.add('show');
}

/**
 * Close full-size image modal
 */
function closeFullsize() {
    document.getElementById('fullsize-modal').classList.remove('show');
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}