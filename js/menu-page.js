/**
 * Menu Page JavaScript
 * Handles filtering interactions and dynamic updates for the menu page
 */

/**
 * Initialize menu page functionality when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function () {

    // Load and display saved favorites on product cards
    loadFavoritesForMenu();

    // Setup smooth animations for product cards
    setupProductAnimations();

    // Setup mobile filter toggle if needed
    setupMobileFilters();

    // Setup multi-select sort item toggles
    setupSortToggles();

    // Track page view for analytics
    trackMenuPageView();
});

/**
 * Setup multi-select sort/filter toggles.
 *
 * Rules:
 *  - data-sort-param="price_sort"  + data-sort-exclusive="price_sort"
 *      → mutually exclusive within the group (selecting one deselects other price options)
 *  - data-sort-param="popular"     (no exclusive group) → independent toggle
 *  - data-sort-param="bestsellers" (no exclusive group) → independent toggle
 *
 * Clicking an already-active item DESELECTS it (removes the param from URL).
 * Clicking an inactive item ACTIVATES it (adds/updates the param in URL).
 */
function setupSortToggles() {
    const sortItems = document.querySelectorAll('.sort-item[data-sort-param]');

    sortItems.forEach(function (item) {
        item.addEventListener('click', function () {
            const param     = item.dataset.sortParam;
            const value     = item.dataset.sortValue;
            const exclusive = item.dataset.sortExclusive || null; // e.g. "price_sort"
            const isActive  = item.classList.contains('active');

            const urlParams = new URLSearchParams(window.location.search);

            if (isActive) {
                // Deselect: remove the param entirely
                urlParams.delete(param);
            } else {
                // If this param is part of an exclusive group, deselect others first
                if (exclusive) {
                    // Remove existing value for this exclusive group
                    urlParams.delete(exclusive);
                }
                // Activate: set the param
                urlParams.set(param, value);
            }

            // Navigate to the new URL
            const newSearch = urlParams.toString();
            window.location.href = window.location.pathname + (newSearch ? '?' + newSearch : '');
        });
    });
}

/**
 * Load favorite products from localStorage and mark them visually.
 * Updates heart icons on product cards for favorited items.
 */
function loadFavoritesForMenu() {
    const favorites = JSON.parse(localStorage.getItem('coffeeFavorites')) || [];

    favorites.forEach(function (productId) {
        const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
        if (productCard) {
            const heartIcon = productCard.querySelector('.favorite-icon i');
            if (heartIcon) {
                heartIcon.classList.remove('far');
                heartIcon.classList.add('fas');
                productCard.querySelector('.favorite-icon').classList.add('active');
            }
        }
    });
}

/**
 * Setup intersection observer for product card fade-in animations.
 */
function setupProductAnimations() {
    const productCards = document.querySelectorAll('.product-card');

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.animation = 'fadeInUp 0.5s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    productCards.forEach(function (card) {
        observer.observe(card);
    });
}

/**
 * Setup mobile filter panel toggle.
 */
function setupMobileFilters() {
    if (window.innerWidth <= 768) {
        const filterPanel = document.querySelector('.filter-panel');
        const menuContent = document.querySelector('.menu-content');

        if (filterPanel && menuContent) {
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

            document.body.appendChild(toggleBtn);
            filterPanel.style.display = 'none';

            toggleBtn.addEventListener('click', function () {
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
 * Track menu page view for analytics.
 */
function trackMenuPageView() {
    const urlParams   = new URLSearchParams(window.location.search);
    const category    = urlParams.get('category')    || 'all';
    const priceSort   = urlParams.get('price_sort')  || 'none';
    const popular     = urlParams.get('popular')     || '0';
    const bestsellers = urlParams.get('bestsellers') || '0';

    console.log('Menu Page View:', {
        category:    category,
        priceSort:   priceSort,
        popular:     popular,
        bestsellers: bestsellers,
        timestamp:   new Date().toISOString()
    });
}

/**
 * Update URL parameters without page reload (utility).
 */
function updateURLParameter(param, value) {
    const urlParams = new URLSearchParams(window.location.search);

    if (value) {
        urlParams.set(param, value);
    } else {
        urlParams.delete(param);
    }

    const newUrl = window.location.pathname + '?' + urlParams.toString();
    window.history.pushState({ path: newUrl }, '', newUrl);
}

/**
 * Filter products by search term (client-side).
 */
function filterProductsBySearch(searchTerm) {
    const products    = document.querySelectorAll('.product-card');
    const searchLower = searchTerm.toLowerCase().trim();
    let   visibleCount = 0;

    products.forEach(function (product) {
        const productName = product.querySelector('.product-name').textContent.toLowerCase();
        const productDesc = product.querySelector('.product-description').textContent.toLowerCase();

        if (searchLower === '' || productName.includes(searchLower) || productDesc.includes(searchLower)) {
            product.style.display = 'flex';
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });

    const resultsCount = document.querySelector('.results-count');
    if (resultsCount) {
        resultsCount.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'item' : 'items'}`;
    }
}

/**
 * Smooth scroll to top of products grid.
 */
function scrollToProducts() {
    const productsGrid = document.querySelector('.products-grid');
    if (productsGrid) {
        productsGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Show loading state on product grid.
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
 * Add keyboard navigation support for filters.
 */
function setupKeyboardNavigation() {
    const filterItems = document.querySelectorAll('.category-item, .sort-item');

    filterItems.forEach(function (item) {
        item.setAttribute('tabindex', '0');

        item.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
    });
}

// Initialize keyboard navigation on load
document.addEventListener('DOMContentLoaded', setupKeyboardNavigation);