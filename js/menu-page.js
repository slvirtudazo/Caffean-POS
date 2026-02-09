/**
 * Menu Page JavaScript
 * Handles filtering interactions and dynamic updates for the menu page
 */

/**
 * Toggle best sellers filter
 * Updates URL with bestsellers parameter and reloads page
 * @param {HTMLInputElement} checkbox - The bestsellers toggle checkbox element
 */
function toggleBestsellers(checkbox) {
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Update or remove bestsellers parameter
    if (checkbox.checked) {
        urlParams.set('bestsellers', '1');
    } else {
        urlParams.delete('bestsellers');
    }
    
    // Reload page with updated parameters
    window.location.search = urlParams.toString();
}

/**
 * Initialize menu page functionality when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Load and display saved favorites on product cards
    loadFavoritesForMenu();
    
    // Setup smooth animations for product cards
    setupProductAnimations();
    
    // Setup mobile filter toggle if needed
    setupMobileFilters();
    
    // Track page view for analytics
    trackMenuPageView();
});

/**
 * Load favorite products from localStorage and mark them visually
 * Updates heart icons on product cards for favorited items
 */
function loadFavoritesForMenu() {
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
 * Setup intersection observer for product card animations
 * Adds fade-in animation when cards come into viewport
 */
function setupProductAnimations() {
    const productCards = document.querySelectorAll('.product-card');
    
    // Configuration for intersection observer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    // Create observer to detect when cards enter viewport
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.animation = 'fadeInUp 0.5s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe each product card
    productCards.forEach(card => {
        observer.observe(card);
    });
}

/**
 * Setup mobile filter panel toggle
 * Creates a collapsible filter panel for mobile devices
 */
function setupMobileFilters() {
    // Only run on mobile devices
    if (window.innerWidth <= 768) {
        const filterPanel = document.querySelector('.filter-panel');
        const menuContent = document.querySelector('.menu-content');
        
        if (filterPanel && menuContent) {
            // Create toggle button for filters
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-filter-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-filter"></i> Filters';
            toggleBtn.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--deep-maroon);
                color: white;
                border: none;
                padding: 1rem 1.5rem;
                border-radius: 50px;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                cursor: pointer;
            `;
            
            // Insert toggle button
            document.body.appendChild(toggleBtn);
            
            // Initially hide filter panel on mobile
            filterPanel.style.display = 'none';
            
            // Toggle filter panel visibility
            toggleBtn.addEventListener('click', function() {
                if (filterPanel.style.display === 'none') {
                    filterPanel.style.display = 'block';
                    filterPanel.style.position = 'fixed';
                    filterPanel.style.top = '80px';
                    filterPanel.style.left = '50%';
                    filterPanel.style.transform = 'translateX(-50%)';
                    filterPanel.style.width = '90%';
                    filterPanel.style.maxWidth = '400px';
                    filterPanel.style.zIndex = '999';
                    filterPanel.style.maxHeight = 'calc(100vh - 100px)';
                    toggleBtn.innerHTML = '<i class="fas fa-times"></i> Close';
                } else {
                    filterPanel.style.display = 'none';
                    toggleBtn.innerHTML = '<i class="fas fa-filter"></i> Filters';
                }
            });
        }
    }
}

/**
 * Track menu page view for analytics
 * Logs page view with current filter selections
 */
function trackMenuPageView() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category') || 'all';
    const sort = urlParams.get('sort') || 'default';
    const bestsellers = urlParams.get('bestsellers') || '0';
    
    // Log analytics (could be sent to analytics service)
    console.log('Menu Page View:', {
        category: category,
        sort: sort,
        bestsellers: bestsellers,
        timestamp: new Date().toISOString()
    });
}

/**
 * Update URL parameters without page reload
 * Useful for future AJAX-based filtering
 * @param {string} param - Parameter name to update
 * @param {string} value - New parameter value
 */
function updateURLParameter(param, value) {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (value) {
        urlParams.set(param, value);
    } else {
        urlParams.delete(param);
    }
    
    const newUrl = window.location.pathname + '?' + urlParams.toString();
    window.history.pushState({path: newUrl}, '', newUrl);
}

/**
 * Filter products by search term
 * Filters visible products based on name or description match
 * @param {string} searchTerm - Search query string
 */
function filterProductsBySearch(searchTerm) {
    const products = document.querySelectorAll('.product-card');
    const searchLower = searchTerm.toLowerCase().trim();
    let visibleCount = 0;
    
    products.forEach(product => {
        const productName = product.querySelector('.product-name').textContent.toLowerCase();
        const productDesc = product.querySelector('.product-description').textContent.toLowerCase();
        
        if (searchLower === '' || productName.includes(searchLower) || productDesc.includes(searchLower)) {
            product.style.display = 'flex';
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });
    
    // Update results count
    const resultsCount = document.querySelector('.results-count');
    if (resultsCount) {
        resultsCount.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'item' : 'items'}`;
    }
}

/**
 * Smooth scroll to top of products grid
 * Used when changing filters or categories
 */
function scrollToProducts() {
    const productsGrid = document.querySelector('.products-grid');
    if (productsGrid) {
        productsGrid.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Show loading state on product grid
 * Displays skeleton loading effect while products load
 */
function showLoadingState() {
    const productsGrid = document.querySelector('.products-grid');
    if (productsGrid) {
        productsGrid.innerHTML = `
            <div class="loading-skeleton">
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
                <div class="skeleton-card"></div>
            </div>
        `;
    }
}

/**
 * Add keyboard navigation support for filters
 * Allows users to navigate filters using keyboard
 */
function setupKeyboardNavigation() {
    const filterItems = document.querySelectorAll('.category-item, .sort-item');
    
    filterItems.forEach((item, index) => {
        item.setAttribute('tabindex', '0');
        
        item.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
    });
}

// Initialize keyboard navigation on load
document.addEventListener('DOMContentLoaded', setupKeyboardNavigation);