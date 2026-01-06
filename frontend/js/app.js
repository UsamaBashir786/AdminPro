// ============================================
// PRODUCT MANAGEMENT SYSTEM - Main JavaScript
// Developed by: Usama
// Last Updated: 2026
// Version: 1.0
// ============================================

// Let's set up our API endpoints first
// These point to our backend services
const API_ENDPOINT = '../api';          // For public APIs
const SERVER_URL = '../backend';       // For admin operations

// Global state variables - we'll track everything here
let loggedInUser = null;               // Current user info
let activeCategory = 0;                // Which category is selected
let allCategoriesList = [];            // All available categories
let productItems = [];                 // Products in current category

// Debug mode - handy for development
const DEBUG_MODE = true;
if (DEBUG_MODE) {
    console.log('🚀 App initialized at:', new Date().toLocaleTimeString());
}

// ============================================
// APP STARTUP - Runs when page loads
// ============================================

// When page finishes loading, do these things
document.addEventListener('DOMContentLoaded', function() {
    // First check if user is logged in
    verifyLoginStatus();
    
    // Load the category list for sidebar
    getCategories();
    
    // Set up all click events and listeners
    setupAllEventHandlers();
    
    // My custom initialization stuff
    customAppSetup();
});

// ============================================
// USER MANAGEMENT FUNCTIONS
// ============================================

// Check if someone is logged in
async function verifyLoginStatus() {
    try {
        // Make API call to check session
        const apiResponse = await fetch(`${API_ENDPOINT}/user.php?action=session`);
        const resultData = await apiResponse.json();
        
        if (resultData.logged_in) {
            // User is logged in
            loggedInUser = resultData.user;
            updateHeaderDisplay(true);
            
            // If it's a super admin, show admin controls
            if (loggedInUser.role === 'super') {
                const adminPanel = document.getElementById('adminPanel');
                if (adminPanel) {
                    adminPanel.classList.add('visible');
                }
            }
        } else {
            // No user logged in
            updateHeaderDisplay(false);
        }
    } catch (err) {
        console.warn('⚠️ Could not check login status:', err);
        updateHeaderDisplay(false);
    }
}

// Update the top header based on login status
function updateHeaderDisplay(userLoggedIn) {
    const userInfoBox = document.getElementById('userInfo');
    const loginButtons = document.getElementById('authButtons');
    
    if (!userInfoBox || !loginButtons) return;
    
    if (userLoggedIn && loggedInUser) {
        // Show welcome message and logout button
        userInfoBox.innerHTML = `Hi <strong>${loggedInUser.name}</strong> <span style="opacity:0.7">(${loggedInUser.role})</span>`;
        loginButtons.innerHTML = `<button class="btn btn-secondary" onclick="userLogout()">Sign Out</button>`;
    } else {
        // Show login/signup buttons
        userInfoBox.innerHTML = '';
        loginButtons.innerHTML = `
            <a href="login.html" class="btn btn-primary">Login</a>
            <a href="signup.html" class="btn btn-secondary" style="margin-left:8px">Register</a>
        `;
    }
}

// Logout the current user
async function userLogout() {
    try {
        // Call logout API
        await fetch(`${SERVER_URL}/logout.php`, {
            method: 'POST'
        });
        
        // Clear user data
        loggedInUser = null;
        
        // Reload page to reflect changes
        window.location.reload();
    } catch (err) {
        console.error('Logout failed:', err);
        alert('Sorry, logout failed. Try again.');
    }
}

// ============================================
// CATEGORY FUNCTIONS
// ============================================

// Fetch all categories from server
async function getCategories() {
    const categoryContainer = document.getElementById('categoryList');
    if (!categoryContainer) return;
    
    categoryContainer.innerHTML = '<li class="loading">Loading categories...</li>';
    
    try {
        const response = await fetch(`${API_ENDPOINT}/category.php`);
        const jsonData = await response.json();
        
        if (jsonData.success) {
            allCategoriesList = jsonData.categories;
            
            // Check if we have any categories
            if (allCategoriesList.length === 0) {
                categoryContainer.innerHTML = '<li class="empty-state">No categories yet</li>';
                return;
            }
            
            // Clear loading message
            categoryContainer.innerHTML = '';
            
            // Create list items for each category
            allCategoriesList.forEach(function(cat) {
                const listItem = document.createElement('li');
                listItem.className = 'category-item';
                listItem.textContent = cat.name;
                listItem.title = cat.description || cat.name;
                
                // When clicked, select this category
                listItem.onclick = function(event) {
                    chooseCategory(cat.id, event);
                };
                
                categoryContainer.appendChild(listItem);
            });
            
            if (DEBUG_MODE) {
                console.log(`✅ Loaded ${allCategoriesList.length} categories`);
            }
        } else {
            categoryContainer.innerHTML = '<li class="empty-state">Could not load categories</li>';
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        categoryContainer.innerHTML = '<li class="empty-state">Network error</li>';
    }
}

// Choose a category and load its products
async function chooseCategory(categoryId, clickEvent) {
    activeCategory = categoryId;
    
    // Remove active class from all items
    const allCategoryItems = document.querySelectorAll('.category-item');
    allCategoryItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked item
    if (clickEvent && clickEvent.target) {
        clickEvent.target.classList.add('active');
    }
    
    // Now load products for this category
    await fetchCategoryProducts(categoryId);
}

// ============================================
// PRODUCT FUNCTIONS
// ============================================

// Get products for a specific category
async function fetchCategoryProducts(catId) {
    const productsGrid = document.getElementById('productGrid');
    if (!productsGrid) return;
    
    // Show loading message
    productsGrid.innerHTML = '<div class="loading"><i data-lucide="loader-2" class="animate-spin"></i> Loading products...</div>';
    
    try {
        const apiResult = await fetch(`${API_ENDPOINT}/product.php?category_id=${catId}`);
        const resultJson = await apiResult.json();
        
        if (resultJson.success) {
            productItems = resultJson.products;
            const canSeePrices = resultJson.show_price;  // Can user see prices?
            
            // Check if category has products
            if (productItems.length === 0) {
                productsGrid.innerHTML = `
                    <div class="empty-state">
                        <i data-lucide="package-open" width="48" height="48"></i>
                        <p>No products in this category</p>
                    </div>
                `;
                return;
            }
            
            // Clear grid and add products
            productsGrid.innerHTML = '';
            
            productItems.forEach(prod => {
                const productCard = buildProductCard(prod, canSeePrices);
                productsGrid.appendChild(productCard);
            });
            
            // Refresh Lucide icons
            if (window.lucide) {
                lucide.createIcons();
            }
            
            if (DEBUG_MODE) {
                console.log(`📦 Loaded ${productItems.length} products for category ${catId}`);
            }
        } else {
            productsGrid.innerHTML = '<div class="empty-state">Failed to load products</div>';
        }
    } catch (err) {
        console.error('Error loading products:', err);
        productsGrid.innerHTML = '<div class="empty-state">Could not load products</div>';
    }
}

// Build HTML card for a product
function buildProductCard(productData, showPriceFlag) {
    const cardDiv = document.createElement('div');
    cardDiv.className = 'product-card';
    
    // Handle product image - use placeholder if none
    let productImageUrl;
    if (productData.image && productData.image.trim() !== '') {
        productImageUrl = `../${productData.image}`;
    } else {
        // SVG placeholder for missing images
        productImageUrl = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23f5f5f5" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23aaa"%3ENo Image%3C/text%3E%3C/svg%3E';
    }
    
    // Build card HTML
    const priceHtml = showPriceFlag && productData.price 
        ? `<div class="product-price">$${parseFloat(productData.price).toFixed(2)}</div>`
        : '<div class="price-placeholder">Login to view price</div>';
    
    const descriptionHtml = productData.description 
        ? `<div class="product-description">${safeHTML(productData.description)}</div>`
        : '';
    
    cardDiv.innerHTML = `
        <img src="${productImageUrl}" alt="${productData.name}" class="product-image" 
             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23f5f5f5%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23aaa%22%3ENo Image%3C/text%3E%3C/svg%3E'">
        
        <div class="product-info">
            <div class="product-name">${safeHTML(productData.name)}</div>
            <div class="product-sku">#${safeHTML(productData.sku)}</div>
            ${descriptionHtml}
            ${priceHtml}
        </div>
    `;
    
    return cardDiv;
}

// ============================================
// MODAL FUNCTIONS (Add Category/Product)
// ============================================

// Open the "Add Category" modal
function openCategoryModal() {
    // Check permissions
    if (!loggedInUser || loggedInUser.role !== 'super') {
        alert('Only administrators can add categories');
        return;
    }
    
    // Reset form
    const catForm = document.getElementById('categoryForm');
    if (catForm) catForm.reset();
    
    // Hide any old messages
    const msgBox = document.getElementById('categoryMessage');
    if (msgBox) msgBox.style.display = 'none';
    
    // Hide loading indicator
    const loadingEl = document.getElementById('categoryLoading');
    if (loadingEl) loadingEl.classList.remove('active');
    
    // Show the modal
    const modalEl = document.getElementById('categoryModal');
    if (modalEl) {
        modalEl.classList.add('active');
        
        // Focus on first input after a small delay
        setTimeout(function() {
            const nameInput = document.getElementById('categoryName');
            if (nameInput) nameInput.focus();
        }, 150);
    }
    
    // Make sure icons are visible
    if (window.lucide) {
        lucide.createIcons();
    }
}

// Close the category modal
function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Clean up form
    const form = document.getElementById('categoryForm');
    if (form) form.reset();
}

// Open the "Add Product" modal
function openProductModal() {
    // Check if user can add products
    if (!loggedInUser) {
        alert('Please login first');
        window.location.href = 'login.html';
        return;
    }
    
    if (loggedInUser.role !== 'super' && loggedInUser.role !== 'admin') {
        alert('You need admin access to add products');
        return;
    }
    
    // Reset the form
    const prodForm = document.getElementById('productForm');
    if (prodForm) prodForm.reset();
    
    // Clear messages
    const msgBox = document.getElementById('productMessage');
    if (msgBox) msgBox.style.display = 'none';
    
    // Clear loading indicator
    const loadingEl = document.getElementById('productLoading');
    if (loadingEl) loadingEl.classList.remove('active');
    
    // Clear file name display
    const fileNameEl = document.getElementById('imageFileName');
    if (fileNameEl) fileNameEl.textContent = '';
    
    // Fill category dropdown
    const catSelect = document.getElementById('productCategory');
    if (catSelect) {
        catSelect.innerHTML = '<option value="">-- Choose Category --</option>';
        
        allCategoriesList.forEach(function(cat) {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            
            // Select current category if set
            if (activeCategory && cat.id === activeCategory) {
                option.selected = true;
            }
            
            catSelect.appendChild(option);
        });
    }
    
    // Show modal
    const modalEl = document.getElementById('productModal');
    if (modalEl) {
        modalEl.classList.add('active');
        
        // Focus on product name field
        setTimeout(function() {
            const nameInput = document.getElementById('productName');
            if (nameInput) nameInput.focus();
        }, 150);
    }
    
    // Refresh icons
    if (window.lucide) {
        lucide.createIcons();
    }
}

// Close product modal
function closeProductModal() {
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Reset form
    const form = document.getElementById('productForm');
    if (form) form.reset();
}

// ============================================
// FORM HANDLERS
// ============================================

// Save new category
async function saveNewCategory(event) {
    if (event) event.preventDefault();
    
    const catName = document.getElementById('categoryName')?.value.trim();
    const catDesc = document.getElementById('categoryDescription')?.value.trim();
    
    // Basic validation
    if (!catName || catName.length < 2) {
        showModalMessage('categoryMessage', 'Category name is required (min 2 chars)', 'error');
        return;
    }
    
    // Show loading
    const loadingEl = document.getElementById('categoryLoading');
    if (loadingEl) loadingEl.classList.add('active');
    
    try {
        const response = await fetch(`${SERVER_URL}/categories.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: catName,
                description: catDesc || ''
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Success!
            showModalMessage('categoryMessage', '✅ Category saved successfully!', 'success');
            
            // Close modal and refresh after delay
            setTimeout(function() {
                closeCategoryModal();
                getCategories(); // Reload category list
            }, 1200);
        } else {
            // Error from server
            showModalMessage('categoryMessage', '❌ ' + (result.message || 'Save failed'), 'error');
        }
    } catch (err) {
        console.error('Save category error:', err);
        showModalMessage('categoryMessage', '❌ Network error - try again', 'error');
    } finally {
        // Hide loading indicator
        if (loadingEl) loadingEl.classList.remove('active');
    }
}

// Save new product
async function saveNewProduct(event) {
    if (event) event.preventDefault();
    
    // Get form values
    const prodName = document.getElementById('productName')?.value.trim();
    const prodCategory = document.getElementById('productCategory')?.value;
    const prodSKU = document.getElementById('productSKU')?.value.trim();
    const prodPrice = document.getElementById('productPrice')?.value;
    const prodDesc = document.getElementById('productDescription')?.value.trim();
    const prodImage = document.getElementById('productImage')?.files[0];
    
    // Validation
    if (!prodName || !prodCategory || !prodSKU || !prodPrice) {
        showModalMessage('productMessage', 'Please fill all required fields', 'error');
        return;
    }
    
    // Validate price
    const priceNum = parseFloat(prodPrice);
    if (isNaN(priceNum) || priceNum < 0) {
        showModalMessage('productMessage', 'Enter a valid price (number >= 0)', 'error');
        return;
    }
    
    // Validate image if provided
    if (prodImage) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!allowedTypes.includes(prodImage.type)) {
            showModalMessage('productMessage', 'Image must be JPG, PNG, GIF, or WebP', 'error');
            return;
        }
        
        if (prodImage.size > maxSize) {
            showModalMessage('productMessage', 'Image too large (max 5MB)', 'error');
            return;
        }
    }
    
    // Show loading
    const loadingEl = document.getElementById('productLoading');
    if (loadingEl) loadingEl.classList.add('active');
    
    try {
        // Prepare form data
        const dataToSend = new FormData();
        dataToSend.append('name', prodName);
        dataToSend.append('category_id', prodCategory);
        dataToSend.append('sku', prodSKU);
        dataToSend.append('price', priceNum.toFixed(2));
        
        if (prodDesc) dataToSend.append('description', prodDesc);
        if (prodImage) dataToSend.append('image', prodImage);
        
        // Send to server
        const response = await fetch(`${SERVER_URL}/products.php`, {
            method: 'POST',
            body: dataToSend
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Success message
            showModalMessage('productMessage', '✅ Product added successfully!', 'success');
            
            // Close and refresh after delay
            setTimeout(function() {
                closeProductModal();
                
                // If we're viewing the same category, refresh products
                if (activeCategory && activeCategory.toString() === prodCategory) {
                    fetchCategoryProducts(activeCategory);
                }
            }, 1200);
        } else {
            showModalMessage('productMessage', '❌ ' + (result.message || 'Save failed'), 'error');
        }
    } catch (err) {
        console.error('Save product error:', err);
        showModalMessage('productMessage', '❌ Network error - check connection', 'error');
    } finally {
        // Hide loading
        if (loadingEl) loadingEl.classList.remove('active');
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Show message in modal
function showModalMessage(elementId, messageText, messageType) {
    const msgElement = document.getElementById(elementId);
    if (!msgElement) return;
    
    msgElement.textContent = messageText;
    msgElement.className = `message ${messageType}`;
    msgElement.style.display = 'block';
}

// Safe HTML - prevent XSS attacks
function safeHTML(text) {
    if (!text) return '';
    
    const tempDiv = document.createElement('div');
    tempDiv.textContent = text;
    return tempDiv.innerHTML;
}

// Setup all event handlers
function setupAllEventHandlers() {
    // Category form submission
    const catForm = document.getElementById('categoryForm');
    if (catForm) {
        catForm.addEventListener('submit', saveNewCategory);
    }
    
    // Product form submission
    const prodForm = document.getElementById('productForm');
    if (prodForm) {
        prodForm.addEventListener('submit', saveNewProduct);
    }
    
    // Product image file selection
    const imageInput = document.getElementById('productImage');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const fileNameDisplay = document.getElementById('imageFileName');
            if (!fileNameDisplay) return;
            
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = 'File: ' + this.files[0].name;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    }
    
    // Close modals when clicking outside
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.id === 'categoryModal') {
                    closeCategoryModal();
                } else if (this.id === 'productModal') {
                    closeProductModal();
                }
            }
        });
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCategoryModal();
            closeProductModal();
        }
    });
}

// Custom app setup - add your own stuff here
function customAppSetup() {
    // This is where I add my personal touches
    
    // Log startup time
    if (DEBUG_MODE) {
        console.log('🛠️ Custom setup complete');
    }
    
    // You can add more custom initialization here
    // Example: Set default values, check localStorage, etc.
}

// ============================================
// GLOBAL FUNCTIONS (accessible from HTML)
// ============================================

// These functions are called from onclick attributes
window.showCategoryForm = openCategoryModal;
window.showProductForm = openProductModal;
window.closeCategoryModal = closeCategoryModal;
window.closeProductModal = closeProductModal;
window.logout = userLogout;

// Make safeHTML available globally if needed
window.safeHTML = safeHTML;