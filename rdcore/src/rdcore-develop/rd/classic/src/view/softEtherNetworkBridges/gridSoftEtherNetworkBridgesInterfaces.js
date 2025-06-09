Ext.define('Rd.view.softEtherNetworkBridges.gridSoftEtherNetworkBridgesInterfaces', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherNetworkBridgesInterfaces',
    multiSelect: true,
    stateful: true,
    stateId: 'gridSoftEtherNetworkBridgesInterfaces',
    stateEvents: ['groupclick', 'columnhide'],
    border: false,
    network_bridge_id:0,
    //store: 'sSoftEtherNetworkBridgeInterfaces',
    requires: [
        'Rd.view.components.ajaxToolbar',
        'Ext.toolbar.Paging',
        'Ext.ux.ProgressBarPager'
    ],
    viewConfig: {
        loadMask: true
    },
    urlMenu: '/cake3/rd_cake/soft-ether-network-bridges/menu-for-grid-interfaces.json',
    plugins: 'gridfilters', //*We specify this
    initComponent: function () {
        var me = this;
        
        
        me.store   = Ext.create('Rd.store.sSoftEtherNetworkBridgeInterfaces');
        me.getStore().getProxy().setExtraParams({ 'network_bridge_id' : me.network_bridge_id });

        me.tbar = Ext.create('Rd.view.components.ajaxToolbar', {
            'url': me.urlMenu
        });

        me.bbar = [{
            xtype: 'pagingtoolbar',
            store: me.store,
            displayInfo: true,
            plugins: {
                'ux-progressbarpager': true
            }
        }];

        me.columns = [
            //   {xtype: 'rownumberer',stateId: 'StateGridSoftEtherLocalBridges0'},
            
            {
                text: i18n('sName'),
                dataIndex: 'if_name',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherLocalBridges1'
            },

        ];
        me.callParent(arguments);
    }
});
