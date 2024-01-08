//{block name="backend/order/view/list/list"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.Order.view.list.MultiSafepayList', {
    override: 'Shopware.apps.Order.view.list.List',

    createActionColumn: function () {
        var me = this,
            result = me.callParent(arguments),
            items = result.items;

        items.push({
            iconCls: 'sprite-money--minus',
            action: 'refundOrder',
            tooltip: 'Fully refund order at MultiSafepay',
            handler:function (view, rowIndex, colIndex, item) {
                var store = view.getStore(),
                        record = store.getAt(rowIndex);

                me.fireEvent('refundOrder', record);
            },
            getClass: function (value, metadata, record) {
                if (record.raw.payment
                    && (record.raw.payment.name.substring(0, 13) !== "multisafepay_"
                        || record.raw.paymentStatus.id !== 12
                        || record.raw.payment.name === "multisafepay_AFTERPAY"
                        || record.raw.payment.name === "multisafepay_EINVOICE"
                        || record.raw.payment.name === "multisafepay_IN3"
                        || record.raw.payment.name === "multisafepay_KLARNA"
                        || record.raw.payment.name === "multisafepay_PAYAFTER")) {
                    return 'x-hidden';
                }
                return '';
            }
        });

        var column = Ext.create(
            'Ext.grid.column.Action',
            {
                width: (30 * items.length),
                items: items
            }
        );
        return column;
    },
});
//{/block}
