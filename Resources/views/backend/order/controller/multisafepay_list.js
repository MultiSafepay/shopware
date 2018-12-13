//{block name="backend/order/controller/list"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.Order.controller.MultiSafepayList', {
    override: 'Shopware.apps.Order.controller.List',

    /**
     * Override init to add additional event for button to ship an order
     */
    init: function() {
        var me = this;

        me.callParent(arguments);

        me.control({
            'order-list-main-window order-list': {
                shipOrder: me.onShipOrder,
                refundOrder: me.onRefundOrder,
            }
        });
    },

    /**
     * Event listener method which fired when the user clicks the ship button
     * in the order list to ship a single order at MultiSafepay.
     * @param record
     * @return void
     */
    onShipOrder: function(record) {
        var me = this,
            store = me.subApplication.getStore('Order'),
            message = ('Are you sure you want to ship order') + ' ' + record.get('number'),
            title = 'Ship order';

        Ext.MessageBox.confirm(title, message, function(response) {
            if (response !== 'yes') {
                return;
            }

            Ext.Ajax.request({
                url: '{url controller=MultiSafepayPayment action=shipOrder}',
                params: {
                    orderNumber: record.get('number'),
                    transactionId: record.get('transactionId'),
                },
                success: function(response) {
                    var result = Ext.decode(response.responseText);
                    Shopware.Notification.createGrowlMessage('Success', result.message);

                    /* refresh order page */
                    var orderStore = me.subApplication.getStore('Order');
                    orderStore.loadPage(orderStore.currentPage);
                }
            });
        });
    },

    /**
     * Event listener method which fired when the user clicks the ship button
     * in the order list to ship a single order at MultiSafepay.
     * @param record
     * @return void
     */
    onRefundOrder: function(record) {
        var me = this,
            store = me.subApplication.getStore('Order'),
            message = ('Are you sure you want to fully refund order') + ' ' + record.get('number'),
            title = 'Refund order';

        Ext.MessageBox.confirm(title, message, function(response) {
            if (response !== 'yes') {
                return;
            }

            Ext.Ajax.request({
                url: '{url controller=MultiSafepayPayment action=refundOrder}',
                params: {
                    orderNumber: record.get('number'),
                    transactionId: record.get('transactionId'),
                },
                success: function(response) {
                    var result = Ext.decode(response.responseText);

                    if(!result.success){
                        Shopware.Notification.createGrowlMessage('Error', result.message);

                    }else{
                        Shopware.Notification.createGrowlMessage('Success', result.message);
                    }

                    /* refresh order page */
                    var orderStore = me.subApplication.getStore('Order');
                    orderStore.loadPage(orderStore.currentPage);
                }
            });
        });
    }    
});
//{/block}
