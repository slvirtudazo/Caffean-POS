/**
 * Purge Coffee Shop - Main JavaScript File
 * Handles cart operations, universal favorite management, and notifications.
 */

let cart = JSON.parse(localStorage.getItem('coffeeCart')) || [];
let currentNotificationTimer = null;

/**
 * Add product to shopping cart
 * FIX #1: On error, strictly prevent any cart update or navigation.
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
        .then(response => {
            if (!response.ok) throw new Error('Server error: ' + response.status);
            return response.json();
        })
        .then(data => {
            if (data.success === true) {
                showNotification(data.message, 'success');
                animateCartIcon();
                trackInteraction(productId, 'add_to_cart');

                // Keep local cart array in sync ONLY on success
                const existingItem = cart.find(item => item.id === productId);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({ id: productId, quantity: 1 });
                }
                localStorage.setItem('coffeeCart', JSON.stringify(cart));
                updateCartCount();
            } else {
                // FIX #1: error — show message only, do NOT update cart or navigate
                showNotification(data.message || 'Could not add product to cart.', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            // FIX #1: catch — show message only, do NOT update cart
            showNotification('Error adding product to cart.', 'error');
        });
}

/**
 * Update cart count display
 */
function updateCartCountDisplay(count) {
    const cartLink = document.querySelector('.nav-icons a[href="cart.php"]');
    if (!cartLink) return;
    let badge = cartLink.querySelector('.cart-badge');

    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            cartLink.appendChild(badge);
        } else {
            // Re-trigger pop animation on update
            badge.style.animation = 'none';
            badge.offsetHeight; // reflow
            badge.style.animation = '';
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
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
    }
}

/**
 * Fetch and update cart count badge from server session
 */
function updateCartCount() {
    fetch('php/get_cart_count.php')
        .then(r => r.json())
        .then(data => {
            if (typeof data.count !== 'undefined') {
                updateCartCountDisplay(data.count);
            }
        })
        .catch(() => { });
}

/**
 * Animate cart icon on add
 */
function animateCartIcon() {
    const icon = document.querySelector('.nav-icons a[href="cart.php"] .fa-shopping-cart');
    if (!icon) return;
    icon.classList.add('cart-bounce');
    setTimeout(() => icon.classList.remove('cart-bounce'), 600);
}

/**
 * Show notification toast
 * Uses .notification + .notification-{type} CSS classes (style.css / components.css).
 * Slide-in is triggered by the CSS animation on .notification;
 * slide-out is triggered by adding .slide-out after 3 s.
 */
function showNotification(message, type = 'info') {
    let notification = document.getElementById('notification-toast');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification-toast';
        document.body.appendChild(notification);
    }

    // Cancel any pending hide timer so a rapid second call resets cleanly
    if (currentNotificationTimer) {
        clearTimeout(currentNotificationTimer);
        currentNotificationTimer = null;
    }

    notification.textContent = message;
    // Re-assigning className re-triggers the slideIn CSS animation
    notification.className = `notification notification-${type}`;

    currentNotificationTimer = setTimeout(() => {
        notification.classList.add('slide-out');
        currentNotificationTimer = null;
    }, 3000);
}

/**
 * Toggle favorite product.
 * iconEl  — the <i> element inside .favorite-icon.
 * Also toggles the .active class on the parent wrapper for CSS targeting.
 */
function toggleFavorite(productId, iconEl) {
    let favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];
    const idx = favorites.indexOf(productId);
    const wrapper = iconEl.closest ? iconEl.closest('.favorite-icon') : iconEl.parentElement;

    if (idx === -1) {
        favorites.push(productId);
        iconEl.classList.replace('far', 'fas');
        iconEl.style.color = '#c0392b';
        if (wrapper) wrapper.classList.add('active');
        showNotification('Added to favorites!', 'success');
    } else {
        favorites.splice(idx, 1);
        iconEl.classList.replace('fas', 'far');
        iconEl.style.color = '';
        if (wrapper) wrapper.classList.remove('active');
        showNotification('Removed from favorites.', 'info');
    }
    localStorage.setItem('coffeeFavorites', JSON.stringify(favorites));
}

/**
 * Load saved favorites on product cards (page load).
 * Applies .fas class + red colour to the heart icon, and .active to wrapper.
 */
function loadFavoritesForMenu() {
    const favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];
    favorites.forEach(productId => {
        const card = document.querySelector(`[data-product-id="${productId}"]`);
        if (!card) return;
        const wrapper = card.querySelector('.favorite-icon');
        const icon = card.querySelector('.favorite-icon i');
        if (icon) {
            icon.classList.replace('far', 'fas');
            icon.style.color = '#c0392b';
        }
        if (wrapper) wrapper.classList.add('active');
    });
}

/**
 * Track user interaction (lightweight analytics)
 */
function trackInteraction(productId, action) {
    try {
        const key = 'interactions';
        const data = JSON.parse(localStorage.getItem(key)) || [];
        data.push({ productId, action, timestamp: Date.now() });
        if (data.length > 100) data.splice(0, data.length - 100);
        localStorage.setItem(key, JSON.stringify(data));
    } catch (e) { /* fail silently */ }
}

// Initialise cart count on page load
document.addEventListener('DOMContentLoaded', () => {
    // Guests never retain cart or interaction data
    if (!window.IS_LOGGED_IN) {
        localStorage.removeItem('coffeeCart');
        localStorage.removeItem('coffeeFavorites');
        localStorage.removeItem('interactions');
        cart = [];
    }
    updateCartCount();
    loadFavoritesForMenu();
});