/**
 * Purge Coffee Shop - Search Module
 * Handles the universal search modal overlay and AJAX database queries.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Attach listener to the search icon in the navigation
    const searchIcon = document.querySelector('.fa-search');
    if (searchIcon) {
        searchIcon.addEventListener('click', function(e) {
            e.preventDefault();
            showSearchOverlay();
        });
        searchIcon.parentElement.style.cursor = 'pointer';
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSearchOverlay();
    });
});

function showSearchOverlay() {
    let overlay = document.getElementById('searchOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'searchOverlay';
        overlay.className = 'search-overlay';
        overlay.innerHTML = `
            <div class="search-modal">
                <div class="search-header">
                    <h3>Search for products</h3>
                    <button class="search-close" onclick="closeSearchOverlay()"><i class="fas fa-times"></i></button>
                </div>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon-input"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Type to search products..." autocomplete="off">
                </div>
                <div class="search-results" id="searchResults">
                    <div class="search-hint">Start typing to search for products...</div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        const searchInput = document.getElementById('searchInput');
        let debounceTimer;
        
        // Listen to input with a slight delay to prevent spamming the database
        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchProductsAjax(e.target.value);
            }, 300); 
        });
        
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeSearchOverlay();
        });
    }
    
    overlay.style.display = 'flex';
    setTimeout(() => {
        overlay.classList.add('active');
        document.getElementById('searchInput').focus();
    }, 10);
}

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

function searchProductsAjax(searchTerm) {
    const searchLower = searchTerm.trim();
    const resultsContainer = document.getElementById('searchResults');
    
    if (searchLower === '') {
        resultsContainer.innerHTML = '<div class="search-hint">Start typing to search for products...</div>';
        return;
    }
    
    resultsContainer.innerHTML = '<div class="search-hint"><i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i> Searching...</div>';
    
    // Fetch results from the database universally
    fetch(`php/search_ajax.php?q=${encodeURIComponent(searchLower)}`)
        .then(response => response.json())
        .then(data => {
            resultsContainer.innerHTML = '';
            
            if (data.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No products found for "${searchTerm}"</p>
                        <small>Try searching with different keywords</small>
                    </div>
                `;
                return;
            }
            
            data.forEach(product => {
                const resultItem = document.createElement('div');
                resultItem.className = 'search-result-item fade-in';
                resultItem.innerHTML = `
                    <div class="result-info">
                        <h4>${highlightMatch(product.name, searchTerm)}</h4>
                        <p>${highlightMatch(product.description.substring(0, 80) + '...', searchTerm)}</p>
                    </div>
                    <div class="result-actions">
                        <span class="result-price">₱${parseFloat(product.price).toFixed(2)}</span>
                    </div>
                `;
                resultsContainer.appendChild(resultItem);
            });
        })
        .catch(error => {
            console.error('Search Error:', error);
            resultsContainer.innerHTML = '<div class="search-hint">An error occurred while searching.</div>';
        });
}

function highlightMatch(text, query) {
    if (!query) return text;
    // Safely highlight without breaking HTML
    const safeText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    const regex = new RegExp(`(${query})`, 'gi');
    return safeText.replace(regex, '<mark>$1</mark>');
}