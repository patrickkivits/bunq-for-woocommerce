( function () {
    const { createElement, useState, useEffect } = window.wp.element;
    const { decodeEntities } = window.wp.htmlEntities;

    const settings = window.wc.wcSettings.getSetting( 'bunq_data', {} );
    const label = decodeEntities( settings.title || 'bunq' );
    const paymentMethods = Object.entries( settings.payment_methods || {} ).map( ( [ id, description ] ) => ( { id, description } ) );

    const Description = () => createElement( 'p', null, decodeEntities( settings.description || '' ) );

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
            createElement( 'select', {
                id: 'wc_bunq_gateway_payment_method',
                name: 'wc_bunq_gateway_payment_method',
                value: paymentMethod,
                onChange: ( event ) => setPaymentMethod( event.target.value ),
            },
                paymentMethods.map( ( option ) => createElement( 'option', { key: option.id, value: option.id }, option.description ) )
            )
        );
    };

    // Block editor preview: no payment events are available there.
    const Edit = () => {
        if ( ! settings.direct_gateway || paymentMethods.length === 0 ) {
            return Description();
        }

        return createElement( 'div', { className: 'payment-methods' },
            Description(),
            createElement( 'select', { id: 'wc_bunq_gateway_payment_method', disabled: true },
                paymentMethods.map( ( option ) => createElement( 'option', { key: option.id, value: option.id }, option.description ) )
            )
        );
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod( {
        name: settings.id || 'bunq',
        label: label,
        content: createElement( Content, null ),
        edit: createElement( Edit, null ),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports || [ 'products' ],
        },
    } );
} )();
