let payment_method;

const settings = window.wc.wcSettings.getSetting( 'bunq_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) ;

const Select = ({ id, options }) => {
    return React.createElement('div', {},
        React.createElement('select', {id: id, name: id, onChange: (e) => {payment_method = e.target.value;}},
            options.map( (option) => React.createElement('option', {value: option.id}, option.description))
        )
    );
};

const Content = ( props ) => {
    if(! settings.direct_gateway) {
        return window.wp.htmlEntities.decodeEntities(settings.description);
    }

    const { eventRegistration, emitResponse } = props;
    const { onPaymentProcessing } = eventRegistration;
    React.useEffect( () => {
        const unsubscribe = onPaymentProcessing( async () => {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            wc_bunq_gateway_payment_method: payment_method,
                        },
                    },
                };
        } );
        // Unsubscribes when this component is unmounted.
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentProcessing,
    ] );


    const paymentMethods= [];
    for (const [id, description] of Object.entries(settings.payment_methods)) {
        paymentMethods.push({id: id, description: description, value: id });
    }

    if(! payment_method) {
        payment_method = paymentMethods[0].id;
    }
    
    return React.createElement('div', {className: 'payment-methods'}, null,
        React.createElement('p', null, window.wp.htmlEntities.decodeEntities(settings.description)),
        Select({id: 'wc_bunq_gateway_payment_method', options: paymentMethods})
    );
};

const Block_Gateway = {
    name: settings.id,
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );