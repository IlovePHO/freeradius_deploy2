Ext.define('Rd.view.softEtherVirtualHubs.gridSoftEtherUsers', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherUsers',
    multiSelect: true,
    //store: 'sSoftEtherUsers',
    stateful: true,
    stateId: 'StateGridSoftEtherUsers',
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
    urlMenu: '/cake3/rd_cake/soft-ether-users/menu-for-grid.json',
    plugins: 'gridfilters', //*We specify this
    initComponent: function () {
        var me = this;
        
        me.store   = Ext.create('Rd.store.sSoftEtherUsers');
        me.getStore().getProxy().setExtraParams({ 'virtual_hub_id' : me.vHub_id });
        
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
            //   {xtype: 'rownumberer',stateId: 'StateGridSoftEtherUsers0'},
            {
                text: i18n('sName'),
                dataIndex: 'user_name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherUsers1'
            },
            {
                text: i18n('sReal_Name'),
                dataIndex: 'real_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherUsers2'
            },
            {
                text: i18n('sPassword'),
                dataIndex: 'auth_password',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherUsers3'
            },
            {
                text: i18n('sNote'),
                dataIndex: 'note',
                tdCls: 'gridTree',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherUsers4'
            },

        ];
        me.callParent(arguments);
    }
});
