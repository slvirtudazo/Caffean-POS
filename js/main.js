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

                /* Highlight card border when item is in cart */
                const card = document.querySelector(`.product-card[data-product-id="${productId}"]`);
                if (card) card.classList.add('in-cart');
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

/**
 * Update qty display, minus-button state, and in-cart highlight for a menu card
 */
function setMenuCardUI(pid, qty) {
    const numEl = document.getElementById('mpf-num-' + pid);
    const minusEl = document.getElementById('mpf-minus-' + pid);
    if (numEl) numEl.textContent = qty;
    if (minusEl) minusEl.disabled = qty === 0;
    const card = document.querySelector(`.product-card[data-product-id="${pid}"]`);
    if (card) card.classList.toggle('in-cart', qty > 0);
}

/**
 * Handle qty changes on menu product cards (logged-in users only)
 * Adds, updates, or removes item from session cart via AJAX
 */
function menuCardQty(pid, delta) {
    const numEl = document.getElementById('mpf-num-' + pid);
    const current = numEl ? parseInt(numEl.textContent) || 0 : 0;
    const next = Math.max(0, current + delta);

    if (next === 0) {
        /* Remove from cart */
        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('product_id', pid);
        fetch('php/update_cart_item.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setMenuCardUI(pid, 0);
                    updateCartCountDisplay(data.cart_count);
                    cart = cart.filter(i => i.id !== pid);
                    localStorage.setItem('coffeeCart', JSON.stringify(cart));
                    showNotification('Removed from cart.', 'info');
                }
            })
            .catch(() => { });

    } else if (current === 0) {
        /* First add */
        const fd = new FormData();
        fd.append('product_id', pid);
        fd.append('quantity', 1);
        fd.append('ajax', '1');
        fetch('php/add_to_cart.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setMenuCardUI(pid, 1);
                    updateCartCountDisplay(data.cart_count);
                    animateCartIcon();
                    trackInteraction(pid, 'add_to_cart');
                    cart.push({ id: pid, quantity: 1 });
                    localStorage.setItem('coffeeCart', JSON.stringify(cart));
                    showNotification(data.message || 'Added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Could not add to cart.', 'error');
                }
            })
            .catch(() => { });

    } else {
        /* Update quantity */
        const fd = new FormData();
        fd.append('action', 'update_qty');
        fd.append('product_id', pid);
        fd.append('quantity', next);
        fetch('php/update_cart_item.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setMenuCardUI(pid, next);
                    updateCartCountDisplay(data.cart_count);
                    const item = cart.find(i => i.id === pid);
                    if (item) { item.quantity = next; localStorage.setItem('coffeeCart', JSON.stringify(cart)); }
                    showNotification('Cart updated.', 'info');
                }
            })
            .catch(() => { });
    }
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
 * Show notification toast — matches kiosk toast style.
 * Creates the element once, reuses it; shows/hides via .show class + CSS transition.
 */
function showNotification(message, type = 'info') {
    let toast = document.getElementById('notification-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'notification-toast';
        document.body.appendChild(toast);
    }

    // Cancel any pending hide timer
    if (currentNotificationTimer) {
        clearTimeout(currentNotificationTimer);
        currentNotificationTimer = null;
    }

    toast.textContent = message;

    // Force reflow so transition re-triggers on rapid calls
    toast.classList.remove('show');
    void toast.offsetWidth;
    toast.classList.add('show');

    currentNotificationTimer = setTimeout(() => {
        toast.classList.remove('show');
        currentNotificationTimer = null;
    }, 2200);
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

    /* Restore in-cart border highlights from saved cart */
    cart.forEach(item => {
        if (item.quantity > 0) {
            const card = document.querySelector(`.product-card[data-product-id="${item.id}"]`);
            if (card) card.classList.add('in-cart');
        }
    });

    /* Init menu card qty selectors from server session cart */
    if (window.serverCart) {
        Object.entries(window.serverCart).forEach(([pid, qty]) => {
            if (qty > 0) setMenuCardUI(parseInt(pid), qty);
        });
    }
});