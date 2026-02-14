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