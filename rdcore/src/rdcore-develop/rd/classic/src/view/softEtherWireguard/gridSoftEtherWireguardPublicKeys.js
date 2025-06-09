Ext.define('Rd.view.softEtherWireguard.gridSoftEtherWireguardPublicKeys', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherWireguardPublicKeys',
    multiSelect: true,
    store: 'sSoftEtherWireguardPublicKeys',
    stateful: true,
    stateId: 'StategridSoftEtherWireguardPublicKeys',
    stateEvents: ['groupclick', 'columnhide'],
    border: false,
    requires: [
        'Rd.view.components.ajaxToolbar',
        'Ext.toolbar.Paging',
        'Ext.ux.ProgressBarPager'
    ],
    viewConfig: {
        loadMask: true
    },
    vHub_id: null,
    urlMenu: '/cake3/rd_cake/soft-ether-wireguard/menu-for-grid.json',
    plugins: 'gridfilters', //*We specify this
    initComponent: function () {
        var me = this;


        me.bbar = [{
            xtype: 'pagingtoolbar',
            store: me.store,
            displayInfo: true,
            plugins: {
                'ux-progressbarpager': true
            }
        }];

        me.tbar = Ext.create('Rd.view.components.ajaxToolbar', {
            'url': me.urlMenu
        });
        me.columns = [
            //   {xtype: 'rownumberer',stateId: 'StategridSEWPublicKeys0'},
            {
                text: i18n('sPublic_key'),
                dataIndex: 'public_key',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSEWPublicKeys1'
            },
            {
                text: i18n('sVirtual_Hub'),
                dataIndex: 'hub_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSEWPublicKeys2'
            },
            {
                text: i18n('sVPN_users'),
                dataIndex: 'user_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSEWPublicKeys3'
            },

        ];
        me.callParent(arguments);
    }
});
