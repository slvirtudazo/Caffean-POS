/**
 * Purge Coffee Shop - Main JavaScript File
 * This file contains all client-side functionality including shopping cart operations,
 * favorite product management, search functionality, and UI interactions.
 * It now tracks user interactions for calculating best sellers.
 */

// Initialize cart from localStorage or create empty cart
let cart = JSON.parse(localStorage.getItem('coffeeCart')) || [];

/**
 * Add product to shopping cart
 * This function submits the product to the server-side cart handler
 * and tracks the interaction for best sellers calculation
 * @param {number} productId - The ID of the product to add to cart
 */
function addToCart(productId) {
    // Create form data to send to server
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('ajax', '1');
    
    // Send AJAX request to add to cart
    fetch('php/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            animateCartIcon();
            
            // Track add-to-cart interaction for best sellers
            trackInteraction(productId, 'add_to_cart');
            
            // Update cart count if returned
            if (data.cart_count) {
                updateCartCountDisplay(data.cart_count);
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showNotification('Error adding product to cart', 'error');
    });
}

/**
 * Update cart count display with a specific number
 * @param {number} count - The number of items in cart
 */
function updateCartCountDisplay(count) {
    const cartIcon = document.querySelector('.fa-shopping-cart').parentElement;
    let badge = cartIcon.querySelector('.cart-badge');
    
    if (!badge && count > 0) {
        badge = document.createElement('span');
        badge.className = 'cart-badge';
        badge.style.cssText = `
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--burgundy-wine);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        `;
        cartIcon.style.position = 'relative';
        cartIcon.appendChild(badge);
    }
    
    if (badge) {
        badge.textContent = count;
        if (count === 0) {
            badge.remove();
        }
    }
}

/**
 * Remove product from cart
 * @param {number} productId - The ID of the product to remove
 */
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    localStorage.setItem('coffeeCart', JSON.stringify(cart));
    updateCartCount();
    showNotification('Product removed from cart', 'info');
}

/**
 * Update product quantity in cart
 * @param {number} productId - The ID of the product
 * @param {number} newQuantity - The new quantity to set
 */
function updateQuantity(productId, newQuantity) {
    const item = cart.find(item => item.id === productId);
    if (item && newQuantity > 0) {
        item.quantity = newQuantity;
        localStorage.setItem('coffeeCart', JSON.stringify(cart));
        updateCartCount();
    } else if (newQuantity <= 0) {
        removeFromCart(productId);
    }
}

/**
 * Update cart count display in navigation
 * Shows the total number of items in the cart
 */
function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    
    cartCountElements.forEach(element => {
        element.textContent = totalItems;
        // Show or hide cart count badge based on item count
        if (totalItems > 0) {
            element.style.display = 'inline-block';
        } else {
            element.style.display = 'none';
        }
    });
}

/**
 * Animate cart icon when item is added
 * Provides visual feedback to user
 */
function animateCartIcon() {
    const cartIcon = document.querySelector('.fa-shopping-cart');
    if (cartIcon) {
        cartIcon.classList.add('bounce');
        setTimeout(() => {
            cartIcon.classList.remove('bounce');
        }, 500);
    }
}

/**
 * Toggle favorite/wishlist status for products
 * Tracks the interaction for best sellers calculation
 * @param {number} productId - The ID of the product to favorite
 * @param {HTMLElement} element - The heart icon element clicked
 */
function toggleFavorite(productId, element) {
    // Get favorites from localStorage
    let favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];
    
    const index = favorites.indexOf(productId);
    
    if (index > -1) {
        // Remove from favorites
        favorites.splice(index, 1);
        element.classList.remove('fas');
        element.classList.add('far');
        showNotification('Removed from favorites', 'info');
        
        // Track unfavorite (negative interaction)
        trackInteraction(productId, 'unfavorite');
    } else {
        // Add to favorites
        favorites.push(productId);
        element.classList.remove('far');
        element.classList.add('fas');
        showNotification('Added to favorites!', 'success');
        
        // Track favorite interaction for best sellers
        trackInteraction(productId, 'favorite');
    }
    
    // Save to localStorage
    localStorage.setItem('coffeeFavorites', JSON.stringify(favorites));
}

/**
 * Track user interactions with products for best sellers calculation
 * Sends interaction data to the server to update popularity scores
 * @param {number} productId - The ID of the product
 * @param {string} interactionType - Type of interaction: 'favorite', 'unfavorite', 'add_to_cart'
 */
function trackInteraction(productId, interactionType) {
    // Create form data
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('interaction_type', interactionType);
    
    // Send to server asynchronously (don't wait for response)
    fetch('php/track_interaction.php', {
        method: 'POST',
        body: formData
    })
    .catch(error => {
        // Silent fail - interaction tracking shouldn't interrupt user experience
        console.log('Interaction tracking error:', error);
    });
}

/**
 * Show notification to user
 * @param {string} message - The message to display
 * @param {string} type - Type of notification (success, error, info)
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background-color: ${type === 'success' ? '#2A0000' : type === 'error' ? '#c33' : '#5B1312'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

/**
 * Create and show search overlay
 */
function showSearchOverlay() {
    // Check if overlay already exists
    let overlay = document.getElementById('searchOverlay');
    
    if (!overlay) {
        // Create overlay
        overlay = document.createElement('div');
        overlay.id = 'searchOverlay';
        overlay.className = 'search-overlay';
        overlay.innerHTML = `
            <div class="search-modal">
                <div class="search-header">
                    <h3>Search for products</h3>
                    <button class="search-close" onclick="closeSearchOverlay()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon-input"></i>
                    <input type="text" 
                           id="searchInput" 
                           class="search-input" 
                           placeholder="Type to search products..."
                           autocomplete="off">
                </div>
                <div class="search-results" id="searchResults">
                    <div class="search-hint">Start typing to search for products...</div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Add event listener for input
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function(e) {
            searchProducts(e.target.value);
        });
        
        // Close on overlay click
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeSearchOverlay();
            }
        });
    }
    
    // Show overlay
    overlay.style.display = 'flex';
    setTimeout(() => {
        overlay.classList.add('active');
        document.getElementById('searchInput').focus();
    }, 10);
}

/**
 * Close search overlay
 */
function closeSearchOverlay() {
    const overlay = document.getElementById('searchOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.getElementById('searchInput').value = '';
            document.getElementById('searchResults').innerHTML = '<div class="search-hint">Start typing to search for products...</div>';
        }, 300);
    }
}

/**
 * Search functionality for products - now with live results
 * @param {string} searchTerm - The search query
 */
function searchProducts(searchTerm) {
    const products = document.querySelectorAll('.product-card');
    const searchLower = searchTerm.toLowerCase().trim();
    const resultsContainer = document.getElementById('searchResults');
    
    if (!resultsContainer) {
        // Fallback for pages without search overlay
        products.forEach(product => {
            const productName = product.querySelector('.product-name').textContent.toLowerCase();
            const productDesc = product.querySelector('.product-description').textContent.toLowerCase();
            
            if (searchLower === '' || productName.includes(searchLower) || productDesc.includes(searchLower)) {
                product.style.display = 'block';
                product.style.animation = 'fadeIn 0.3s ease';
            } else {
                product.style.display = 'none';
            }
        });
        return;
    }
    
    // Clear previous results
    resultsContainer.innerHTML = '';
    
    if (searchLower === '') {
        resultsContainer.innerHTML = '<div class="search-hint">Start typing to search for products...</div>';
        return;
    }
    
    let matchCount = 0;
    
    products.forEach(product => {
        const productName = product.querySelector('.product-name').textContent;
        const productDesc = product.querySelector('.product-description').textContent;
        const productPrice = product.querySelector('.product-price').textContent;
        const productId = product.dataset.productId;
        
        if (productName.toLowerCase().includes(searchLower) || 
            productDesc.toLowerCase().includes(searchLower)) {
            matchCount++;
            
            const resultItem = document.createElement('div');
            resultItem.className = 'search-result-item';
            resultItem.innerHTML = `
                <div class="result-info">
                    <h4>${highlightMatch(productName, searchTerm)}</h4>
                    <p>${highlightMatch(productDesc.substring(0, 100) + '...', searchTerm)}</p>
                </div>
                <div class="result-actions">
                    <span class="result-price">${productPrice}</span>
                    <button class="btn-order-small" onclick="addToCart(${productId}); closeSearchOverlay();">
                        <i class="fas fa-shopping-cart"></i> Add
                    </button>
                </div>
            `;
            resultsContainer.appendChild(resultItem);
        }
    });
    
    if (matchCount === 0) {
        resultsContainer.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No products found for "${searchTerm}"</p>
                <small>Try searching with different keywords</small>
            </div>
        `;
    }
}

/**
 * Highlight matching text in search results
 * @param {string} text - The text to highlight
 * @param {string} query - The search query
 */
function highlightMatch(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

/**
 * Initialize search functionality when page loads
 */
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count on page load
    updateCartCount();
    
    // Setup search functionality with new overlay
    const searchIcon = document.querySelector('.fa-search');
    if (searchIcon) {
        searchIcon.addEventListener('click', function(e) {
            e.preventDefault();
            showSearchOverlay();
        });
        
        // Add hover effect
        searchIcon.parentElement.style.cursor = 'pointer';
    }
    
    // Setup favorite icons
    const favoriteIcons = document.querySelectorAll('.favorite-icon i');
    favoriteIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const productCard = this.closest('.product-card');
            const productId = productCard.dataset.productId;
            if (productId) {
                toggleFavorite(parseInt(productId), this);
            }
        });
    });
    
    // Load favorites on page load
    loadFavorites();
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Close search overlay on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSearchOverlay();
        }
    });
});

/**
 * Load favorite products from localStorage and mark them visually
 */
function loadFavorites() {
    const favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];
    
    favorites.forEach(productId => {
        const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
        if (productCard) {
            const heartIcon = productCard.querySelector('.favorite-icon i');
            if (heartIcon) {
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
            }
        }
    });
}

/**
 * Format price with Philippine Peso symbol
 * @param {number} price - The price to format
 * @returns {string} Formatted price string
 */
function formatPrice(price) {
    return '₱ ' + parseFloat(price).toFixed(2);
}

/**
 * Calculate cart total
 * @returns {number} Total price of all items in cart
 */
function calculateCartTotal() {
    // This would need product price data from the server
    // For now, returns the count
    return cart.reduce((total, item) => total + item.quantity, 0);
}

// Add CSS animations and search overlay styles dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .bounce {
        animation: bounce 0.5s ease;
    }
    
    @keyframes bounce {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
    }
    
    /* Search Overlay Styles */
    .search-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(42, 0, 0, 0.8);
        z-index: 9999;
        align-items: flex-start;
        justify-content: center;
        padding-top: 100px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .search-overlay.active {
        opacity: 1;
    }
    
    .search-modal {
        background: #F5F1E8;
        border-radius: 16px;
        width: 90%;
        max-width: 700px;
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideInDown 0.3s ease-out;
    }
    
    @keyframes slideInDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .search-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        background: #2A0000;
        color: #F5F1E8;
    }
    
    .search-header h3 {
        margin: 0;
        font-family: 'EB Garamond', serif;
        font-size: 1.5rem;
    }
    
    .search-close {
        background: transparent;
        border: none;
        color: #F5F1E8;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        transition: transform 0.2s ease;
    }
    
    .search-close:hover {
        transform: scale(1.1);
    }
    
    .search-input-wrapper {
        position: relative;
        padding: 1.5rem;
        background: white;
        border-bottom: 2px solid #E2D9C8;
    }
    
    .search-icon-input {
        position: absolute;
        left: 2rem;
        top: 50%;
        transform: translateY(-50%);
        color: #5B1312;
        font-size: 1.2rem;
    }
    
    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid #E2D9C8;
        border-radius: 12px;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
        outline: none;
        transition: border-color 0.3s ease;
    }
    
    .search-input:focus {
        border-color: #5B1312;
    }
    
    .search-results {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #F5F1E8;
    }
    
    .search-hint, .no-results {
        text-align: center;
        padding: 3rem 2rem;
        color: #3C1518;
    }
    
    .no-results i {
        font-size: 3rem;
        color: #E2D9C8;
        margin-bottom: 1rem;
    }
    
    .no-results p {
        font-size: 1.125rem;
        margin-bottom: 0.5rem;
        color: #2A0000;
        font-weight: 600;
    }
    
    .no-results small {
        color: #5B1312;
    }
    
    .search-result-item {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
    }
    
    .search-result-item:hover {
        box-shadow: 0 4px 16px rgba(42, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .result-info {
        flex: 1;
    }
    
    .result-info h4 {
        margin: 0 0 0.5rem 0;
        font-family: 'Outfit', sans-serif;
        color: #2A0000;
        font-size: 1.125rem;
    }
    
    .result-info h4 mark {
        background-color: #ffd700;
        padding: 0 0.25rem;
        border-radius: 3px;
    }
    
    .result-info p {
        margin: 0;
        font-size: 0.875rem;
        color: #3C1518;
        line-height: 1.4;
    }
    
    .result-info p mark {
        background-color: #ffd700;
        padding: 0 0.25rem;
        border-radius: 3px;
    }
    
    .result-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .result-price {
        font-family: 'Outfit', sans-serif;
        font-size: 1.125rem;
        font-weight: 700;
        color: #5B1312;
        white-space: nowrap;
    }
    
    .btn-order-small {
        background: #2A0000;
        color: #F5F1E8;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .btn-order-small:hover {
        background: #5B1312;
        transform: scale(1.05);
    }
    
    @media (max-width: 768px) {
        .search-modal {
            width: 95%;
            max-height: 90vh;
        }
        
        .search-result-item {
            flex-direction: column;
            align-items: stretch;
        }
        
        .result-actions {
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E2D9C8;
        }
    }
`;
document.head.appendChild(style);

// Export functions for use in other scripts if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        addToCart,
        removeFromCart,
        updateQuantity,
        toggleFavorite,
        calculateCartTotal,
        trackInteraction
    };
}