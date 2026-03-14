<?php

// Central product image resolver — maps product names to their direct image URLs.

// Product name to direct image URL map.
$PRODUCT_IMAGE_MAP = [
    // Hot Coffee
    'Caffe Americano'              => 'https://i.imgur.com/dpLGx8h.jpg',
    'Cappuccino'                   => 'https://i.imgur.com/fuPhCE3.jpg',
    'Espresso'                     => 'https://i.imgur.com/yQwWLRs.jpg',
    'Hazelnut Latte'               => 'https://i.imgur.com/zZ0pED9.jpg',
    'White Chocolate Mocha'        => 'https://i.imgur.com/XHlGTqg.jpg',
    // Iced Coffee
    'Dirty Horchata'               => 'https://i.imgur.com/VgIbaVB.jpg',
    'Iced Caramel Macchiato'       => 'https://i.imgur.com/GNt156H.jpg',
    'Iced Mocha'                   => 'https://i.imgur.com/jVYvHj5.jpg',
    'Iced Vanilla Latte'           => 'https://i.imgur.com/SK6TldI.jpg',
    'Salted Caramel'               => 'https://i.imgur.com/3SMjsr5.jpg',
    // Non-Coffee
    'Classic Hot Chocolate'        => 'https://i.imgur.com/U09ftsM.jpg',
    'Mango Passionfruit Refresher' => 'https://i.imgur.com/lLWj7cd.jpg',
    'Matcha Green Tea Latte'       => 'https://i.imgur.com/7jYg1GT.jpg',
    'Spiced Chai Latte'            => 'https://i.imgur.com/8UgM2xd.jpg',
    'Strawberry Milk'              => 'https://i.imgur.com/i8RnbvM.jpg',
    // Milkshakes
    'Cookies & Cream Frappe'       => 'https://i.imgur.com/8jhp4s3.jpg',
    'Dark Chocolate Freeze'        => 'https://i.imgur.com/9UKuvV6.jpg',
    'Matcha Cream Frappe'          => 'https://i.imgur.com/J3MAHTn.jpg',
    'Strawberry Cheesecake'        => 'https://i.imgur.com/headILU.jpg',
    'Toffee Nut Crunch'            => 'https://i.imgur.com/AKyN6bx.jpg',
    // Tea
    'Chamomile Honey'              => 'https://i.imgur.com/DpFsHKY.jpg',
    'Earl Grey Milk Tea'           => 'https://i.imgur.com/cOPZ29f.jpg',
    'Jasmine Green Tea'            => 'https://i.imgur.com/IS34qPG.jpg',
    'Lemon Ginger Tea'             => 'https://i.imgur.com/pYU6Nwb.jpg',
    'Peach Iced Tea'               => 'https://i.imgur.com/MFo65cv.jpg',
    // Desserts
    'Affogato'                     => 'https://i.imgur.com/Xx9UuIX.jpg',
    'Chocolate Lava Cake'          => 'https://i.imgur.com/3hsy12w.jpg',
    'New York Cheesecake'          => 'https://i.imgur.com/WRiuEpy.jpg',
    'Red Velvet Cake'              => 'https://i.imgur.com/ONOzQi1.jpg',
    'Tiramisu'                     => 'https://i.imgur.com/r8fEDs2.jpg',
    // Pastry
    'Baguette'                     => 'https://i.imgur.com/JIxNjIN.jpg',
    'Blueberry Scone'              => 'https://i.imgur.com/6HIYu1s.jpg',
    'Chocolate Muffin'             => 'https://i.imgur.com/KnDVgNT.jpg',
    'Cinnamon Roll'                => 'https://i.imgur.com/d9p5asp.jpg',
    'Classic Croissant'            => 'https://i.imgur.com/AlVnKOl.jpg',
    // Snacks
    'Chicken Pesto Pasta'          => 'https://i.imgur.com/3p6WKhS.jpg',
    'Club Sandwich'                => 'https://i.imgur.com/NTE6Cbx.jpg',
    'Loaded Nachos'                => 'https://i.imgur.com/MQEyUvg.jpg',
    'Sausage Roll'                 => 'https://i.imgur.com/BAtKCyU.jpg',
    'Tuna Melt Panini'             => 'https://i.imgur.com/OaThJ6e.jpg',
    // Add-ons
    'Coffee Jelly'                 => 'https://i.imgur.com/BSrFnga.jpg',
    'Extra Espresso Shot'          => 'https://i.imgur.com/1y7iusr.jpg',
    'Pearl (Boba)'                 => 'https://i.imgur.com/6jIBkth.jpg',
    'Vanilla Syrup'                => 'https://i.imgur.com/lCRfakJ.jpg',
    'Whipped Cream'                => 'https://i.imgur.com/PwbYynP.jpg',
    // Coffee Beans
    'Decaf Swiss Water Process'    => 'https://i.imgur.com/uXLIouX.jpg',
    'House Blend Espresso'         => 'https://i.imgur.com/6fZtgVv.jpg',
    'Colombia Supremo'             => 'https://i.imgur.com/Wak6cVn.jpg',
    'Vietnam Robusta'              => 'https://i.imgur.com/ohXChNv.jpg',
    'Sagada Arabica (Dark Roast)'  => 'https://i.imgur.com/bvadXLZ.jpg',
    // Milk & Creamers
    'Almond Milk (Emborg)'         => 'https://i.imgur.com/SwE6hIw.jpg',
    'Oat Milk'                     => 'https://i.imgur.com/Rmcibmz.jpg',
    'Skim Milk'                    => 'https://i.imgur.com/C0sqLtr.jpg',
    'Soy Milk'                     => 'https://i.imgur.com/J4Ttj21.jpg',
    'Whole Milk'                   => 'https://i.imgur.com/QicYc0I.jpg',
    // Brewing Equipment
    'AeroPress Coffee Maker'       => 'https://i.imgur.com/8q1EtUL.jpg',
    'Breville Barista Express'     => 'https://i.imgur.com/F60y2wr.jpg',
    'French Press (Bodum)'         => 'https://i.imgur.com/pKbz9GB.jpg',
    'Hario V60 Dripper (Ceramic)'  => 'https://i.imgur.com/3NMAYcn.jpg',
    'Temperature Controlled Kettle' => 'https://i.imgur.com/04JYQ9c.jpg',
];

// Returns the resolved image URL for a product.
// Priority: name map → external URL → local path → category fallback.
function resolveProductImage($name, $image_path, $cat_id = 0)
{
    global $PRODUCT_IMAGE_MAP;

    // Use the name map if a match exists.
    if (isset($PRODUCT_IMAGE_MAP[$name])) {
        return $PRODUCT_IMAGE_MAP[$name];
    }

    // Use the DB path if it is an external URL.
    if (!empty($image_path) && strpos($image_path, 'http') === 0) {
        return $image_path;
    }

    // Use the local uploaded path if it exists.
    if (!empty($image_path)) {
        return $image_path;
    }

    // Fall back to a category-based placeholder image.
    $pastry_cats = [3, 6, 7, 8];
    if (in_array($cat_id, $pastry_cats)) {
        return 'images/pastry.png';
    }

    return 'images/coffee.png';
}
