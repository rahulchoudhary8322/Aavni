<?php
/**
 * Import Real Amazon AAVNI Long Kurti & Expand Category Catalog
 */

require_once dirname(dirname(dirname(__DIR__))) . '/wp-load.php';

if (!current_user_can('manage_options') && php_sapi_name() !== 'cli') {
    die('Unauthorized');
}

echo "=== Importing Amazon AAVNI Long Kurti Products ===\n";

$upload_dir = wp_upload_dir();
$base_upload = $upload_dir['basedir'] . '/aavni/';
$base_url = $upload_dir['baseurl'] . '/aavni/';

require_once(ABSPATH . 'wp-admin/includes/image.php');

function get_or_attach_media($filename, $title, $base_upload, $base_url) {
    $file_path = $base_upload . $filename;
    if (!file_exists($file_path)) {
        echo "File does not exist: $file_path\n";
        return 0;
    }

    // Check if attachment already exists by title
    $existing = get_page_by_title($title, OBJECT, 'attachment');
    if ($existing) {
        return $existing->ID;
    }

    $filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'guid' => $base_url . $filename,
        'post_mime_type' => $filetype['type'],
        'post_title' => $title,
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $file_path);
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    update_post_meta($attach_id, '_wp_attachment_image_alt', $title);
    echo "Registered image: $title (ID: $attach_id)\n";
    return $attach_id;
}

$img_brown_front = get_or_attach_media('long-kurti-b0hgr9lqz9-brown.jpg', 'AAVNI Floral Printed Halter Neck Cotton Long Kurti Brown Front', $base_upload, $base_url);
$img_brown_back  = get_or_attach_media('long-kurti-b0hgr9lqz9-brown-back.jpg', 'AAVNI Floral Printed Halter Neck Kurti Brown Tie-Up Back Detail', $base_upload, $base_url);
$img_fabric_det  = get_or_attach_media('long-kurti-b0hgr9lqz9-fabric-detail.jpg', 'AAVNI Pure Breathable Cotton Fabric Close-up Jaipur Motif', $base_upload, $base_url);
$img_blue_front  = get_or_attach_media('long-kurti-b0hgr9lqz9-blue.jpg', 'AAVNI Halter Neck Sleeveless Cotton Long Kurti Indigo Blue', $base_upload, $base_url);
$img_olive_front = get_or_attach_media('long-kurti-b0hgr9lqz9-olive.jpg', 'AAVNI Halter Neck Sleeveless Cotton Long Kurti Olive Green', $base_upload, $base_url);
$img_pink_front  = get_or_attach_media('long-kurti-b0hgr9lqz9-pink.jpg', 'AAVNI Halter Neck Sleeveless Cotton Long Kurti Dusty Pink', $base_upload, $base_url);
$img_black_front = get_or_attach_media('long-kurti-black-sanganeri.jpg', 'AAVNI Sanganeri Black Floral Cotton Straight Long Kurti', $base_upload, $base_url);
$img_maroon_front= get_or_attach_media('long-kurti-maroon-gold.jpg', 'AAVNI Crimson Maroon Gold Foil Print Straight Long Kurti', $base_upload, $base_url);

// Ensure Categories exist
$cat_long_kurti = get_term_by('slug', 'long-kurti', 'product_cat');
if (!$cat_long_kurti) {
    $term = wp_insert_term('Long Kurti', 'product_cat', array('slug' => 'long-kurti'));
    $cat_id = $term['term_id'];
} else {
    $cat_id = $cat_long_kurti->term_id;
}

// Update Category Description for rich SEO banner
wp_update_term($cat_id, 'product_cat', array(
    'description' => 'Explore AAVNI’s signature collection of calf-length long kurtis. From modern halter neck cuts with back tie-ups to traditional straight silhouettes, each piece is handcrafted from 100% breathable cambric cotton with authentic Jaipur block prints for all-day comfort and effortless elegance.'
));

// Register Attributes
function create_wc_attribute($name, $slug) {
    global $wpdb;
    $attr = wc_get_attribute_taxonomies();
    foreach ($attr as $a) {
        if ($a->attribute_name === $slug) {
            return;
        }
    }
    wc_create_attribute(array(
        'name' => $name,
        'slug' => $slug,
        'type' => 'select',
        'order_by' => 'menu_order',
        'has_archives' => false
    ));
}
create_wc_attribute('Color', 'color');
create_wc_attribute('Size', 'size');

// Populate terms
$colors = array('Printed Brown', 'Indigo Blue', 'Olive Green', 'Dusty Pink', 'Sanganeri Black', 'Crimson Maroon');
foreach ($colors as $c) {
    if (!term_exists($c, 'pa_color')) {
        wp_insert_term($c, 'pa_color');
    }
}
$sizes = array('S (36")', 'M (38")', 'L (40")', 'XL (42")', 'XXL (44")');
foreach ($sizes as $s) {
    if (!term_exists($s, 'pa_size')) {
        wp_insert_term($s, 'pa_size');
    }
}

// 1. CREATE MAIN AMAZON PRODUCT (B0HGR9LQZ9)
$amazon_sku = 'AAV-LK-B04';
$existing_id = wc_get_product_id_by_sku($amazon_sku);
if ($existing_id) {
    wp_delete_post($existing_id, true);
    echo "Removed previous instance of SKU $amazon_sku for fresh variable creation.\n";
}

$product = new WC_Product_Variable();
$product->set_name('AAVNI Floral Printed Halter Neck Sleeveless Cotton Long Kurti (Tie-Up Back)');
$product->set_slug('aavni-floral-printed-halter-neck-sleeveless-cotton-long-kurti-b0hgr9lqz9');
$product->set_sku($amazon_sku);
$product->set_status('publish');
$product->set_catalog_visibility('visible');
$product->set_featured(true);
$product->set_category_ids(array($cat_id));
$product->set_image_id($img_brown_front);
$product->set_gallery_image_ids(array($img_brown_back, $img_fabric_det, $img_blue_front, $img_olive_front, $img_pink_front));

// Short Description with Bullet Points
$short_desc = '
<div class="aavni-product-quick-specs">
    <div class="aavni-rating-badge" style="display:inline-flex; align-items:center; gap:6px; background:#FBF6EE; border:1px solid #E8DFD1; padding:4px 10px; border-radius:20px; font-size:13px; font-weight:600; color:#8C6239; margin-bottom:12px;">
        <span style="color:#E67A00;">★★★★★</span> 4.2 <span style="color:#777; font-weight:400;">(85 verified ratings on Amazon)</span>
    </div>
    <ul class="aavni-feature-bullets" style="padding-left:18px; margin: 12px 0 16px 0; line-height:1.7; font-size:14.5px; color:#3A3732;">
        <li><strong>PRINTED COTTON LONG KURTI:</strong> Ethnic floral motifs on a warm brown cotton base give this long kurti a distinctive look that is easy to dress up or down.</li>
        <li><strong>MODERN HALTER NECK DESIGN:</strong> A flattering halter neckline with adjustable tie-up back creates a contemporary ethnic silhouette.</li>
        <li><strong>SLEEVELESS CASUAL COMFORT:</strong> Cut for warm weather ease, providing effortless movement and all-day breathable comfort.</li>
        <li><strong>CALF LENGTH WITH SIDE SLITS:</strong> Elegant 44-inch length with side slits to pair seamlessly with leggings, palazzos, or blue denim jeans.</li>
        <li><strong>VERSATILE OCCASIONS:</strong> Ideal for everyday casual wear, office ethnic days, college styling, or weekend festive lunches.</li>
    </ul>
</div>
';
$product->set_short_description($short_desc);

// Rich Description with Amazon Specifications Table & Brand Story
$long_desc = '
<div class="aavni-rich-specs-section">
    <h3 style="font-family:\'Playfair Display\', serif; font-size:22px; margin-bottom:16px; color:#1B1A18;">Product Specifications</h3>
    <table class="aavni-specs-table" style="width:100%; border-collapse:collapse; margin-bottom:32px; font-size:14.5px;">
        <tbody>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; width:35%; color:#6E6A63; background:#FAF8F5;">Material Composition</td><td style="padding:10px 14px; color:#1B1A18;">100% Pure Breathable Cambric Cotton</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Neck Style</td><td style="padding:10px 14px; color:#1B1A18;">Halter Neck with Adjustable Tie-Up Back Tassels</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Sleeve Type</td><td style="padding:10px 14px; color:#1B1A18;">Sleeveless (Comfort Armhole Cut)</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Length</td><td style="padding:10px 14px; color:#1B1A18;">Calf Length (Approx 44 Inches)</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Silhouette / Fit</td><td style="padding:10px 14px; color:#1B1A18;">Regular Straight Kurti with Side Slits</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Pattern & Print</td><td style="padding:10px 14px; color:#1B1A18;">Jaipur Traditional Floral Motifs & Coordinated Border</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Item Model Number</td><td style="padding:10px 14px; color:#1B1A18;">Long Kurti B-04</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Amazon ASIN</td><td style="padding:10px 14px; color:#1B1A18;">B0HGR9LQZ9</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Manufacturer</td><td style="padding:10px 14px; color:#1B1A18;">AAVNI DESIGN, Sanganer, Jaipur, Rajasthan</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Country of Origin</td><td style="padding:10px 14px; color:#1B1A18;">India</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Wash Care</td><td style="padding:10px 14px; color:#1B1A18;">Machine wash cold gentle cycle with like colors, warm iron</td></tr>
            <tr style="border-bottom:1px solid #EBE7DF;"><td style="padding:10px 14px; font-weight:600; color:#6E6A63; background:#FAF8F5;">Package Contains</td><td style="padding:10px 14px; color:#1B1A18;">1 Piece Pure Cotton Long Kurti (Net Wt: 200g)</td></tr>
        </tbody>
    </table>

    <div class="aavni-brand-story" style="background:#FAF8F5; border:1px solid #EBE7DF; border-radius:8px; padding:28px; margin-bottom:32px;">
        <h4 style="font-family:\'Playfair Display\', serif; font-size:20px; color:#1B1A18; margin-top:0; margin-bottom:12px;">From The Brand — AAVNI</h4>
        <p style="font-size:15px; line-height:1.8; color:#4A4742; margin-bottom:16px;">
            We believe every woman deserves fashion that feels comfortable, stylish, and uniquely hers. Our collection brings together modern silhouettes, authentic hand-inspired prints, and everyday comfort, crafted for women who love to express their personal style. From graceful kurtis to versatile ethnic coordinates, we focus on quality, breathable pure cotton fabrics, and effortless elegance for every occasion.
        </p>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-top:20px;">
            <div style="background:#FFF; padding:16px; border-radius:6px; border:1px solid #EEE;">
                <strong style="display:block; color:#A85A48; font-size:15px; margin-bottom:4px;">100% Breathable Cotton</strong>
                <span style="font-size:13px; color:#666;">Ultra-soft cambric weave pre-shrunk for lasting shape and zero transparency.</span>
            </div>
            <div style="background:#FFF; padding:16px; border-radius:6px; border:1px solid #EEE;">
                <strong style="display:block; color:#A85A48; font-size:15px; margin-bottom:4px;">Jaipur Artisanal Heritage</strong>
                <span style="font-size:13px; color:#666;">Traditional floral bootas designed by master craftsmen of Rajasthan.</span>
            </div>
            <div style="background:#FFF; padding:16px; border-radius:6px; border:1px solid #EEE;">
                <strong style="display:block; color:#A85A48; font-size:15px; margin-bottom:4px;">Smart Indo-Western Cut</strong>
                <span style="font-size:13px; color:#666;">Halter neck with adjustable back tie-up allows customizable fit across chest.</span>
            </div>
        </div>
    </div>

    <div class="aavni-styling-guide" style="margin-bottom:24px;">
        <h4 style="font-family:\'Playfair Display\', serif; font-size:19px; color:#1B1A18; margin-bottom:10px;">How To Style This Kurti</h4>
        <ul style="font-size:14.5px; line-height:1.7; color:#555; padding-left:20px;">
            <li><strong>Casual Daytime:</strong> Pair with light blue cropped denim jeans, leather juttis, and a tote bag.</li>
            <li><strong>Festive & Evening:</strong> Match with flared off-white cotton palazzos, statement oxidized silver jhumkas, and kohl-rimmed eyes.</li>
            <li><strong>Work & College:</strong> Style with cigarette trousers and minimalist stud earrings for an effortless smart-casual look.</li>
        </ul>
    </div>
</div>
';
$product->set_description($long_desc);

// Set Attributes
$attr_color = new WC_Product_Attribute();
$attr_color->set_id(wc_attribute_taxonomy_id_by_name('color'));
$attr_color->set_name('pa_color');
$attr_color->set_options(array('Printed Brown', 'Indigo Blue', 'Olive Green', 'Dusty Pink'));
$attr_color->set_position(0);
$attr_color->set_visible(true);
$attr_color->set_variation(true);

$attr_size = new WC_Product_Attribute();
$attr_size->set_id(wc_attribute_taxonomy_id_by_name('size'));
$attr_size->set_name('pa_size');
$attr_size->set_options(array('S (36")', 'M (38")', 'L (40")', 'XL (42")', 'XXL (44")'));
$attr_size->set_position(1);
$attr_size->set_visible(true);
$attr_size->set_variation(true);

$product->set_attributes(array($attr_color, $attr_size));
$main_prod_id = $product->save();
echo "Saved Variable Product: AAVNI Floral Printed Halter Neck Long Kurti (ID: $main_prod_id)\n";

// Add Variations
$colors_map = array(
    'Printed Brown' => $img_brown_front,
    'Indigo Blue'   => $img_blue_front,
    'Olive Green'   => $img_olive_front,
    'Dusty Pink'    => $img_pink_front
);
$sizes_list = array('S (36")', 'M (38")', 'L (40")', 'XL (42")', 'XXL (44")');

foreach ($colors_map as $c_name => $c_img_id) {
    foreach ($sizes_list as $s_name) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($main_prod_id);
        $variation->set_attributes(array(
            'pa_color' => sanitize_title($c_name),
            'pa_size'  => sanitize_title($s_name)
        ));
        $variation->set_regular_price('999');
        $variation->set_sale_price('499');
        $variation->set_price('499');
        $variation->set_sku('AAV-LK-B04-' . strtoupper(substr(sanitize_title($c_name), 0, 3)) . '-' . strtoupper(substr($s_name, 0, 2)));
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity(15);
        $variation->set_stock_status('instock');
        if ($c_img_id) {
            $variation->set_image_id($c_img_id);
        }
        $var_id = $variation->save();
    }
}
echo "Added all Color & Size variations for Amazon Long Kurti.\n";

// 2. CREATE 5 MORE LONG KURTI PRODUCTS TO POPULATE THE CATEGORY PAGE RICHLY
$additional_kurtis = array(
    array(
        'title' => 'AAVNI Indigo Blue Handblock Floral Sleeveless Long Kurti',
        'slug' => 'aavni-indigo-blue-handblock-sleeveless-long-kurti',
        'sku' => 'AAV-LK-005',
        'regular_price' => '999',
        'sale_price' => '499',
        'image' => $img_blue_front,
        'gallery' => array($img_fabric_det),
        'short_desc' => 'Traditional Jaipur Indigo blue handblock floral print on breathable pure cotton with halter tie-up neck and side slits.',
        'full_desc' => '<p>Embrace timeless Indigo elegance. This AAVNI long kurti brings handcrafted Sanganeri block floral prints alive on pure lightweight cotton. Designed with a halter neck and comfort sleeveless cut for warm Indian summers.</p><ul><li><strong>Fabric:</strong> 100% Breathable Cotton</li><li><strong>Length:</strong> 44 inches (Calf Length)</li><li><strong>Care:</strong> Wash separately with mild detergent.</li></ul>'
    ),
    array(
        'title' => 'AAVNI Olive Green Ethnic Printed Tie-Up Back Long Kurti',
        'slug' => 'aavni-olive-green-ethnic-printed-long-kurti',
        'sku' => 'AAV-LK-006',
        'regular_price' => '999',
        'sale_price' => '499',
        'image' => $img_olive_front,
        'gallery' => array($img_fabric_det),
        'short_desc' => 'Earth-inspired olive green floral kurti with back tie-up tassels, straight silhouette, and side slits.',
        'full_desc' => '<p>Earth tones meet modern grace. This AAVNI olive green ethnic kurti is tailored with floral bootas and a halter neck placket. Perfect for work, college, and weekend outings.</p><ul><li><strong>Fabric:</strong> Pure Cotton Cambric</li><li><strong>Fit:</strong> Straight Comfort Fit</li><li><strong>Country of Origin:</strong> India</li></ul>'
    ),
    array(
        'title' => 'AAVNI Dusty Pink Halter Neck Casual Summer Long Kurti',
        'slug' => 'aavni-dusty-pink-halter-neck-long-kurti',
        'sku' => 'AAV-LK-007',
        'regular_price' => '999',
        'sale_price' => '499',
        'image' => $img_pink_front,
        'gallery' => array($img_fabric_det),
        'short_desc' => 'Pastel floral dusty pink long kurti with tie-up back, breathable cotton texture, and side slits.',
        'full_desc' => '<p>Soft pastel blush with botanical jaal prints. Features halter neck ties, side ease slits, and breathable pure cotton drape for relaxed summer days.</p><ul><li><strong>Fabric:</strong> 100% Soft Cotton</li><li><strong>Neck:</strong> Halter Neck</li><li><strong>Length:</strong> Calf Length</li></ul>'
    ),
    array(
        'title' => 'AAVNI Sanganeri Monochrome Floral Cotton Straight Long Kurti',
        'slug' => 'aavni-sanganeri-monochrome-floral-cotton-long-kurti',
        'sku' => 'AAV-LK-008',
        'regular_price' => '1099',
        'sale_price' => '549',
        'image' => $img_black_front,
        'gallery' => array(),
        'short_desc' => 'Classic ivory and black Sanganeri handblock print long kurti with 3/4 sleeves and comfortable side slits.',
        'full_desc' => '<p>A classic statement of monochrome artistry. Crafted from pure cotton featuring intricate Jaipur floral foliage block prints, round henley neckline, and 3/4 sleeves with border piping.</p><ul><li><strong>Fabric:</strong> 100% Pure Cotton</li><li><strong>Sleeves:</strong> 3/4th Sleeves</li><li><strong>Length:</strong> 44 inches</li></ul>'
    ),
    array(
        'title' => 'AAVNI Crimson Maroon Gold Foil Print Straight Long Kurti',
        'slug' => 'aavni-crimson-maroon-gold-foil-print-long-kurti',
        'sku' => 'AAV-LK-009',
        'regular_price' => '1299',
        'sale_price' => '699',
        'image' => $img_maroon_front,
        'gallery' => array(),
        'short_desc' => 'Rich crimson maroon pure cotton kurti with delicate gold foil ethnic borders and keyhole neckline.',
        'full_desc' => '<p>Rich festive grace in breathable natural cotton. Features auspicious crimson maroon with gold foil accents along the yoke, sleeve borders, and hemline.</p><ul><li><strong>Fabric:</strong> 100% Fine Cotton</li><li><strong>Occasion:</strong> Festive, Puja, & Celebrations</li><li><strong>Length:</strong> Calf Length</li></ul>'
    )
);

foreach ($additional_kurtis as $item) {
    $existing = wc_get_product_id_by_sku($item['sku']);
    if ($existing) {
        wp_delete_post($existing, true);
    }
    $p = new WC_Product_Simple();
    $p->set_name($item['title']);
    $p->set_slug($item['slug']);
    $p->set_sku($item['sku']);
    $p->set_regular_price($item['regular_price']);
    $p->set_sale_price($item['sale_price']);
    $p->set_price($item['sale_price']);
    $p->set_description($item['full_desc']);
    $p->set_short_description($item['short_desc']);
    $p->set_status('publish');
    $p->set_catalog_visibility('visible');
    $p->set_manage_stock(true);
    $p->set_stock_quantity(20);
    $p->set_stock_status('instock');
    $p->set_category_ids(array($cat_id));
    if ($item['image']) {
        $p->set_image_id($item['image']);
    }
    if (!empty($item['gallery'])) {
        $p->set_gallery_image_ids($item['gallery']);
    }
    $new_id = $p->save();
    echo "Created Long Kurti: {$item['title']} (ID: $new_id)\n";
}

echo "=== Amazon Long Kurti Catalog Population Complete! ===\n";
