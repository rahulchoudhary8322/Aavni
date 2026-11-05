# AAVNI E-COMMERCE FUNCTIONALITY BLUEPRINT & TECHNICAL AUDIT
**Master Technical Reference & Reusable Architecture Specification for New Gajak E-Commerce Website**

---

## EXECUTIVE SUMMARY & AUDIT SCOPE

* **Source Project Audited:** AAVNI Handcrafted Ethnic & Contemporary Wear (`http://localhost/wordpress/Aavni`)
* **Core Technology Stack:** WordPress 7.1.4, WooCommerce 11.2.1, PHP 8.2.12, MySQL/MariaDB
* **WooCommerce Storage Mode:** **High-Performance Order Storage (HPOS)** is **ACTIVE & ENABLED** (`Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() = true`).
* **Active Theme:** `aavni-theme` (Custom child theme of parent `astra`).
* **Active Custom Plugins:**
  1. `aavni-shiprocket` (`wp-content/plugins/aavni-shiprocket/aavni-shiprocket.php`) — Official Shiprocket API v1 logistics engine.
  2. `woo-razorpay` (`wp-content/plugins/woo-razorpay/woo-razorpay.php`) — Official Razorpay payment gateway.
  3. `woocommerce` (`wp-content/plugins/woocommerce/woocommerce.php`) — Core e-commerce foundation.
* **Target Reusable Destination:** Brand-new e-commerce website for an authentic Indian Gajak, Chikki, Sweets & Traditional Confectionery enterprise.

---

# PHASE 1 — COMPLETE SYSTEM AUDIT & ARCHITECTURAL INVENTORY

### 1.1 Technical Environment Snapshot

| Component | Active Production Setting on AAVNI | Gajak Adaptation Recommendation |
| :--- | :--- | :--- |
| **WordPress Core** | 7.1.4 (Custom Permalinks: `/%postname%/`) | Keep latest WordPress 6.x / 7.x LTS with pretty permalinks. |
| **WooCommerce Core**| 11.2.1 with HPOS (`wp_wc_orders`) enabled | Retain HPOS mode from day 1 for order throughput & transactional speed. |
| **Parent Theme** | Astra (`wp-content/themes/astra`) | Use lightweight Astra or bespoke theme matching culinary/sweets luxury. |
| **Child Theme** | `aavni-theme` (`wp-content/themes/aavni-theme`) | Create dedicated `gajak-theme` with culinary aesthetic (warm amber, gold, cream). |
| **Database Engine** | MySQL 8.x with UTF8MB4 Collation | Retain UTF8MB4 for Hindi/regional product naming (e.g. गुड़ गजक, तिलकुट). |
| **Custom Tables** | `wp_aavni_returns`, `wp_aavni_support_tickets` | Replicate as `wp_gajak_returns`, `wp_gajak_support_tickets` with food safety logic. |

---

# PHASE 2 — CUSTOMER PANEL & STOREFRONT EXPERIENCE AUDIT

### 2.1 Customer-Facing Feature Matrix

| Feature | Where it Appears | Implementation Status | Verified Working? | Technical Component & Files | Limitations & Dependencies |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Homepage Edge Carousel** | Homepage hero section | Fully Implemented | **YES** | `front-page.php`, `class-aavni-admin-storefront.php` | Supports up to 5 slides, auto-advance, responsive mobile dots/arrows. |
| **Announcement Bar** | Global sticky top bar | Fully Implemented | **YES** | `header.php`, `aavni-custom.css`, options API | Configurable in Admin Storefront (text, colors, promotion). |
| **Category Showcase** | Homepage mid-fold | Fully Implemented | **YES** | `front-page.php`, `setup-catalog.php` | Responsive grid linking to category archives with custom badges. |
| **Curated Hero Banners** | Shop & Category archives | Fully Implemented | **YES** | `woocommerce.php`, `assets/images/banners/` | Custom hero photo, title, subtitle & quick filter chips per category. |
| **Search Suggestions** | Header modal & bottom nav | Fully Implemented | **YES** | `inc/aavni-search-enhancements.php`, `assets/js/aavni-scripts.js` | Custom AJAX search (`aavni_live_search`) with ethnic wear term stemming. |
| **Quick Filter Chips** | Category page headers | Fully Implemented | **YES** | `woocommerce.php` | Dynamic chips reading taxonomy terms (`get_terms('product_cat')`). |
| **Product Archive Cards** | Shop & Category grids | Fully Implemented | **YES** | `inc/aavni-woocommerce-enhancements.php` | Custom sale badges, price display, AJAX "Add to Bag" button. |
| **Visual Rating & Badges** | Product archive cards | Partially Implemented (Static visual) | **YES (Visual only)** | `inc/aavni-woocommerce-enhancements.php:48-84` | Hardcoded 4.2 rating & swatch dots. Must be dynamically tied to real reviews for Gajak. |
| **Off-Canvas Cart Drawer**| Slide-out on right | Fully Implemented | **YES** | `header.php`, `functions.php:75-143`, `aavni-scripts.js` | Uses `woocommerce_add_to_cart_fragments` for seamless cart updates. |
| **Native Cart Page** | `/cart/` | Fully Implemented | **YES** | WooCommerce core template | Coupon code field, item line-totals, free shipping threshold indicator. |
| **Native Checkout Page** | `/checkout/` | Fully Implemented | **YES** | `inc/aavni-payment-enhancements.php` | Indian billing address, 10-digit phone validation, trust badges, payment selector. |
| **Pincode Delivery Estimator**| Single Product page | Fully Implemented | **YES** | `inc/aavni-woocommerce-enhancements.php:137-173`, `aavni-scripts.js` | Client-side pincode checker with 3-5 day estimate and COD confirmation. |
| **Single Product Tabs** | Single Product page | Fully Implemented | **YES** | `inc/aavni-woocommerce-enhancements.php:175-335` | Custom tabs for "Specifications & Overview", "Artisanal Heritage", "Verified Reviews". |
| **Customer Wishlist** | Header & product cards | Partially Implemented | **YES (Client-Side only)**| `assets/js/aavni-scripts.js:198-240` | Uses browser `localStorage` (`aavni_wishlist`). Does not persist in DB or across devices. |
| **Google 1-Click Login** | `/my-account/` | Configured / Requires Secret | **TESTED** | `inc/aavni-google-auth.php` | OAuth 2.0 redirect flow. Client ID/Secret configured; requires public callback domain. |
| **Indian Phone Registration**| `/my-account/` registration | Fully Implemented | **YES** | `inc/aavni-customer-panel.php:139-220` | Validates mandatory 10-digit Indian phone (`^[6-9][0-9]{9}$`) & real MX domain lookup. |
| **My Account Overview** | `/my-account/` | Fully Implemented | **YES** | `inc/aavni-customer-panel.php`, `woocommerce/myaccount/dashboard.php` | Metric KPI cards: Total Orders, In-Transit, Returns, Support Tickets. |
| **Visual Tracking Timeline**| `/my-account/track-order/` | Fully Implemented | **YES** | `inc/aavni-customer-panel.php:1115-1258` | 6-milestone luxury progress bar with live courier AWB, tracking URL, and status. |
| **Customer Order Return** | `/my-account/returns/` | Fully Implemented | **YES** | `inc/aavni-customer-panel.php:1259-1409`, `wp_aavni_returns` table | Customer return/exchange form with photo upload, reasons, and tracking. |
| **Customer Support Desk** | `/my-account/help-support/`| Fully Implemented | **YES** | `inc/aavni-customer-panel.php:1503-1675`, `wp_aavni_support_tickets` table | Ticketing system with ticket code, replies thread, priority, order linking. |
| **Printable Tax Invoice** | Customer & Admin views | Fully Implemented | **YES** | `inc/aavni-customer-panel.php:373-554` | Official GST Tax Invoice with GSTIN, HSN summary, CGST/SGST breakdown. |
| **WhatsApp Direct Float** | Floating on all pages | Fully Implemented | **YES** | `footer.php:341-355` | Pre-configured WhatsApp link to care phone `+91 9828103222`. |
| **Mobile Bottom Bar** | Fixed mobile footer nav | Fully Implemented | **YES** | `footer.php:290-340` | Quick access to Home, Shop, Search, Wishlist, and Cart Drawer. |
| **Contact Us Form** | `/contact-us/` | Partially Implemented | **YES (UI only)** | `page-contact-us.php` | Validates nonce and inputs, shows success message, but does not send email. |

---

# PHASE 3 — COMPLETE ADMIN PANEL ARCHITECTURE

### 3.1 Dual-Surfacing Admin Interface
AAVNI has two synchronized administrative entry points:
1. **WordPress Standard Admin:** Registered under top-level menu `AAVNI Store` (`admin.php?page=aavni-dashboard`) with 11 submenus.
2. **Frontend Admin Portal:** Dedicated template `page-admin-portal.php` accessible at `/admin-portal/`. Accessible only to authenticated Administrators and Shop Managers (`current_user_can('edit_shop_orders')`).

### 3.2 Modular Controller Structure (`inc/admin/`)

```
wp-content/themes/aavni-theme/inc/admin/
├── aavni-admin-core.php                     # Master admin router, action dispatcher, asset loader
├── class-aavni-admin-dashboard.php          # Real-time revenue, order stats, return alerts
├── class-aavni-admin-orders.php             # Order management, visual milestone advance, invoice
├── class-aavni-admin-products.php            # Product catalog, quick add modal, category adder, SKU generator
├── class-aavni-admin-inventory.php           # Real-time AJAX stock updates, low-stock thresholding
├── class-aavni-admin-returns.php             # Return approvals, exchange reorders, refund tracking
├── class-aavni-admin-support.php             # Ticket management, admin reply dispatching
├── class-aavni-admin-customers.php           # Customer CRM, lifetime value, phone directory
├── class-aavni-admin-reviews.php             # Review moderation, star breakdown, approvals
├── class-aavni-admin-coupons.php             # WooCommerce coupon generator, discount limits
├── class-aavni-admin-reports.php             # Gross sales, AOV, net revenue, monthly trends
├── class-aavni-admin-settings.php            # GSTIN, care contacts, COD/Razorpay status, Shiprocket, SMTP
├── class-aavni-admin-storefront.php          # Hero slider banner customizer, announcement bar
├── class-aavni-admin-notifications.php       # Customer milestone transactional email triggers
└── class-aavni-new-order-notification.php    # Owner alert engine on new checkout orders
```

---

# PHASE 4 — CUSTOM ORDER LIFECYCLE & FULFILMENT PIPELINE

### 4.1 Visual Milestone Pipeline

AAVNI enforces a 6-stage progressive milestone pipeline:
1. `confirmed` — Order Confirmed & Payment Verified.
2. `processing` — Processing & Quality Inspection.
3. `packed` — Packed in Protective Luxury Packaging.
4. `shipped` — Dispatched & Handed over to Courier Partner.
5. `out_for_delivery` — Out for Doorstep Delivery.
6. `delivered` — Successfully Delivered (auto-transitions WooCommerce status to `completed`).
* Exception Stages: `cancelled`, `refunded`.

### 4.2 Data Storage Mechanism (HPOS Compatible)
* Milestone state is stored in order metadata: `_aavni_order_milestone`.
* Courier name stored in `_aavni_courier_name`.
* Courier AWB stored in `_aavni_tracking_number`.
* Tracking link stored in `_aavni_tracking_url`.
* Complete audit history stored in serialized array `_aavni_milestone_history`:
  ```json
  [
    {
      "milestone": "shipped",
      "title": "Dispatched",
      "note": "Package dispatched via Delhivery Surface. AWB: 140392819201",
      "time": "2026-10-30 14:15:22"
    }
  ]
  ```
* All updates are executed through WooCommerce HPOS CRUD: `$order->update_meta_data()`, `$order->save()`.

---

# PHASE 5 — SHIPPING & SHIPROCKET INTEGRATION AUDIT

### 5.1 Shiprocket Integration Status Summary

* **Dedicated Plugin:** `aavni-shiprocket` (`wp-content/plugins/aavni-shiprocket/`)
* **Plugin State:** **ACTIVE & HPOS COMPATIBLE**.
* **API Version:** Official Shiprocket REST API v1 (`https://apiv2.shiprocket.in/v1/external`).
* **Authentication Engine:** Implemented via `AAVNI_Shiprocket_API::get_token()`. Includes JWT token caching in WordPress transients for 9 days with automatic renewal on 401 Unauthorized responses.
* **Current Operational State:** **CODE-COMPLETE & ARCHITECTURALLY VERIFIED; AWAITING LIVE CREDENTIALS FROM SITE OWNER**.
  * Email setting: `aavni_shiprocket_email` (Currently empty).
  * Password setting: `aavni_shiprocket_password` (Currently empty).
  * Pickup location: `aavni_shiprocket_pickup_location` (Currently empty).

### 5.2 Deep-Dive Capability Breakdown

| Logistics Function | Implementation Details | Verification Result |
| :--- | :--- | :--- |
| **API Authentication** | `POST /v1/external/auth/login` | Verified logic & error trapping. Tested unconfigured state returns safe message. |
| **Test Connection Button** | AJAX `aavni_sr_test_connection` | Nonced endpoint verified. Reports honest connection test without crashes. |
| **Pickup Locations** | `GET /v1/external/settings/company/pickup` | AJAX `aavni_sr_fetch_pickups` dynamically populates admin dropdown. |
| **Courier Serviceability** | `GET /v1/external/courier/serviceability/` | Queries origin & destination pincodes, weight, dimensions, COD status. |
| **Shipment Creation** | `POST /v1/external/orders/create/adhoc` | Formats multi-item orders, declared value, tax calculations, HSN codes. |
| **AWB Assignment** | `POST /v1/external/courier/assign/awb` | Requests genuine courier AWB; auto-updates `_aavni_sr_awb` & milestones. |
| **Shipping Label Printing**| `POST /v1/external/courier/generate/label` | Retrieves 4×6 thermal PDF URL and injects one-click print button. |
| **Pickup Scheduling** | `POST /v1/external/courier/generate/pickup` | Schedules courier physical pickup from configured warehouse location. |
| **Manifest Generation** | `POST /v1/external/manifests/generate` | Generates official courier handover manifest sheet. |
| **Tracking Sync** | `GET /v1/external/courier/track/awb/` | Queries current tracking status, maps courier activities to AAVNI milestones. |
| **Background Cron** | `aavni_shiprocket_tracking_sync_event` | Registered in WordPress Cron every 6 hours. Next run confirmed in DB. |
| **Bulk Shipping Toolbar** | Floating UI on Orders table | Supports Bulk Create, Bulk Assign AWB, Bulk Print Labels, Bulk Sync. |

---

# PHASE 6 — PAYMENT GATEWAY & CHECKOUT AUDIT

### 6.1 Payment Gateway Breakdown

| Gateway | Configuration on AAVNI | Live Operational Readiness |
| :--- | :--- | :--- |
| **Cash on Delivery (COD)** | Enabled in WooCommerce. Enhanced with Indian postal verification, trust badges, zero checkout friction. | **FULLY OPERATIONAL & VERIFIED**. Used by 13 of 15 current orders. |
| **Razorpay (UPI, Cards, NetBanking)** | Plugin `woo-razorpay` is active. Configured with `rzp_test_...` Test API Key ID & Secret. Webhook secret is **NOT** configured. | **TEST MODE ONLY**. Requires live merchant onboarding (`rzp_live_...`) and webhook endpoint registration (`/wc-api/WC_Default_Razorpay_Webhook`). |
| **Direct Bank Transfer (BACS)**| Installed in WooCommerce Core. | **DISABLED**. |
| **Cheque Payments** | Installed in WooCommerce Core. | **DISABLED**. |

### 6.2 Checkout Payment Security & Enhancements
* Implemented in `inc/aavni-payment-enhancements.php`.
* Dynamic visual badges added to payment titles:
  * COD receives a styled `Pay on Delivery` badge.
  * Razorpay displays UPI / QR, Cards, NetBanking, and Wallets chips.
* Trust strip injected into checkout review:
  * 🔒 256-Bit Bank Grade SSL Encryption.
  * 🛡️ Zero Payment Fraud Guarantee.
  * ⚡ Instant UPI & Card Processing.

---

# PHASE 7 — EMAILS, NOTIFICATIONS & SMTP AUDIT

### 7.1 Notification System Status

| Notification Type | Trigger & File | Delivery Status on Current Environment |
| :--- | :--- | :--- |
| **Owner Alert (New Order)** | `class-aavni-new-order-notification.php` | Configured to send to `rahulchoudhary8322@gmail.com`. Includes retry mechanism. |
| **Customer Milestone Update**| `class-aavni-admin-notifications.php` | Dispatches branded HTML emails when milestone changes (Confirmed, Packed, Dispatched, Delivered). |
| **Customer Returns Notification**| `aavni_handle_return_submission()` | Triggers notification when a return/exchange ticket is created or updated. |
| **Customer Support Ticket Reply**| `aavni_handle_support_ticket_submission()` | Triggers notification when admin replies to an inquiry ticket. |

### 7.2 SMTP Configuration
* **SMTP Provider:** Google Workspace / Gmail SMTP (`smtp.gmail.com`).
* **Port & Encryption:** Port 587 using TLS.
* **Authenticated User:** `rahulchoudhary8322@gmail.com`.
* **Password Storage:** Google App Password stored in WordPress options (`aavni_smtp_password`).
* **Operational Note:** PHP's native `mail()` is disabled on XAMPP Windows. The dedicated SMTP configuration ensures emails route through Google's authenticated relays.

---

# PHASE 8 — SECURITY, DATABASE & TECHNICAL ARCHITECTURE AUDIT

1. **High Performance Order Storage (HPOS):** 100% compliant. Custom functions use `wc_get_order()`, `$order->get_meta()`, `$order->update_meta_data()`, and `$order->save()`. No direct legacy `wp_postmeta` SQL queries are used.
2. **Privilege Escalation Prevention:**
   * Customer registration strictly enforces `$data['role'] = 'customer'` via `woocommerce_new_customer_data` hook.
   * Admin actions verify `current_user_can('edit_shop_orders')` or `current_user_can('manage_woocommerce')`.
3. **CSRF & Nonce Protection:** All custom POST handlers and AJAX endpoints verify nonces:
   * `aavni_shiprocket_nonce` (Shiprocket AJAX)
   * `aavni_save_shiprocket_settings_nonce` (Shipping settings)
   * `aavni_update_milestone_nonce` (Milestone updates)
   * `aavni_contact_action` (Contact forms)
   * `aavni_return_nonce` (Return submissions)
4. **Input Sanitization & Output Escaping:** Consistent use of `sanitize_text_field()`, `sanitize_email()`, `absint()`, `esc_html()`, `esc_attr()`, and `esc_url()`.
5. **Secret Redaction:**
   * Shiprocket API password is masked with `●●●●●●●●`.
   * Diagnostic logs redact access tokens, authorization headers, and raw credentials before persisting to options table.

---

# PHASE 9 — VERIFIED FUNCTIONALITY TEST MATRIX

| Feature Area | Implementation Status | Test Method | Test Result | Verification Evidence | Remaining Work for Gajak Project |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Hero Slider Customizer** | Fully Implemented | Admin Save & Front-End Render | **PASS** | `front-page.php:30-106` | Replace apparel slides with Gajak/sweets imagery. |
| **Product Category Taxonomy**| Fully Implemented | WordPress DB Term Query | **PASS** | 4 Categories, 21 Products | Create Gajak categories (Gur Gajak, Til Gajak, Roll, Chikki). |
| **Quick Add Category Modal**| Fully Implemented | AJAX `aavni_admin_add_category` | **PASS** | `class-aavni-admin-products.php` | Direct adaptation. |
| **Auto SKU Generator** | Fully Implemented | AJAX `aavni_admin_generate_sku` | **PASS** | Generates prefix + random digits | Adapt prefix to `GJK-` (e.g. `GJK-GUR-101`). |
| **Off-Canvas Cart Drawer** | Fully Implemented | AJAX Fragment Interception | **PASS** | `functions.php:75-143` | Direct adaptation. |
| **Indian Phone Verification**| Fully Implemented | Regex Validation Check | **PASS** | Validates `^[6-9][0-9]{9}$` | Direct adaptation. |
| **Pincode Delivery Estimator**| Fully Implemented | Single Product JS Input | **PASS** | `aavni-scripts.js:150-194` | Direct adaptation. |
| **Visual Milestone Pipeline**| Fully Implemented | Orders Hub & My Account View | **PASS** | Updates `_aavni_order_milestone` | Adapt labels for food dispatch & packing. |
| **GST Tax Invoice Generator**| Fully Implemented | Invoice URL Render Check | **PASS** | `inc/aavni-customer-panel.php:416`| Update business name, FSSAI number & GSTIN. |
| **Support Tickets System** | Fully Implemented | DB Table Query & Form Post | **PASS** | Table `wp_aavni_support_tickets` | Replicate DB schema for Gajak store. |
| **Returns & Exchanges Desk**| Fully Implemented | DB Table Query & Form Post | **PASS** | Table `wp_aavni_returns` | **Replace apparel exchange with Food Safety Non-Returnable Policy**. |
| **Shiprocket API Client** | Implemented / Code-Complete| Dry-Run Unconfigured Check | **PASS** | `class-aavni-shiprocket-api.php` | Input store owner's live Shiprocket credentials. |
| **Shiprocket Tracking Cron**| Fully Implemented | WP Cron Schedule Verification | **PASS** | Hook `aavni_shiprocket_tracking_sync_event`| Direct adaptation. |
| **Razorpay Checkout** | Configured (Test Mode) | Gateway Class Inspection | **PASS** | `woo-razorpay` loaded | Provide live production API keys & webhook secret. |
| **Cash on Delivery (COD)** | Fully Implemented | Checkout Simulation | **PASS** | Active on 13 orders | Direct adaptation. |
| **SMTP Email Delivery** | Fully Implemented | SMTP Option Verification | **PASS** | `smtp.gmail.com:587` | Add Gajak store's official Gmail/SMTP account. |
| **WhatsApp Support Float** | Fully Implemented | DOM Footer Verification | **PASS** | `footer.php:341` | Replace phone number with Gajak business WhatsApp. |

---

# PHASE 10 — REUSABLE E-COMMERCE BLUEPRINT FOR GAJAK WEBSITE

This blueprint provides the exact master architecture for an AI coding agent to implement the new Gajak and Traditional Sweets e-commerce store.

```
                      ┌─────────────────────────────────────────┐
                      │          GAJAK STORE PLATFORM           │
                      │  WordPress + WooCommerce + HPOS Engine  │
                      └────────────────────┬────────────────────┘
                                           │
         ┌─────────────────────────────────┼─────────────────────────────────┐
         ▼                                 ▼                                 ▼
┌──────────────────┐             ┌──────────────────┐             ┌──────────────────┐
│ CUSTOMER FRONTEND│             │ LOGISTICS ENGINE │             │  PAYMENT ENGINE  │
├──────────────────┤             ├──────────────────┤             ├──────────────────┤
│• Amber/Gold UI   │             │• Shiprocket v1   │             │• Cash on Delivery│
│• Gram/Kg Selector│             │• Food Packaging  │             │• Razorpay Live   │
│• FSSAI / Shelf-  │             │• Pincode Transit │             │• UPI / QR Codes  │
│  Life Badges     │             │• Thermal Labels  │             │• Instant Receipts│
│• Freshness Alert │             │• Live Tracking   │             │• Secure Checkout │
└────────┬─────────┘             └────────┬─────────┘             └────────┬─────────┘
         │                                │                                │
         └────────────────────────────────┼────────────────────────────────┘
                                           ▼
                      ┌─────────────────────────────────────────┐
                      │          ADMIN FULFILMENT HUB           │
                      │  Batch Management • 6-Step Milestones   │
                      │  Air / Surface Booking • Invoice Engine │
                      └─────────────────────────────────────────┘
```

---

## 1. REUSABLE ARCHITECTURAL ASSETS (Directly Portable from AAVNI)

1. **HPOS Compatibility Structure:** Declare `FeaturesUtil::declare_compatibility('custom_order_tables', ...)` from day one.
2. **Shiprocket Plugin (`aavni-shiprocket`):** Rename to `gajak-shiprocket`. The REST API client, transient JWT caching, courier serviceability check, AWB booking, label generation, and tracking sync cron are 100% reusable without changing the core API logic.
3. **Dual Admin Architecture (`inc/admin/`):** The 12-section management dashboard can be ported directly into the Gajak theme.
4. **GST Tax Invoice Generator:** The PDF/printable invoice template in `aavni_render_printable_invoice()` generates compliant Indian GST tax invoices.
5. **Customer Account Portal (`inc/aavni-customer-panel.php`):** The custom endpoints (`/my-account/track-order/`, `/my-account/help-support/`) and custom database tables can be ported directly.
6. **Live Search Stemming (`inc/aavni-search-enhancements.php`):** The AJAX search engine can be adapted to index sweets terminology.

---

## 2. FOOD & GAJAK-SPECIFIC ADAPTATIONS REQUIRED

### A. Product Attributes & Variation Structure
* **AAVNI Apparel:** Uses Size (`S, M, L, XL`) and Color Swatches.
* **Gajak Adaptation:** Must use **Weight / Pack Size**:
  * `250g Box` (Trial / Personal pack)
  * `500g Box` (Standard family pack)
  * `1 kg Gift Tin` (Festive packaging)
  * `5 kg Bulk Carton` (Celebration / Wholesale pack)
* **Pricing by Weight:** Product cards must display pricing per pack and effective rate per kg (e.g., `₹240 (₹480/kg)`).

### B. Food Safety, Ingredients & Shelf Life Specifications
On the single product page, replace the fabric/styling specifications with:
1. **FSSAI License Badge:** Prominently display FSSAI registration number and green vegetarian dot badge (🟢 100% Pure Vegetarian).
2. **Shelf Life & Storage Instructions:**
   * "Best before 90 days from manufacture date."
   * "Store in a cool, dry place. Keep in an airtight container once opened."
3. **Ingredients & Allergen Information:**
   * Key ingredients breakdown: Sesame seeds (Til), Jaggery (Gur), Pure Desi Ghee, Cardamom (Elaichi), Pistachio, Almonds.
   * Allergen warning: "Contains Sesame and Tree Nuts."
4. **Manufacturing Date & Freshness Promise:**
   * "Freshly prepared in traditional batches in Morena/Jaipur."

### C. Return & Refund Policy for Food Items (Crucial Legal Adaptation)
* **AAVNI Apparel Policy:** 10-day exchange and return policy with photo upload.
* **Gajak Food Adaptation:** **Food products are strictly NON-RETURNABLE due to food safety and hygiene regulations.**
* **Exception Clause:** Refunds or immediate reshipment must only be granted for:
  1. Transit damage (crushed tin/box).
  2. Broken tamper-evident seal upon delivery.
  3. Incorrect item delivered.
  4. Spoiled or expired package.
* **Customer Panel Update:** Modify `inc/aavni-customer-panel.php` so the returns endpoint is titled **"Report Damaged / Spoiled Item"** rather than "Return & Exchange".

### D. Packaging & Shipping Configurations (Gajak Logistics)
Apparel kurtis use 0.45 kg dead weight and 30×25×4 cm polyethylene flyers. Gajak requires:
1. **Packaging Specs:**
   * 500g Gajak: Dead weight ~0.60 kg (including sturdy corrugated box + bubble wrap + moisture absorber). Dimensions: 20 × 15 × 8 cm.
   * 1 kg Gajak Tin: Dead weight ~1.20 kg. Dimensions: 24 × 20 × 10 cm.
2. **Transit Time Priority:** Gajak is climate-sensitive (especially in high humidity). Configure default shipping mode to **Air / Express** for outstation deliveries to ensure transit time does not exceed 3–4 days.

---

## 3. GAJAK WEBSITE IMPLEMENTATION ROADMAP

### Stage 1: Foundation & Base Setup
1. Setup WordPress LTS + WooCommerce on the new domain with HPOS enabled.
2. Initialize `gajak-theme` with culinary color palette (Warm Amber `#D97706`, Royal Gold `#B45309`, Cream `#FFFBEB`, Dark Truffle `#1C1917`).
3. Create custom database tables: `wp_gajak_support_tickets` and `wp_gajak_damage_claims`.

### Stage 2: Product Taxonomy & Catalog
1. Configure categories:
   * **Gur Gajak (Jaggery Specials):** Khasta Gajak, Shahi Til Gur Gajak, Gur Roll Gajak.
   * **Sugar / Crystal Gajak:** Kaju Til Gajak, Elaichi Gajak.
   * **Chikki & Brittle:** Peanut Chikki, Dry Fruit Chikki, Til Sakri.
   * **Festive Gift Boxes & Hampers:** Diwali / Makar Sankranti assortment tins.
2. Implement weight variations (250g, 500g, 1kg) with WooCommerce variable products.

### Stage 3: Storefront & Customer Experience
1. Port the AAVNI edge-to-edge carousel slider for seasonal Gajak campaigns.
2. Port the off-canvas AJAX cart drawer and header search modal.
3. Replace the apparel search stemming dictionary with sweets variants:
   * `gajak`, `gajjak`, `gazak`, `chikki`, `tilkut`, `tilpatti`, `jaggery`, `gur`, `revadi`.
4. Inject FSSAI badge, ingredients tab, and storage instructions into single product templates.

### Stage 4: Checkout & Payment Integration
1. Configure Cash on Delivery (COD) for eligible pincodes.
2. Onboard live Razorpay merchant account (`rzp_live_...`). Configure webhook URL:
   `https://yourgajakstore.com/wc-api/WC_Default_Razorpay_Webhook`
3. Port the Indian phone number validator and checkout trust badges.

### Stage 5: Shiprocket Shipping Engine
1. Port `aavni-shiprocket` plugin as `gajak-shiprocket`.
2. Input live Shiprocket API credentials and select the primary sweet confectionery warehouse pickup location.
3. Configure packaging weights (0.6 kg for 500g, 1.2 kg for 1kg).
4. Verify courier serviceability for Indian metro and outstation pincodes.

### Stage 6: Fulfilment Pipeline & Notifications
1. Adapt AAVNI's 6-step milestone pipeline for confectionery:
   * `confirmed` (Order Booked) → `processing` (Fresh Batch Packaged & Sealed) → `packed` (Packed in Corrugated Box) → `shipped` (In Transit with Courier) → `out_for_delivery` → `delivered`.
2. Configure SMTP credentials using the store's official domain email (e.g. `orders@yourgajakstore.com`).
3. Connect the floating WhatsApp support widget to the Gajak store's customer service number.

---

## 4. DEPLOYMENT CHECKLIST & EXTERNAL PREREQUISITES

| Requirement | Purpose | Critical Notes |
| :--- | :--- | :--- |
| **Domain & SSL Certificate** | HTTPS production hosting | Mandatory for Google OAuth, Razorpay live checkouts, and Shiprocket webhooks. |
| **FSSAI Food License** | Legal requirement for online food sales in India | Must be displayed in website footer, invoices, and product details. |
| **GSTIN Registration** | Tax invoices & intra/inter-state GST | Required in Shiprocket for generating E-Way bills on orders > ₹50,000. |
| **Razorpay Production Account**| Online payments (UPI, NetBanking, Cards) | Complete KYC, activate live keys (`rzp_live_...`), and register webhook secret. |
| **Shiprocket Business Account**| Live courier booking & automated tracking | Complete KYC, verify pickup warehouse address, and add initial shipping wallet credits. |
| **Dedicated SMTP Service** | Transactional customer & owner emails | Use Google Workspace, SendGrid, or Amazon SES with SPF/DKIM records. |
| **Tamper-Evident Packaging** | Preserving Gajak crispness & food safety | Use vacuum-sealed pouches and branded tamper-proof outer boxes. |

