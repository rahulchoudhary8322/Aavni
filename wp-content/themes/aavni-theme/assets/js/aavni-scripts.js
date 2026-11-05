/**
 * AAVNI Premium Clothing - Frontend Scripts
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // --- Cart Drawer ---
  const cartToggleBtn = document.querySelectorAll('.aavni-cart-toggle');
  const cartDrawer = document.getElementById('aavniCartDrawer');
  const cartDrawerClose = document.getElementById('aavniCartDrawerClose');
  const modalOverlay = document.getElementById('aavniModalOverlay');

  function openCart() {
    if (cartDrawer) {
      cartDrawer.classList.add('open');
      if (modalOverlay) modalOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeCart() {
    if (cartDrawer) {
      cartDrawer.classList.remove('open');
      if (modalOverlay) modalOverlay.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  cartToggleBtn.forEach(btn => btn.addEventListener('click', function (e) {
    e.preventDefault();
    openCart();
  }));

  if (cartDrawerClose) {
    cartDrawerClose.addEventListener('click', closeCart);
  }

  // --- Size Guide Modal ---
  const sizeGuideToggles = document.querySelectorAll('.aavni-size-guide-toggle');
  const sizeGuideModal = document.getElementById('aavniSizeGuideModal');
  const sizeGuideClose = document.getElementById('aavniSizeGuideClose');

  sizeGuideToggles.forEach(btn => btn.addEventListener('click', function (e) {
    e.preventDefault();
    if (sizeGuideModal) sizeGuideModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }));

  if (sizeGuideClose) {
    sizeGuideClose.addEventListener('click', function () {
      if (sizeGuideModal) sizeGuideModal.classList.remove('open');
      document.body.style.overflow = '';
    });
  }

  // --- Search Overlay Modal & Instant Live Search ---
  const searchToggle = document.querySelectorAll('.aavni-search-toggle');
  const searchModal = document.getElementById('aavniSearchModal');
  const searchClose = document.getElementById('aavniSearchClose');
  const searchInput = document.getElementById('aavniSearchInput');
  const searchSpinner = document.getElementById('aavniSearchSpinner');
  const searchLiveResults = document.getElementById('aavniLiveSearchResults');
  const searchTags = document.querySelectorAll('.aavni-search-tag');
  let searchDebounceTimer = null;

  function closeSearchModal() {
    if (searchModal) searchModal.classList.remove('open');
    document.body.style.overflow = '';
    if (searchLiveResults) {
      searchLiveResults.style.display = 'none';
      searchLiveResults.innerHTML = '';
    }
  }

  searchToggle.forEach(btn => btn.addEventListener('click', function (e) {
    e.preventDefault();
    if (searchModal) {
      searchModal.classList.add('open');
      if (searchInput) {
        setTimeout(() => searchInput.focus(), 100);
      }
      document.body.style.overflow = 'hidden';
    }
  }));

  if (searchClose) {
    searchClose.addEventListener('click', closeSearchModal);
  }

  // Popular search chips click
  searchTags.forEach(tag => {
    tag.addEventListener('click', function() {
      const q = this.getAttribute('data-query');
      if (searchInput && q) {
        searchInput.value = q;
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
  });

  // Debounced Live Instant Search
  if (searchInput && searchLiveResults) {
    searchInput.addEventListener('input', function() {
      clearTimeout(searchDebounceTimer);
      const query = this.value.trim();

      if (query.length < 2) {
        if (searchSpinner) searchSpinner.style.display = 'none';
        searchLiveResults.style.display = 'none';
        searchLiveResults.innerHTML = '';
        return;
      }

      if (searchSpinner) searchSpinner.style.display = 'block';

      searchDebounceTimer = setTimeout(function() {
        const ajaxUrl = (typeof aavni_params !== 'undefined' && aavni_params.ajax_url) 
          ? aavni_params.ajax_url 
          : '/wordpress/Aavni/wp-admin/admin-ajax.php';

        jQuery.ajax({
          url: ajaxUrl,
          type: 'GET',
          data: {
            action: 'aavni_live_search',
            term: query
          },
          dataType: 'json',
          success: function(res) {
            if (searchSpinner) searchSpinner.style.display = 'none';
            if (!res || !res.success) return;

            const data = res.data;
            let html = '';

            // Matching Categories
            if (data.categories && data.categories.length > 0) {
              html += '<div class="aavni-live-cat-box">';
              data.categories.forEach(cat => {
                html += '<a href="' + cat.url + '" class="aavni-live-cat-link">';
                html += '<span>🏷️ Category:</span> <strong>' + cat.name + '</strong> <span style="color:var(--aavni-text-muted);font-size:11px;">(' + cat.count + ')</span>';
                html += '</a> ';
              });
              html += '</div>';
            }

            // Matching Products
            if (data.products && data.products.length > 0) {
              html += '<ul class="aavni-live-product-list">';
              data.products.forEach(p => {
                html += '<li>';
                html += '<a href="' + p.permalink + '" class="aavni-live-product-item">';
                html += '<img src="' + p.image + '" alt="' + p.title + '" class="aavni-live-product-img">';
                html += '<div class="aavni-live-product-info">';
                if (p.category) {
                  html += '<span class="aavni-live-product-cat">' + p.category + '</span>';
                }
                html += '<h4 class="aavni-live-product-title">' + p.title + '</h4>';
                html += '<div class="aavni-live-product-price">' + p.price_html + '</div>';
                html += '</div>';
                html += '</a>';
                html += '</li>';
              });
              html += '</ul>';

              // View all button
              html += '<a href="' + data.all_url + '" class="aavni-live-view-all">';
              html += 'View all ' + data.count + ' matching results for &ldquo;' + query + '&rdquo; &rarr;';
              html += '</a>';
            } else if (!data.categories || data.categories.length === 0) {
              html += '<div class="aavni-live-empty">';
              html += '<p style="margin:0 0 6px 0;">No products found matching &ldquo;<strong>' + query + '</strong>&rdquo;.</p>';
              html += '<span style="font-size:12px;color:var(--aavni-text-subtle);">Try checking for typos or browse our Long Kurti &amp; Tops collections.</span>';
              html += '</div>';
            }

            searchLiveResults.innerHTML = html;
            searchLiveResults.style.display = 'block';
          },
          error: function() {
            if (searchSpinner) searchSpinner.style.display = 'none';
          }
        });
      }, 220);
    });
  }

  // Global Overlay Click
  if (modalOverlay) {
    modalOverlay.addEventListener('click', function () {
      closeCart();
      if (sizeGuideModal) sizeGuideModal.classList.remove('open');
      closeSearchModal();
      document.body.style.overflow = '';
    });
  }

  // --- Wishlist Management via LocalStorage ---
  const wishlistBadges = document.querySelectorAll('.aavni-wishlist-count');
  let wishlist = JSON.parse(localStorage.getItem('aavni_wishlist') || '[]');

  function updateWishlistUI() {
    wishlistBadges.forEach(b => {
      b.textContent = wishlist.length;
      b.style.display = wishlist.length > 0 ? 'flex' : 'none';
    });

    document.querySelectorAll('.aavni-wishlist-toggle').forEach(btn => {
      const pid = btn.getAttribute('data-product-id');
      if (wishlist.includes(pid)) {
        btn.classList.add('active');
        btn.setAttribute('aria-label', 'Remove from wishlist');
      } else {
        btn.classList.remove('active');
        btn.setAttribute('aria-label', 'Add to wishlist');
      }
    });
  }

  updateWishlistUI();

  document.addEventListener('click', function (e) {
    const toggleBtn = e.target.closest('.aavni-wishlist-toggle');
    if (toggleBtn) {
      e.preventDefault();
      const pid = toggleBtn.getAttribute('data-product-id');
      if (!pid) return;

      if (wishlist.includes(pid)) {
        wishlist = wishlist.filter(id => id !== pid);
      } else {
        wishlist.push(pid);
        // Subtle micro-animation
        toggleBtn.style.transform = 'scale(1.25)';
        setTimeout(() => toggleBtn.style.transform = '', 200);
      }
      localStorage.setItem('aavni_wishlist', JSON.stringify(wishlist));
      updateWishlistUI();
    }
  });

  // --- Mobile Menu Toggle & Drawer ---
  const mobileToggle = document.getElementById('aavniMobileToggle');
  const mobileMenu = document.getElementById('aavniMobileMenu');
  const mobileMenuClose = document.getElementById('aavniMobileMenuClose');

  function openMobileMenu() {
    if (mobileMenu) {
      mobileMenu.classList.add('open');
      if (modalOverlay) modalOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeMobileMenu() {
    if (mobileMenu) {
      mobileMenu.classList.remove('open');
      if (modalOverlay) modalOverlay.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  if (mobileToggle) {
    mobileToggle.addEventListener('click', function (e) {
      e.preventDefault();
      openMobileMenu();
    });
  }

  if (mobileMenuClose) {
    mobileMenuClose.addEventListener('click', function (e) {
      e.preventDefault();
      closeMobileMenu();
    });
  }

  // Close mobile drawer when clicking any link inside it
  if (mobileMenu) {
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function () {
        closeMobileMenu();
      });
    });
  }

  // Universal Modal Overlay Click Handler: Closes any active drawer or modal
  if (modalOverlay) {
    modalOverlay.addEventListener('click', function () {
      closeCart();
      closeMobileMenu();
      if (typeof closeSearchModal === 'function') closeSearchModal();
      if (sizeGuideModal) sizeGuideModal.classList.remove('open');
      modalOverlay.classList.remove('open');
      document.body.style.overflow = '';
    });
  }

  // Mobile Bottom Nav Wishlist Button Handler
  document.querySelectorAll('.aavni-wishlist-nav-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const newArrivals = document.querySelector('.aavni-product-grid');
      if (newArrivals) {
        newArrivals.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else {
        window.location.href = '<?php echo esc_url(wc_get_page_permalink("shop")); ?>';
      }
    });
  });

  // --- Newsletter Form Submission ---
  const newsletterForm = document.getElementById('aavniNewsletterForm');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const emailInput = newsletterForm.querySelector('input[type="email"]');
      const responseMsg = document.getElementById('aavniNewsletterResponse');
      if (emailInput && emailInput.value) {
        emailInput.disabled = true;
        if (responseMsg) {
          responseMsg.innerHTML = '<span style="color: #C5A880; font-weight: 500;">✓ Thank you for subscribing to AAVNI. Enjoy 10% off your first order!</span>';
          responseMsg.style.display = 'block';
        }
      }
    });
  }

  // --- WooCommerce AJAX Cart Support ---
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('added_to_cart', function (event, fragments, cart_hash) {
      openCart();
    });

    // --- Amazon-style Variation Swatches & Size Pills Enhancer ---
    function setupVariationSwatches() {
      const $form = jQuery('form.variations_form');
      if (!$form.length) return;

      // Color Swatches
      const $colorSelect = $form.find('select#pa_color');
      if ($colorSelect.length && !$colorSelect.hasClass('swatches-enhanced')) {
        $colorSelect.addClass('swatches-enhanced').hide();
        const colorPalette = {
          'printed-brown': { bg: '#8C5835', name: 'Printed Brown' },
          'indigo-blue': { bg: '#2A4B7C', name: 'Indigo Blue' },
          'olive-green': { bg: '#5B6B48', name: 'Olive Green' },
          'dusty-pink': { bg: '#B36B7E', name: 'Dusty Pink' }
        };

        const $swatchWrap = jQuery('<div class="aavni-visual-swatches" style="display:flex; gap:12px; margin:8px 0 16px 0;"></div>');
        $colorSelect.find('option').each(function () {
          const val = jQuery(this).val();
          if (!val) return;
          const text = jQuery(this).text();
          const info = colorPalette[val] || { bg: '#333', name: text };
          const $btn = jQuery('<button type="button" class="aavni-swatch-btn" data-val="' + val + '" style="display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border:1px solid #DDD; border-radius:24px; background:#FFF; cursor:pointer; font-size:13px; font-family:inherit; transition:all 0.2s;"><span style="width:16px; height:16px; border-radius:50%; background:' + info.bg + '; border:1px solid #FFF; box-shadow:0 0 0 1px rgba(0,0,0,0.2);"></span> ' + text + '</button>');
          
          $btn.on('click', function (e) {
            e.preventDefault();
            $swatchWrap.find('.aavni-swatch-btn').css({ borderColor: '#DDD', background: '#FFF', fontWeight: 'normal' });
            $btn.css({ borderColor: '#A85A48', background: '#FAF8F5', fontWeight: '700' });
            $colorSelect.val(val).trigger('change');
          });

          $swatchWrap.append($btn);
        });
        $colorSelect.after($swatchWrap);
        // Select first if available
        $swatchWrap.find('.aavni-swatch-btn').first().trigger('click');
      }

      // Size Selector Pills + Size Guide
      const $sizeSelect = $form.find('select#pa_size');
      if ($sizeSelect.length && !$sizeSelect.hasClass('swatches-enhanced')) {
        $sizeSelect.addClass('swatches-enhanced').hide();
        const $sizeWrap = jQuery('<div class="aavni-visual-sizes" style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin:8px 0 20px 0;"></div>');
        
        $sizeSelect.find('option').each(function () {
          const val = jQuery(this).val();
          if (!val) return;
          const text = jQuery(this).text();
          const $btn = jQuery('<button type="button" class="aavni-size-btn" data-val="' + val + '" style="min-width:54px; height:40px; padding:0 14px; border:1px solid #CCC; border-radius:4px; background:#FFF; cursor:pointer; font-size:14px; font-weight:600; font-family:inherit; transition:all 0.2s;">' + text + '</button>');

          $btn.on('click', function (e) {
            e.preventDefault();
            $sizeWrap.find('.aavni-size-btn').css({ borderColor: '#CCC', background: '#FFF', color: '#1B1A18' });
            $btn.css({ borderColor: '#1B1A18', background: '#1B1A18', color: '#FFF' });
            $sizeSelect.val(val).trigger('change');
          });

          $sizeWrap.append($btn);
        });

        // Add inline Size Chart link
        const $chartLink = jQuery('<a href="#" class="aavni-size-guide-toggle" style="margin-left:14px; font-size:13px; color:#A85A48; text-decoration:underline; font-weight:600;">📏 View Size Chart</a>');
        $chartLink.on('click', function(e) {
          e.preventDefault();
          if (sizeGuideModal) sizeGuideModal.classList.add('open');
        });
        $sizeWrap.append($chartLink);

        $sizeSelect.after($sizeWrap);
        // Select first size by default
        $sizeWrap.find('.aavni-size-btn').first().trigger('click');
      }

      // Append BUY NOW Button
      const $singleAdd = $form.find('.single_add_to_cart_button');
      if ($singleAdd.length && !jQuery('#aavniBuyNowBtn').length) {
        const $buyNow = jQuery('<button type="button" id="aavniBuyNowBtn" style="background:#C5A880; color:#FFF; font-family:inherit; font-size:13.5px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; padding:12px 24px; border:none; border-radius:4px; cursor:pointer; transition:all 0.2s; margin-left:10px;">⚡ BUY NOW</button>');
        $buyNow.hover(
          function() { jQuery(this).css('background', '#AF8E60'); },
          function() { jQuery(this).css('background', '#C5A880'); }
        );
        $buyNow.on('click', function(e) {
          e.preventDefault();
          // Add input to signal instant checkout
          jQuery('<input>').attr({
            type: 'hidden',
            name: 'aavni_instant_checkout',
            value: '1'
          }).appendTo($form);
          $singleAdd.trigger('click');
          setTimeout(function() {
            window.location.href = (typeof aavni_params !== 'undefined') ? aavni_params.checkout_url : '/wordpress/Aavni/checkout/';
          }, 400);
        });
        $singleAdd.after($buyNow);
      }
    }

    setupVariationSwatches();
  }

  // --- Interactive Pincode Delivery Checker ---
  const pincodeBtn = document.getElementById('aavniPincodeBtn');
  const pincodeInput = document.getElementById('aavniPincodeInput');
  const pincodeResult = document.getElementById('aavniPincodeResult');

  if (pincodeBtn && pincodeInput && pincodeResult) {
    function checkDelivery() {
      const pin = pincodeInput.value.trim();
      if (!pin || pin.length < 6 || !/^\d{6}$/.test(pin)) {
        pincodeResult.innerHTML = '<span style="color:#D9534F;">⚠️ Please enter a valid 6-digit Indian PIN code (e.g. 302002).</span>';
        pincodeResult.style.display = 'block';
        return;
      }

      // Calculate delivery date (today + 3 business days)
      const now = new Date();
      now.setDate(now.getDate() + 3);
      const options = { weekday: 'long', month: 'short', day: 'numeric' };
      const deliveryDateStr = now.toLocaleDateString('en-IN', options);

      pincodeResult.innerHTML = `
        <div style="background:#EBF7ED; border:1px solid #C3E6CB; color:#155724; padding:10px 14px; border-radius:4px; margin-top:8px;">
          <strong style="display:block; font-size:13.5px; margin-bottom:4px;">✓ Delivery available for ${pin}!</strong>
          <span style="font-size:12.5px; color:#1E7E34; display:block;">• Estimated Doorstep Delivery by <strong>${deliveryDateStr}</strong>.</span>
          <span style="font-size:12.5px; color:#1E7E34; display:block;">• <strong>Cash on Delivery (COD)</strong> available.</span>
          <span style="font-size:12.5px; color:#1E7E34; display:block;">• Free Express Delivery on orders above ₹999.</span>
        </div>
      `;
      pincodeResult.style.display = 'block';
    }

    pincodeBtn.addEventListener('click', checkDelivery);
    pincodeInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        checkDelivery();
      }
    });
  }

  // --- Google Authentication Frontend Handlers ---
  function showGoogleAuthNotice(targetBtn, message, isError = true) {
    if (!targetBtn) return;
    const existing = targetBtn.parentElement.querySelector('.aavni-google-auth-notice');
    if (existing) existing.remove();

    const notice = document.createElement('div');
    notice.className = 'aavni-google-auth-notice';
    notice.style.cssText = 'margin-top:10px; padding:10px 14px; border-radius:6px; font-size:13px; line-height:1.4; text-align:center; ' +
      (isError ? 'background:#FEF2F2; color:#991B1B; border:1px solid #FCA5A5;' : 'background:#F0FDF4; color:#166534; border:1px solid #86EFAC;');
    notice.innerHTML = (isError ? '⚠️ ' : '✓ ') + message;
    targetBtn.parentElement.appendChild(notice);

    setTimeout(() => {
      notice.style.transition = 'opacity 0.4s ease';
      notice.style.opacity = '0';
      setTimeout(() => notice.remove(), 400);
    }, 6000);
  }

  function handleGoogleTrigger(e) {
    if (e) e.preventDefault();
    const btn = e.target.closest('.aavni-btn-google-auth');

    // If Google Client ID is configured and GIS is available, trigger official Google Sign-In
    if (typeof aavni_google_auth !== 'undefined' && aavni_google_auth.client_id && typeof google !== 'undefined' && google.accounts) {
      try {
        google.accounts.id.initialize({
          client_id: aavni_google_auth.client_id,
          callback: handleGoogleGISCredential
        });
        google.accounts.id.prompt((notification) => {
          if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
            console.log('Google GIS prompt skipped or suppressed:', notification.getNotDisplayedReason());
          }
        });
        return;
      } catch(err) {
        console.warn('Google GIS prompt error:', err);
      }
    }

    // When client ID is not configured, inform the user clearly to use standard email & password
    showGoogleAuthNotice(btn, 'Google Sign-In is not configured yet. Please log in using your account email and password below.');
  }

  // Universal Event Delegation for Google auth button (popup/GIS style only)
  // NOTE: The login page uses a redirect-based <a> tag (.aavni-google-oauth-btn) — do NOT intercept that.
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.aavni-btn-google-auth');
    if (btn && !btn.classList.contains('aavni-google-oauth-btn')) {
      handleGoogleTrigger(e);
    }
  });

  // Handle GIS Credential from real Google OAuth
  function handleGoogleGISCredential(response) {
    if (response && response.credential) {
      submitGoogleAuthPayload({ credential: response.credential });
    }
  }

  // Submit Verified Authentication Payload to WordPress Backend
  function submitGoogleAuthPayload(userData) {
    if (!userData || !userData.credential) {
      return;
    }

    const activeBtn = document.querySelector('.aavni-btn-google-auth');
    if (activeBtn) {
      activeBtn.disabled = true;
      activeBtn.style.opacity = '0.7';
    }

    const params = Object.assign({
      action: 'aavni_google_auth',
      nonce: (typeof aavni_google_auth !== 'undefined') ? aavni_google_auth.nonce : '',
      redirect_to: window.location.href.indexOf('checkout') !== -1 ? ((typeof aavni_google_auth !== 'undefined') ? aavni_google_auth.checkout_url : '') : ((typeof aavni_google_auth !== 'undefined') ? aavni_google_auth.my_account_url : '')
    }, userData);

    jQuery.ajax({
      url: (typeof aavni_google_auth !== 'undefined') ? aavni_google_auth.ajax_url : '/wordpress/Aavni/wp-admin/admin-ajax.php',
      type: 'POST',
      data: params,
      dataType: 'json',
      success: function(res) {
        if (activeBtn) {
          activeBtn.disabled = false;
          activeBtn.style.opacity = '1';
        }
        if (res && res.success) {
          showGoogleAuthNotice(activeBtn, res.data.message || 'Signed in successfully! Redirecting...', false);
          setTimeout(function() {
            window.location.href = res.data.redirect_url || ((typeof aavni_google_auth !== 'undefined') ? aavni_google_auth.my_account_url : '/wordpress/Aavni/my-account/');
          }, 800);
        } else {
          showGoogleAuthNotice(activeBtn, (res.data && res.data.message) ? res.data.message : 'Google authentication failed.');
        }
      },
      error: function(xhr, status, error) {
        console.error('Google Auth Error:', error);
        if (activeBtn) {
          activeBtn.disabled = false;
          activeBtn.style.opacity = '1';
        }
        showGoogleAuthNotice(activeBtn, 'Connection failed. Please sign in with your email and password.');
      }
    });
  }

  // --- Full-Width Edge-to-Edge Hero Carousel Slider ---
  const heroSlider = document.getElementById('aavniHeroSlider');
  const heroWrapper = document.getElementById('aavniHeroWrapper');
  const heroSlides = document.querySelectorAll('.aavni-hero-slide');
  const heroPrev = document.getElementById('aavniHeroPrev');
  const heroNext = document.getElementById('aavniHeroNext');
  const heroDots = document.querySelectorAll('.aavni-slider-dot');

  if (heroSlider && heroWrapper && heroSlides.length > 1) {
    let currentSlide = 0;
    const totalSlides = heroSlides.length;
    let autoPlayTimer = null;

    function goToSlide(index) {
      if (index < 0) {
        index = totalSlides - 1;
      } else if (index >= totalSlides) {
        index = 0;
      }
      currentSlide = index;
      heroWrapper.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';

      heroSlides.forEach((slide, idx) => {
        slide.classList.toggle('active', idx === currentSlide);
      });

      heroDots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx === currentSlide);
      });
    }

    function nextSlide() {
      goToSlide(currentSlide + 1);
    }

    function prevSlide() {
      goToSlide(currentSlide - 1);
    }

    const isAutoplayEnabled = heroSlider.getAttribute('data-autoplay') !== 'false';
    const intervalMs = parseInt(heroSlider.getAttribute('data-autoplay-interval'), 10) || 5000;

    function startAutoPlay() {
      stopAutoPlay();
      if (isAutoplayEnabled) {
        autoPlayTimer = setInterval(nextSlide, intervalMs);
      }
    }

    function stopAutoPlay() {
      if (autoPlayTimer) {
        clearInterval(autoPlayTimer);
        autoPlayTimer = null;
      }
    }

    if (heroNext) {
      heroNext.addEventListener('click', function(e) {
        e.preventDefault();
        nextSlide();
        startAutoPlay();
      });
    }

    if (heroPrev) {
      heroPrev.addEventListener('click', function(e) {
        e.preventDefault();
        prevSlide();
        startAutoPlay();
      });
    }

    heroDots.forEach(dot => {
      dot.addEventListener('click', function(e) {
        e.preventDefault();
        const targetIdx = parseInt(this.getAttribute('data-index'), 10);
        goToSlide(targetIdx);
        startAutoPlay();
      });
    });

    // Pause on hover
    heroSlider.addEventListener('mouseenter', stopAutoPlay);
    heroSlider.addEventListener('mouseleave', startAutoPlay);

    // Touch swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    heroSlider.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    heroSlider.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    }, { passive: true });

    function handleSwipe() {
      const swipeDistance = touchEndX - touchStartX;
      if (Math.abs(swipeDistance) > 40) {
        if (swipeDistance < 0) {
          nextSlide();
        } else {
          prevSlide();
        }
        startAutoPlay();
      }
    }

    // Start carousel autoplay
    startAutoPlay();
  }
});


