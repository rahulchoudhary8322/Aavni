<?php
/**
 * AAVNI Intelligent Product Search System
 * 
 * Features:
 * - Morphological Stemming (Plural <-> Singular for Indian apparel & fashion terms)
 * - Deep Multi-Facet Search: Titles, Short Descriptions, Categories, Tags, SKU & Attributes
 * - Accurate Content Matching (Eliminates CSS/HTML false positives like 'margin-top')
 * - Priority Relevance Ordering (Exact Category > Exact Title > Words > Excerpt)
 * - Instant Live AJAX Product Search with Images, Prices & Category Badges
 * - Popular Searches Quick Chips
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Intelligent Morphological Stemming for Fashion & Ethnic Wear
 */
function aavni_get_stemmed_search_variants($search_term) {
    $search_term = trim(strtolower($search_term));
    if (empty($search_term)) {
        return array();
    }

    $words = array_filter(preg_split('/\s+/', $search_term));
    $all_variants = array($search_term);

    // Common fashion, fabric & Indian ethnic wear mappings
    $fashion_dictionary = array(
        'tops'      => array('top', 'tops', 'tunic', 'tunics'),
        'top'       => array('top', 'tops', 'tunic', 'tunics'),
        'tunic'     => array('tunic', 'tunics', 'top', 'tops'),
        'tunics'    => array('tunic', 'tunics', 'top', 'tops'),
        'kurtis'    => array('kurti', 'kurtis', 'kurta', 'kurtas'),
        'kurti'     => array('kurti', 'kurtis', 'kurta', 'kurtas'),
        'kurta'     => array('kurti', 'kurtis', 'kurta', 'kurtas'),
        'kurtas'    => array('kurti', 'kurtis', 'kurta', 'kurtas'),
        'nighties'  => array('nighty', 'nighties', 'nightwear', 'loungewear'),
        'nighty'    => array('nighty', 'nighties', 'nightwear', 'loungewear'),
        'nightwear' => array('nighty', 'nighties', 'nightwear', 'loungewear'),
        'dresses'   => array('dress', 'dresses'),
        'dress'     => array('dress', 'dresses'),
        'suits'     => array('suit', 'suits'),
        'suit'      => array('suit', 'suits'),
        'pants'     => array('pant', 'pants', 'trousers', 'trouser'),
        'pant'      => array('pant', 'pants', 'trousers', 'trouser'),
        'trousers'  => array('pant', 'pants', 'trousers', 'trouser'),
        'palazzos'  => array('palazzo', 'palazzos'),
        'palazzo'   => array('palazzo', 'palazzos'),
        'dupattas'  => array('dupatta', 'dupattas'),
        'dupatta'   => array('dupatta', 'dupattas'),
        'stripes'   => array('stripe', 'striped', 'stripes'),
        'striped'   => array('stripe', 'striped', 'stripes'),
        'stripe'    => array('stripe', 'striped', 'stripes'),
        'cottons'   => array('cotton', 'cottons'),
        'cotton'    => array('cotton', 'cottons'),
        'prints'    => array('print', 'printed', 'prints'),
        'printed'   => array('print', 'printed', 'prints'),
        'print'     => array('print', 'printed', 'prints'),
        'florals'   => array('floral', 'florals'),
        'floral'    => array('floral', 'florals'),
        'sleeveless'=> array('sleeveless', 'sleeves', 'sleeve'),
        'sleeves'   => array('sleeveless', 'sleeves', 'sleeve'),
        'halters'   => array('halter', 'halters'),
        'halter'    => array('halter', 'halters'),
    );

    foreach ($words as $w) {
        $variants = array($w);
        if (isset($fashion_dictionary[$w])) {
            $variants = array_merge($variants, $fashion_dictionary[$w]);
        } else {
            // General English plural/singular rules
            if (strlen($w) > 3) {
                if (substr($w, -3) === 'ies') {
                    $variants[] = substr($w, 0, -3) . 'y';
                } elseif (substr($w, -2) === 'es') {
                    $variants[] = substr($w, 0, -2);
                } elseif (substr($w, -1) === 's') {
                    $variants[] = substr($w, 0, -1);
                } else {
                    $variants[] = $w . 's';
                }
            }
        }
        $all_variants = array_merge($all_variants, $variants);
    }

    return array_values(array_unique(array_filter($all_variants)));
}

/**
 * 2. Deep Multi-Facet Search Filter (Hooks into WordPress posts_search)
 */
function aavni_enhance_product_search_query($search, $query) {
    global $wpdb;

    if (empty($search)) {
        return $search;
    }

    // Target WooCommerce Product Searches
    $post_type = $query->get('post_type');
    $is_product_search = false;

    if ($post_type === 'product' || (is_array($post_type) && in_array('product', $post_type))) {
        $is_product_search = true;
    }
    if (!$is_product_search && isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
        $is_product_search = true;
    }

    if ($query->is_search() && ($is_product_search || is_woocommerce() || is_shop())) {
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }

        $variants = aavni_get_stemmed_search_variants($search_term);
        if (empty($variants)) {
            return $search;
        }

        $clauses = array();

        foreach ($variants as $variant) {
            $like = '%' . $wpdb->esc_like($variant) . '%';
            $regex_word = '[[:space:]]+' . preg_quote($variant, '/') . '([[:space:][:punct:]]|$)';

            // 1. Post Title (LIKE matches are great here because titles have no HTML)
            $title_match = $wpdb->prepare("wp_posts.post_title LIKE %s", $like);

            // 2. Post Excerpt (Short description, plain text)
            $excerpt_match = $wpdb->prepare("wp_posts.post_excerpt LIKE %s", $like);

            // 3. Post Content (Using regex word match to prevent matching HTML tags/styles like 'margin-top')
            $content_match = $wpdb->prepare("wp_posts.post_content REGEXP %s", $regex_word);

            // 4. Product Categories & Tags (product_cat & product_tag)
            $tax_match = $wpdb->prepare("wp_posts.ID IN (
                SELECT tr.object_id FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE tt.taxonomy IN ('product_cat', 'product_tag')
                AND (t.name LIKE %s OR t.slug LIKE %s)
            )", $like, $like);

            // 5. Product SKU
            $sku_match = $wpdb->prepare("wp_posts.ID IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_sku' AND meta_value LIKE %s
            )", $like);

            $clauses[] = "($title_match OR $excerpt_match OR $content_match OR $tax_match OR $sku_match)";
        }

        if (!empty($clauses)) {
            $search = " AND (" . implode(" OR ", $clauses) . ") ";
        }
    }

    return $search;
}
add_filter('posts_search', 'aavni_enhance_product_search_query', 20, 2);

/**
 * 3. Relevance Ordering for Product Searches (Prioritize Category > Title > Excerpt > Content)
 */
function aavni_enhance_product_search_orderby($orderby, $query) {
    global $wpdb;

    if ($query->is_search() && ($query->get('post_type') === 'product' || (isset($_GET['post_type']) && $_GET['post_type'] === 'product'))) {
        $search_term = $query->get('s');
        if (!empty($search_term)) {
            $term_clean = $wpdb->esc_like($search_term);
            $exact_like = '%' . $term_clean . '%';
            $starts_with = $term_clean . '%';

            $custom_order = $wpdb->prepare(
                "CASE 
                    -- Category match gets highest priority
                    WHEN wp_posts.ID IN (
                        SELECT tr.object_id FROM {$wpdb->term_relationships} tr
                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                        WHERE tt.taxonomy = 'product_cat' AND (t.name LIKE %s OR t.slug LIKE %s)
                    ) THEN 1
                    -- Title starts with search term
                    WHEN wp_posts.post_title LIKE %s THEN 2
                    -- Title contains search term
                    WHEN wp_posts.post_title LIKE %s THEN 3
                    -- Excerpt contains search term
                    WHEN wp_posts.post_excerpt LIKE %s THEN 4
                    ELSE 5
                END ASC, wp_posts.post_date DESC",
                $exact_like,
                $exact_like,
                $starts_with,
                $exact_like,
                $exact_like
            );

            return $custom_order;
        }
    }

    return $orderby;
}
add_filter('posts_orderby', 'aavni_enhance_product_search_orderby', 20, 2);

/**
 * 4. Fast AJAX Live Search API Endpoint
 */
function aavni_ajax_live_search() {
    $search_term = isset($_REQUEST['term']) ? sanitize_text_field($_REQUEST['term']) : '';
    if (empty($search_term) || strlen($search_term) < 2) {
        wp_send_json_success(array('count' => 0, 'products' => array(), 'categories' => array()));
    }

    // 1. Search matching product categories
    $matching_categories = array();
    $variants = aavni_get_stemmed_search_variants($search_term);

    $cat_terms = array();
    foreach ($variants as $var) {
        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'name__like' => $var,
        ));
        if (!empty($terms) && !is_wp_error($terms)) {
            $cat_terms = array_merge($cat_terms, (array)$terms);
        }
    }

    if (!empty($cat_terms)) {
        $seen_cats = array();
        foreach ($cat_terms as $cat) {
            if ($cat->slug === 'uncategorized' || isset($seen_cats[$cat->term_id])) {
                continue;
            }
            $seen_cats[$cat->term_id] = true;
            $matching_categories[] = array(
                'id'    => $cat->term_id,
                'name'  => $cat->name,
                'count' => $cat->count,
                'url'   => get_term_link($cat),
            );
        }
    }

    // 2. Query products using our enhanced query
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        's'              => $search_term,
    );

    $product_query = new WP_Query($args);
    $products = array();

    if ($product_query->have_posts()) {
        while ($product_query->have_posts()) {
            $product_query->the_post();
            $product = wc_get_product(get_the_ID());
            if (!$product) continue;

            // Thumbnail
            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src();

            // Categories
            $cats = wp_get_post_terms(get_the_ID(), 'product_cat', array('fields' => 'names'));
            $cat_name = !empty($cats) ? $cats[0] : '';

            $products[] = array(
                'id'         => get_the_ID(),
                'title'      => get_the_title(),
                'permalink'  => get_permalink(),
                'image'      => $image_url,
                'category'   => $cat_name,
                'price_html' => $product->get_price_html(),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'term'         => $search_term,
        'count'        => $product_query->found_posts,
        'products'     => $products,
        'categories'   => $matching_categories,
        'all_url'      => add_query_arg(array('s' => $search_term, 'post_type' => 'product'), home_url('/')),
    ));
}
add_action('wp_ajax_aavni_live_search', 'aavni_ajax_live_search');
add_action('wp_ajax_nopriv_aavni_live_search', 'aavni_ajax_live_search');

/**
 * 5. Keep Search Results Archive Page Visible (Do Not Auto-Redirect to Single Product)
 */
add_filter('woocommerce_redirect_single_search_result', '__return_false');

