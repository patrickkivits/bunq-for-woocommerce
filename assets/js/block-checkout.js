( function () {
    const { createElement, useState, useEffect } = window.wp.element;
    const { decodeEntities } = window.wp.htmlEntities;

    // The bunq gateway and the separate payment method gateways (CheckoutIntegration). WooCommerce only hands out the
    // data of those that are offered.
    const gatewayIds = ( window.wcBunqBlockCheckout && window.wcBunqBlockCheckout.gatewayIds ) || [ 'bunq' ];

    gatewayIds.forEach( ( gatewayId ) => {
        const settings = window.wc.wcSettings.getSetting( gatewayId + '_data', null );

        if ( settings ) {
            registerPaymentMethod( settings );
        }
    } );

    // Whether bunq accepts the cart total for a payment method; the total can change on the checkout page.
    function fitsLimits( limits, cartTotals ) {
        if ( ! limits || ! cartTotals ) {
            return true;
        }

        const total = parseInt( cartTotals.total_price, 10 ) / Math.pow( 10, cartTotals.currency_minor_unit || 0 );

        return total >= limits.min && ( limits.max === null || total <= limits.max );
    }

    function registerPaymentMethod( settings ) {
        const title = decodeEntities( settings.title || 'bunq' );
        const paymentMethods = Object.entries( settings.payment_methods || {} ).map( ( [ id, description ] ) => ( { id, description } ) );
        const icons = settings.icons || [];

        // The title with the payment method logos on the right, as WooCommerce's own payment methods show theirs.
        const label = createElement( 'span', {
            className: 'wc-bunq-payment-method-label',
            style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', width: '100%' },
        },
            createElement( 'span', null, title ),
            icons.length > 0 && createElement( 'span', {
                className: 'wc-bunq-payment-method-icons',
                style: { display: 'inline-flex', flexWrap: 'wrap', justifyContent: 'flex-end', gap: '4px' },
            }, icons.map( ( icon ) => createElement( 'img', {
                key: icon.id,
                src: icon.src,
                alt: decodeEntities( icon.alt || '' ),
                height: 24,
                style: { height: '24px', width: 'auto' },
            } ) ) )
        );

        const Description = () => ( settings.description ? createElement( 'p', null, decodeEntities( settings.description ) ) : null );

        // The markup of WooCommerce's own block checkout selects (such as Country/Region), so that WooCommerce's styles,
        // and a theme's styles for WooCommerce's fields, apply to this one as well.
        const PaymentMethodSelect = ( selectProps ) => createElement( 'div', { className: 'wc-blocks-components-select' },
            createElement( 'div', { className: 'wc-blocks-components-select__container' },
                createElement( 'label', { htmlFor: 'wc_bunq_gateway_payment_method', className: 'wc-blocks-components-select__label' },
                    decodeEntities( settings.select_label || 'Payment method' )
                ),
                createElement( 'select', Object.assign( {
                    id: 'wc_bunq_gateway_payment_method',
                    className: 'wc-blocks-components-select__select',
                    size: 1,
                }, selectProps ),
                    paymentMethods.map( ( option ) => createElement( 'option', { key: option.id, value: option.id }, option.description ) )
                ),
                createElement( 'svg', {
                    viewBox: '0 0 24 24',
                    xmlns: 'http://www.w3.org/2000/svg',
                    width: 24,
                    height: 24,
                    className: 'wc-blocks-components-select__expand',
                    'aria-hidden': true,
                    focusable: false,
                }, createElement( 'path', { d: 'M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z' } ) )
            )
        );

        // Checkout: lets the customer pick a payment method and hands the choice to process_payment().
        const Content = ( props ) => {
            // Hooks first, unconditionally (rules of hooks); the branches below only decide what to render.
            const selectable = settings.direct_gateway && paymentMethods.length > 0;
            const [ paymentMethod, setPaymentMethod ] = useState( selectable ? paymentMethods[ 0 ].id : '' );
            const { eventRegistration, emitResponse } = props;
            // onPaymentSetup replaced onPaymentProcessing in WooCommerce Blocks 9.x; keep the old name as a fallback.
            const onPaymentSetup = eventRegistration.onPaymentSetup || eventRegistration.onPaymentProcessing;

            useEffect( () => {
                if ( ! selectable ) {
                    return undefined;
                }

                const unsubscribe = onPaymentSetup( async () => ( {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            wc_bunq_gateway_payment_method: paymentMethod,
                        },
                    },
                } ) );

                return () => unsubscribe();
            }, [ selectable, onPaymentSetup, emitResponse.responseTypes.SUCCESS, paymentMethod ] );

            if ( ! selectable ) {
                return Description();
            }

            return createElement( 'div', { className: 'payment-methods' },
                Description(),
                PaymentMethodSelect( {
                    name: 'wc_bunq_gateway_payment_method',
                    value: paymentMethod,
                    onChange: ( event ) => setPaymentMethod( event.target.value ),
                } )
            );
        };

        // Block editor preview: no payment events are available there.
        const Edit = () => {
            if ( ! settings.direct_gateway || paymentMethods.length === 0 ) {
                return Description();
            }

            return createElement( 'div', { className: 'payment-methods' },
                Description(),
                PaymentMethodSelect( { disabled: true } )
            );
        };

        window.wc.wcBlocksRegistry.registerPaymentMethod( {
            name: settings.id || 'bunq',
            label: label,
            content: createElement( Content, null ),
            edit: createElement( Edit, null ),
            canMakePayment: ( { cartTotals } ) => fitsLimits( settings.limits, cartTotals ),
            ariaLabel: title,
            supports: {
                features: settings.supports || [ 'products' ],
            },
        } );
    }
} )();
