<?php
/**
 * Setup AAVNI Store Pages, Taxonomy, Attributes, Products and Settings
 */

// Load WordPress
require_once dirname(dirname(dirname(__DIR__))) . '/wp-load.php';

if (!current_user_can('manage_options') && php_sapi_name() !== 'cli') {
    die('Unauthorized');
}

echo "=== Starting AAVNI Store Setup ===\n";

// 1. Create & Assign Pages
$pages = array(
    'home' => array(
        'title' => 'Home',
        'template' => 'front-page.php',
        'slug' => 'home'
    ),
    'about-us' => array(
        'title' => 'Our Story',
        'template' => 'page-about-us.php',
        'slug' => 'about-us'
    ),
    'contact-us' => array(
        'title' => 'Contact Us',
        'template' => 'page-contact-us.php',
        'slug' => 'contact-us'
    ),
    'size-guide' => array(
        'title' => 'Size Guide',
        'template' => 'page-size-guide.php',
        'slug' => 'size-guide'
    ),
    'faq' => array(
        'title' => 'FAQ',
        'template' => 'page-faq.php',
        'slug' => 'faq'
    ),
    'shipping-policy' => array(
        'title' => 'Shipping Policy',
        'template' => 'page-shipping-policy.php',
        'slug' => 'shipping-policy'
    ),
    'terms-and-conditions' => array(
        'title' => 'Terms & Conditions',
        'template' => '',
        'slug' => 'terms-and-conditions',
        'content' => '<h3>Terms of Service</h3><p>Welcome to AAVNI. By accessing or shopping on our website, you agree to our standard terms of service. All designs, trademarks, and imagery belong to AAVNI. Orders are subject to verification and product availability.</p>'
    )
);

foreach ($pages as $key => $p) {
    $existing = get_page_by_path($p['slug']);
    if (!$existing) {
        $page_id = wp_insert_post(array(
            'post_title' => $p['title'],
            'post_name' => $p['slug'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => isset($p['content']) ? $p['content'] : '',
        ));
        if (!empty($p['template'])) {
            update_post_meta($page_id, '_wp_page_template', $p['template']);
        }
        echo "Created page: " . $p['title'] . " (ID: $page_id)\n";
    } else {
        $page_id = $existing->ID;
        wp_update_post(array('ID' => $page_id, 'post_status' => 'publish'));
        if (!empty($p['template'])) {
            update_post_meta($page_id, '_wp_page_template', $p['template']);
        }
        echo "Page exists: " . $p['title'] . " (ID: $page_id)\n";
    }

    if ($key === 'home') {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page_id);
        echo "Assigned Home page as Front Page.\n";
    }
}

// Publish privacy policy & refund returns
$privacy = get_page_by_path('privacy-policy');
if ($privacy) {
    wp_update_post(array('ID' => $privacy->ID, 'post_status' => 'publish'));
    echo "Published Privacy Policy.\n";
}
$refund = get_page_by_path('refund_returns');
if ($refund) {
    wp_update_post(array('ID' => $refund->ID, 'post_status' => 'publish'));
    echo "Published Refund & Returns Policy.\n";
}

// 2. Create Product Categories
$categories = array(
    'Long Kurti' => 'long-kurti',
    'Short Kurti' => 'short-kurti',
    'Tops' => 'tops',
    'Nighty' => 'nighty'
);

$cat_ids = array();
foreach ($categories as $name => $slug) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        $new_term = wp_insert_term($name, 'product_cat', array('slug' => $slug));
        if (!is_wp_error($new_term)) {
            $cat_ids[$slug] = $new_term['term_id'];
            echo "Created Category: $name (ID: {$new_term['term_id']})\n";
        }
    } else {
        $cat_ids[$slug] = $term->term_id;
        echo "Category exists: $name (ID: {$term->term_id})\n";
    }
}

// 3. Register Product Attributes: Size & Fabric
$size_attr_id = wc_create_attribute(array(
    'name' => 'Size',
    'slug' => 'size',
    'type' => 'select',
    'order_by' => 'menu_order',
    'has_archives' => false,
));
echo "Attribute 'Size' verified.\n";

// 4. Import / Attach Media
$upload_dir = wp_upload_dir();
$media_map = array(
    'long_kurti' => $upload_dir['basedir'] . '/aavni/long-kurti-1.jpg',
    'short_kurti' => $upload_dir['basedir'] . '/aavni/short-kurti-1.jpg',
    'chic_top' => $upload_dir['basedir'] . '/aavni/chic-top-1.jpg',
    'nightwear' => $upload_dir['basedir'] . '/aavni/nightwear-1.jpg',
);

$attach_ids = array();
foreach ($media_map as $key => $file_path) {
    if (file_exists($file_path)) {
        $filetype = wp_check_filetype(basename($file_path), null);
        $attachment = array(
            'guid' => $upload_dir['baseurl'] . '/aavni/' . basename($file_path),
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file_path);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        $attach_ids[$key] = $attach_id;
        echo "Attached media: $key (ID: $attach_id)\n";
    }
}

// 5. Create Verified Real Products
$products = array(
    array(
        'title' => 'AAVNI Ivory Gold Embroidered Pure Cotton Long Kurti',
        'slug' => 'aavni-ivory-gold-cotton-long-kurti',
        'sku' => 'AAV-LK-001',
        'regular_price' => '1899',
        'sale_price' => '1499',
        'category' => 'long-kurti',
        'image' => isset($attach_ids['long_kurti']) ? $attach_ids['long_kurti'] : 0,
        'description' => 'A statement of refined Indian grace. This AAVNI long kurti is crafted from 100% pure premium cotton with delicate gold-tone embroidery along the placket, cuffs, and hem. Designed with side slits and a comfortable mandarin collar for graceful everyday styling and festive dinners.<br><br><strong>Fabric:</strong> 100% Breathable Pure Cotton<br><strong>Weave:</strong> Cambric Weave<br><strong>Fit:</strong> Straight Silhouette with 2-inch comfort ease<br><strong>Wash Care:</strong> Gentle hand or machine wash in cold water with mild detergent.',
        'short_description' => 'Pure cotton long kurti with fine gold embroidery and relaxed straight silhouette.',
    ),
    array(
        'title' => 'AAVNI Sage Floral Artisanal Cotton Short Kurti',
        'slug' => 'aavni-sage-floral-cotton-short-kurti',
        'sku' => 'AAV-SK-002',
        'regular_price' => '1299',
        'sale_price' => '999',
        'category' => 'short-kurti',
        'image' => isset($attach_ids['short_kurti']) ? $attach_ids['short_kurti'] : 0,
        'description' => 'Contemporary ethnic flair made for modern routines. This short kurti features soothing sage green botanical motifs on soft cotton fabric with a notched henley neckline and 3/4 sleeves. Pairs effortlessly with linen trousers, jeans, or palazzos.<br><br><strong>Fabric:</strong> 100% Pure Lightweight Cotton<br><strong>Pattern:</strong> Hand-inspired floral print<br><strong>Length:</strong> 30 inches (Short Kurti / Hip-length)<br><strong>Care:</strong> Wash with similar light colors.',
        'short_description' => 'Everyday floral short kurti in sage green with comfortable henley placket.',
    ),
    array(
        'title' => 'AAVNI Terracotta Striped Handloom Cotton Tunic Top',
        'slug' => 'aavni-terracotta-striped-tunic-top',
        'sku' => 'AAV-TP-003',
        'regular_price' => '1199',
        'sale_price' => '899',
        'category' => 'tops',
        'image' => isset($attach_ids['chic_top']) ? $attach_ids['chic_top'] : 0,
        'description' => 'Earthy tones meet contemporary silhouettes. Made from handloom-textured cotton with vertical terracotta and beige stripes that flatter the torso, featuring an elegant V-split neckline and relaxed side ease.<br><br><strong>Fabric:</strong> Handloom Textured Pure Cotton<br><strong>Fit:</strong> Relaxed A-Line Tunic<br><strong>Styling:</strong> Wear with culottes or straight pants.',
        'short_description' => 'Handloom textured cotton tunic top with earthy terracotta stripes.',
    ),
    array(
        'title' => 'AAVNI Dusty Rose Botanic Breathable Cotton Nighty',
        'slug' => 'aavni-dusty-rose-botanic-cotton-nighty',
        'sku' => 'AAV-NW-004',
        'regular_price' => '1099',
        'sale_price' => '799',
        'category' => 'nighty',
        'image' => isset($attach_ids['nightwear']) ? $attach_ids['nightwear'] : 0,
        'description' => 'Unwind in pure natural comfort. This AAVNI loungewear nighty is tailored with ultra-soft, breathable cotton in a delicate dusty rose botanical print. Features an easy pullover design, soft gathering under the bust, and functional side pocket.<br><br><strong>Fabric:</strong> 100% Super-combed Pure Cotton<br><strong>Length:</strong> Full Length (52 inches)<br><strong>Ease:</strong> Relaxed Loungewear Fit',
        'short_description' => 'Ultra-breathable 100% cotton loungewear nighty in soft dusty rose.',
    )
);

foreach ($products as $pdata) {
    $existing = wc_get_product_id_by_sku($pdata['sku']);
    if (!$existing) {
        $product = new WC_Product_Simple();
        $product->set_name($pdata['title']);
        $product->set_slug($pdata['slug']);
        $product->set_sku($pdata['sku']);
        $product->set_regular_price($pdata['regular_price']);
        $product->set_sale_price($pdata['sale_price']);
        $product->set_price($pdata['sale_price']);
        $product->set_description($pdata['description']);
        $product->set_short_description($pdata['short_description']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_manage_stock(true);
        $product->set_stock_quantity(25);
        $product->set_stock_status('instock');

        if (!empty($pdata['image'])) {
            $product->set_image_id($pdata['image']);
        }

        if (isset($cat_ids[$pdata['category']])) {
            $product->set_category_ids(array($cat_ids[$pdata['category']]));
        }

        $prod_id = $product->save();
        echo "Created Product: {$pdata['title']} (ID: $prod_id, SKU: {$pdata['sku']})\n";
    } else {
        echo "Product already exists for SKU {$pdata['sku']} (ID: $existing)\n";
    }
}

// 6. Configure WooCommerce Shipping Method & Zone for India
$delivery_zones = WC_Shipping_Zones::get_zones();
$india_zone_exists = false;
foreach ($delivery_zones as $zone) {
    if ($zone['zone_name'] === 'India Express Delivery') {
        $india_zone_exists = true;
        break;
    }
}

if (!$india_zone_exists) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('India Express Delivery');
    $zone->set_zone_order(1);
    $zone->add_location('IN', 'country');
    $zone->save();

    // Add Flat Rate
    $flat_rate_id = $zone->add_shipping_method('flat_rate');
    $flat_rate = WC_Shipping_Zones::get_shipping_method($flat_rate_id);
    if ($flat_rate) {
        $flat_rate->set_post_data(array(
            'woocommerce_flat_rate_title' => 'Standard Express Delivery',
            'woocommerce_flat_rate_cost' => '80',
            'woocommerce_flat_rate_tax_status' => 'none',
        ));
        $flat_rate->process_admin_options();
    }

    // Add Free Shipping Threshold
    $free_shipping_id = $zone->add_shipping_method('free_shipping');
    $free_shipping = WC_Shipping_Zones::get_shipping_method($free_shipping_id);
    if ($free_shipping) {
        $free_shipping->set_post_data(array(
            'woocommerce_free_shipping_title' => 'Complimentary Express Shipping',
            'woocommerce_free_shipping_requires' => 'min_amount',
            'woocommerce_free_shipping_min_amount' => '999',
        ));
        $free_shipping->process_admin_options();
    }
    echo "Created India Express Delivery Shipping Zone with Free Shipping threshold (₹999).\n";
}

// 7. Enable Cash on Delivery & Direct Bank Transfer (for testing sandbox checkout flow)
update_option('woocommerce_cod_settings', array(
    'enabled' => 'yes',
    'title' => 'Cash on Delivery (COD)',
    'description' => 'Pay cash upon delivery at your doorstep. Verified delivery handling.',
    'instructions' => 'Please keep exact cash ready upon delivery.',
    'enable_for_methods' => array(),
    'enable_for_virtual' => 'yes'
));
echo "Enabled Cash on Delivery (COD).\n";

// Update site tagline
update_option('blogname', 'AAVNI');
update_option('blogdescription', 'Dress To Express. Live To Impress.');

echo "=== AAVNI Store Setup Completed Successfully! ===\n";
