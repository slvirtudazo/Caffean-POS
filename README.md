**Caffean: Web-Based Coffee Shop Management System with Integrated POS and Customer Self-Service Portal**

<hr>

Caffean is a web-based coffee shop management system designed to modernize and unify daily shop operations through an integrated online and in-store platform. It offers tools for browsing and ordering from a product catalog, managing a shopping cart, processing delivery or pickup checkouts, saving favorites, and handling customer account settings, all backed by a centralized MySQL database for consistent and reliable data management. The system integrates five core components—customer self-service portal, in-store self-order kiosk, admin dashboard, product and order management, and a sales insights panel that automate transactions and streamline both front-of-house and back-office workflows. By digitizing order processing and record-keeping, Caffean reduces manual errors, improves operational efficiency, and provides real-time updates across all modules. Built with PHP, MySQL, Bootstrap, and vanilla JavaScript, it delivers a responsive and intuitive experience suitable for small to mid-scale coffee shops seeking to consolidate their POS, online ordering, and management tools into one system.

<hr>

**Project Structure**

```
coffee-shop-management-system/
│
├── index.php                  # Homepage
├── about.php                  # About page
├── menu.php                   # Full menu listing
├── coffee.php                 # Coffee product category page
├── pastry.php                 # Pastry product category page
├── supplies_page.php          # Supplies product category page
├── cart.php                   # Shopping cart
├── checkout.php               # Checkout with receipt dialog
├── order_success.php          # Post-order confirmation
├── account.php                # Customer account overview
├── login.php                  # Login page
├── register.php               # Registration page
├── forgot_password.php        # Password recovery
├── kiosk.php                  # In-store self-order kiosk (POS)
├── kiosk_display.php          # Kiosk display/confirmation screen
│
├── admin/
│   ├── dashboard.php          # Admin dashboard — sales stats overview
│   ├── products.php           # Product CRUD management
│   ├── orders.php             # Online orders management
│   ├── instore_orders.php     # In-store kiosk orders management
│   ├── customers.php          # Customer records
│   ├── insights.php           # Revenue trends and analytics
│   └── profile_settings.php   # Admin profile and password settings
│
├── php/
│   ├── db_connection.php      # Database connection and session init
│   ├── add_to_cart.php        # Add item to cart handler
│   ├── update_cart_item.php   # Update cart item quantity/options
│   ├── sync_cart.php          # Sync cart state to session
│   ├── get_cart_count.php     # Return cart item count (AJAX)
│   ├── place_order.php        # Online order placement handler
│   ├── kiosk_place_order.php  # Kiosk order placement handler
│   ├── favorites.php          # Favorites toggle/fetch handler
│   ├── product_images.php     # Product image path resolver
│   ├── search_ajax.php        # Live search AJAX handler
│   ├── track_interaction.php  # User interaction tracking
│   ├── update_profile.php     # Customer profile update handler
│   └── logout.php             # Session destroy and redirect
│
├── css/
│   ├── style.css              # Global base styles
│   ├── components.css         # Shared UI components
│   ├── buttons.css            # Button variants
│   ├── admin.css              # Admin panel styles
│   ├── dashboard.css          # Dashboard layout styles
│   ├── products.css           # Products admin styles
│   ├── orders.css             # Orders admin styles
│   ├── customers.css          # Customers admin styles
│   ├── kiosk.css              # Kiosk UI styles
│   ├── menu_page.css          # Menu page styles
│   ├── coffee_page.css        # Coffee page styles
│   ├── pastry_page.css        # Pastry page styles
│   ├── supplies_page.css      # Supplies page styles
│   ├── cart.css               # Cart page styles
│   ├── checkout.css           # Checkout page styles
│   ├── account_page.css       # Account page styles
│   ├── about_page.css         # About page styles
│   ├── login.css              # Login page styles
│   ├── register.css           # Register page styles
│   ├── forgot_password.css    # Forgot password styles
│   ├── search.css             # Search overlay styles
│   └── footer_section.css     # Footer styles
│
├── js/
│   ├── main.js                # Global scripts (cart, navbar, modals)
│   ├── menu_page.js           # Menu filter and UI interactions
│   └── search.js              # Live search functionality
│
├── includes/
│   ├── header.php             # Global site header and navbar
│   └── footer.php             # Global site footer
│
└── images/                    # Product and UI images
```

<hr>

**License**

This project is distributed under the **MIT License**, which permits free use, modification, and distribution of the software with proper attribution. Commercial and non-commercial applications are permitted. The software is provided without warranty of any kind, and the authors assume no liability for damages resulting from its use. For complete license terms, refer to the `LICENSE` file in the project repository.
