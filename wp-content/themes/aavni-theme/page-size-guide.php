<?php
/**
 * Template Name: Size Guide
 *
 * @package AAVNI
 */

get_header();
?>

<main id="primary" class="site-main" style="padding: 48px 0 80px 0;">
    <div class="aavni-container">
        
        <nav style="font-size: 13px; color: var(--aavni-text-subtle); margin-bottom: 32px;" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; <span>Size Guide</span>
        </nav>

        <div class="aavni-section-header" style="max-width: 680px; margin: 0 auto 56px auto;">
            <span class="aavni-subheading">Fit Perfection</span>
            <h1 class="aavni-section-title">Women's Size &amp; Fit Guide</h1>
            <p class="aavni-section-desc">
                Find your perfect size across AAVNI's Long Kurtis, Short Kurtis, Tops, and Loungewear.
            </p>
        </div>

        <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 40px; margin-bottom: 48px;">
            <h3 class="aavni-heading" style="font-size: 22px; margin-bottom: 16px;">Standard Indian Kurti &amp; Top Measurements</h3>
            <p style="font-size: 14px; color: var(--aavni-text-muted); margin-bottom: 24px;">Measurements below represent garment sizing in inches. Our 100% pure cotton garments include an optimal comfort margin of 2-3 inches beyond body measurement for ease of movement.</p>
            
            <table class="aavni-size-table">
                <thead>
                    <tr>
                        <th>Size Tag</th>
                        <th>Standard India / UK</th>
                        <th>Garment Bust</th>
                        <th>Garment Waist</th>
                        <th>Garment Hip</th>
                        <th>Long Kurti Length</th>
                        <th>Short Kurti Length</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>XS</strong></td>
                        <td>34 / 8</td>
                        <td>34" (86 cm)</td>
                        <td>30" (76 cm)</td>
                        <td>38" (96 cm)</td>
                        <td>44" (112 cm)</td>
                        <td>30" (76 cm)</td>
                    </tr>
                    <tr>
                        <td><strong>S</strong></td>
                        <td>36 / 10</td>
                        <td>36" (91 cm)</td>
                        <td>32" (81 cm)</td>
                        <td>40" (101 cm)</td>
                        <td>44" (112 cm)</td>
                        <td>30" (76 cm)</td>
                    </tr>
                    <tr>
                        <td><strong>M</strong></td>
                        <td>38 / 12</td>
                        <td>38" (96 cm)</td>
                        <td>34" (86 cm)</td>
                        <td>42" (106 cm)</td>
                        <td>45" (114 cm)</td>
                        <td>31" (79 cm)</td>
                    </tr>
                    <tr>
                        <td><strong>L</strong></td>
                        <td>40 / 14</td>
                        <td>40" (101 cm)</td>
                        <td>36" (91 cm)</td>
                        <td>44" (112 cm)</td>
                        <td>45" (114 cm)</td>
                        <td>31" (79 cm)</td>
                    </tr>
                    <tr>
                        <td><strong>XL</strong></td>
                        <td>42 / 16</td>
                        <td>42" (106 cm)</td>
                        <td>38" (96 cm)</td>
                        <td>46" (117 cm)</td>
                        <td>46" (117 cm)</td>
                        <td>32" (81 cm)</td>
                    </tr>
                    <tr>
                        <td><strong>XXL</strong></td>
                        <td>44 / 18</td>
                        <td>44" (112 cm)</td>
                        <td>40" (101 cm)</td>
                        <td>48" (122 cm)</td>
                        <td>46" (117 cm)</td>
                        <td>32" (81 cm)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 32px;">
                <h4 style="font-size: 18px; margin-bottom: 12px;">How To Measure</h4>
                <ul style="font-size: 14px; color: var(--aavni-text-muted); line-height: 1.8; padding-left: 20px; margin: 0;">
                    <li><strong>Bust:</strong> Measure around the fullest part of your chest with arms relaxed at your sides.</li>
                    <li><strong>Waist:</strong> Measure around your natural waistline, slightly above your belly button.</li>
                    <li><strong>Hips:</strong> Measure around the fullest part of your hips and buttocks while standing with feet together.</li>
                </ul>
            </div>
            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 32px;">
                <h4 style="font-size: 18px; margin-bottom: 12px;">Need Personal Fitting Advice?</h4>
                <p style="font-size: 14px; color: var(--aavni-text-muted); line-height: 1.6; margin-bottom: 16px;">
                    Unsure which size suits you best? Share your bust and height measurements with our stylist for bespoke sizing guidance.
                </p>
                <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="aavni-btn aavni-btn-primary" style="padding: 10px 20px; font-size: 12px;">Ask Our Stylist</a>
            </div>
        </div>

    </div>
</main>

<?php
get_footer();
