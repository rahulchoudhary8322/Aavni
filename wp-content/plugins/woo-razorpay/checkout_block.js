const settings = window.wc.wcSettings.getSetting('razorpay_data', {});
const el = window.wp.element.createElement;
const rawTitle = settings.title || 'UPI, Cards, NetBanking, Wallets (Razorpay)';
const titleText = window.wp.htmlEntities ? window.wp.htmlEntities.decodeEntities(rawTitle) : rawTitle;

const LabelComponent = () => {
    return el(
        'span',
        { className: 'aavni-rzp-checkout-label', style: { display: 'inline-flex', alignItems: 'center', flexWrap: 'wrap', gap: '8px', width: '100%' } },
        el('span', { className: 'aavni-rzp-label-title', style: { fontWeight: '600', color: '#1B1A18' } }, titleText),
        el(
            'span',
            { className: 'aavni-rzp-badges', style: { display: 'inline-flex', gap: '5px', marginLeft: 'auto' } },
            el('span', { className: 'aavni-rzp-tag aavni-tag-upi', style: { fontSize: '10.5px', fontWeight: '700', padding: '2px 7px', borderRadius: '4px', background: '#EFF6FF', color: '#1D4ED8', border: '1px solid #BFDBFE' } }, 'UPI / QR'),
            el('span', { className: 'aavni-rzp-tag aavni-tag-cards', style: { fontSize: '10.5px', fontWeight: '700', padding: '2px 7px', borderRadius: '4px', background: '#F5F3FF', color: '#6D28D9', border: '1px solid #DDD6FE' } }, 'Cards'),
            el('span', { className: 'aavni-rzp-tag aavni-tag-nb', style: { fontSize: '10.5px', fontWeight: '700', padding: '2px 7px', borderRadius: '4px', background: '#ECFDF5', color: '#047857', border: '1px solid #A7F3D0' } }, 'NetBanking')
        )
    );
};

const Content = () => {
    const rawDesc = settings.description || 'Pay securely via UPI (Google Pay, PhonePe, Paytm, QR), Credit/Debit Cards, NetBanking or Wallets.';
    const descText = window.wp.htmlEntities ? window.wp.htmlEntities.decodeEntities(rawDesc) : rawDesc;

    return el(
        'div',
        { className: 'aavni-rzp-block-content', style: { padding: '14px', background: '#FAF9F6', borderRadius: '8px', border: '1px solid #E8E2D8', marginTop: '8px' } },
        el('p', { className: 'aavni-rzp-desc', style: { margin: '0 0 10px 0', fontSize: '13px', color: '#1B1A18', lineHeight: '1.5' } }, descText),
        el(
            'div',
            { className: 'aavni-rzp-features-grid', style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '8px', margin: '10px 0', paddingTop: '10px', borderTop: '1px dashed #E8E2D8' } },
            el('div', { style: { fontSize: '12px', color: '#6B655C', display: 'flex', alignItems: 'center', gap: '6px' } },
                el('span', null, '⚡'),
                el('span', null, 'Google Pay, PhonePe, Paytm, UPI ID & QR')
            ),
            el('div', { style: { fontSize: '12px', color: '#6B655C', display: 'flex', alignItems: 'center', gap: '6px' } },
                el('span', null, '💳'),
                el('span', null, 'Visa, MasterCard, RuPay & Maestro Cards')
            ),
            el('div', { style: { fontSize: '12px', color: '#6B655C', display: 'flex', alignItems: 'center', gap: '6px' } },
                el('span', null, '🏦'),
                el('span', null, '50+ Major Indian Banks NetBanking')
            ),
            el('div', { style: { fontSize: '12px', color: '#6B655C', display: 'flex', alignItems: 'center', gap: '6px' } },
                el('span', null, '🔒'),
                el('span', null, '100% RBI Compliant & 256-Bit SSL Encrypted')
            )
        )
    );
};

const Block_Gateway = {
    name: 'razorpay',
    label: el(LabelComponent, null),
    content: el(Content, null),
    edit: el(Content, null),
    canMakePayment: () => true,
    ariaLabel: titleText,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );