/**
 * Purge Coffee Shop - Main JavaScript File
 * Handles cart operations, universal favorite management, and notifications.
 * (Search functionality is now handled independently in search.js)
 */

let cart = JSON.parse(localStorage.getItem('coffeeCart')) || [];
let currentNotificationTimer = null; // Global timer tracker for notifications

/**
 * Add product to shopping cart
 */
function addToCart(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('ajax', '1');
    
    fetch('php/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            animateCartIcon();
            trackInteraction(productId, 'add_to_cart');
            
            // Keep local cart array in sync
            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ id: productId, quantity: 1 });
            }
            localStorage.setItem('coffeeCart', JSON.stringify(cart));
            
            updateCartCount(); 
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
 */
function updateCartCountDisplay(count) {
    const cartLink = document.querySelector('.nav-icons a[href="cart.php"]');
    if (!cartLink) return;

    let badge = cartLink.querySelector('.cart-badge');
    
    if (!badge && count > 0) {
        badge = document.createElement('span');
        badge.className = 'cart-badge';
        cartLink.appendChild(badge);
    }
    
    if (badge) {
        badge.textContent = count;
        if (count === 0) {
            badge.remove();
        }
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    localStorage.setItem('coffeeCart', JSON.stringify(cart));
    updateCartCount();
    showNotification('Product removed from cart', 'info');
}

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

function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    updateCartCountDisplay(totalItems);
}

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
 * Toggle favorite/wishlist status for products (Universally updates UI)
 */
function toggleFavorite(productId, element) {
    let favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];
    const index = favorites.indexOf(productId);
    const isFavorited = index > -1;
    
    if (isFavorited) {
        favorites.splice(index, 1);
        showNotification('Removed from favorites', 'info');
        trackInteraction(productId, 'unfavorite');
    } else {
        favorites.push(productId);
        showNotification('Added to favorites!', 'success');
        trackInteraction(productId, 'favorite');
    }
    
    localStorage.setItem('coffeeFavorites', JSON.stringify(favorites));

    // Update ALL matching product cards on the screen instantly
    const productCards = document.querySelectorAll(`.product-card[data-product-id="${productId}"]`);
    
    productCards.forEach(card => {
        const heartIcon = card.querySelector('.favorite-icon i');
        const iconContainer = card.querySelector('.favorite-icon');
        
        if (isFavorited) {
            if (heartIcon) {
                heartIcon.classList.remove('fas');
                heartIcon.classList.add('far');
            }
            if (iconContainer) iconContainer.classList.remove('active');
        } else {
            if (heartIcon) {
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
            }
            if (iconContainer) iconContainer.classList.add('active');
        }
    });
}

/**
 * Track user interactions
 */
function trackInteraction(productId, interactionType) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('interaction_type', interactionType);
    
    fetch('php/track_interaction.php', {
        method: 'POST',
        body: formData
    }).catch(error => console.log('Interaction tracking error:', error));
}

/**
 * Show notification to user (Replaces any existing notification instantly)
 */
function showNotification(message, type = 'info') {
    // 1. Instantly remove any existing notification and clear its timer
    const existingNotification = document.getElementById('purge-live-notification');
    if (existingNotification) {
        existingNotification.remove();
        if (currentNotificationTimer) clearTimeout(currentNotificationTimer);
    }
    
    // 2. Create the new notification
    const notification = document.createElement('div');
    notification.id = 'purge-live-notification';
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // 3. Mount to DOM
    document.body.appendChild(notification);
    
    // 4. Remove smoothly after 3 seconds
    currentNotificationTimer = setTimeout(() => {
        notification.classList.add('slide-out');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.parentElement.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

/**
 * Initialization
 */
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    loadFavorites();
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});

function loadFavorites() {
    const favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];
    
    favorites.forEach(productId => {
        const productCards = document.querySelectorAll(`.product-card[data-product-id="${productId}"]`);
        
        productCards.forEach(card => {
            const heartIcon = card.querySelector('.favorite-icon i');
            const iconContainer = card.querySelector('.favorite-icon');
            
            if (heartIcon) {
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
            }
            if (iconContainer) {
                iconContainer.classList.add('active');
            }
        });
    });
}

function formatPrice(price) {
    return '₱ ' + parseFloat(price).toFixed(2);
}

function calculateCartTotal() {
    return cart.reduce((total, item) => total + item.quantity, 0);
}

// Module export for Node environments (if any)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { addToCart, removeFromCart, updateQuantity, toggleFavorite, calculateCartTotal, trackInteraction };
}