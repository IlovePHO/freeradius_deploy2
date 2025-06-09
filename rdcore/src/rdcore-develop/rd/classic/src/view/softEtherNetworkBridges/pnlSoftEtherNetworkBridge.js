Ext.define('Rd.view.softEtherNetworkBridges.pnlSoftEtherNetworkBridge', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.pnlSoftEtherNetworkBridge',
    border: false,
    plain: true,
    cls: 'subTab',
    record: null, //We will supply each instance with a reference to the selected record.
    requires: [
        'Rd.view.softEtherNetworkBridges.gridSoftEtherNetworkBridgesInterfaces',
        'Rd.view.softEtherNetworkBridges.pnlSoftEtherNetworkBridgeStatus',
        'Rd.view.softEtherNetworkBridges.pnlSoftEtherNetworkBridgeAddress',
    ],
    initComponent: function () {
        var me = this;
        var network_bridge_id = me.record.get('id');
        me.items = [
            {
                title: i18n('sInterface'),
                itemId: 'tabSoftEtherNetworkBridgesInterfaces',
                layout: 'fit',
                xtype: 'gridSoftEtherNetworkBridgesInterfaces',
                network_bridge_id: network_bridge_id,
            },
            {
                title: i18n('sStatus'),
                itemId: 'tabSoftEtherNetworkBridgeStatus',
                layout: 'fit',
                xtype: 'pnlSoftEtherNetworkBridgeStatus',
                record : me.record,
            },
            {
                title: i18n('sIP_Address'),
                itemId: 'tabSoftEtherNetworkBridgeAddress',
                layout: 'fit',
                xtype: 'pnlSoftEtherNetworkBridgeAddress',
                record : me.record,
            },

        ];
        this.callParent(arguments);
    },


});
