Ext.define('Rd.view.proxies.gridProxies', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridProxies',
    multiSelect: true,
    store: 'sProxies',
    stateful: true,
    stateId: 'StateGridProxies',
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
    urlMenu: '/cake3/rd_cake/proxies/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StateGridProxies0'},
            {
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridProxies2'
            },
            {
                text: i18n('sAvailable_to_sub_providers'),
                flex: 1,
                xtype: 'templatecolumn',
                tdCls: 'gridTree',
                tpl: new Ext.XTemplate(
                    "<tpl if='available_to_siblings == true'><div class=\"fieldGreen\"><i class=\"fa fa-check-circle\"></i> " + i18n("sYes") + "</div></tpl>",
                    "<tpl if='available_to_siblings == false'><div class=\"fieldRed\"><i class=\"fa fa-times-circle\"></i> " + i18n("sNo") + "</div></tpl>"
                ),
                dataIndex: 'available_to_siblings',
                filter: {
                    type: 'boolean',
                    defaultValue: false,
                    yesText: 'Yes',
                    noText: 'No'
                },
                stateId: 'StateGridProxies3'
            },
            {
                text: i18n('sHome_server_pool'),
                dataIndex: 'home_server_pool_name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridProxies4'
            },
            {
                text: i18n('sDescription'),
                dataIndex: 'description',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridProxies5'
            },

        ];
        me.callParent(arguments);
    }
});
