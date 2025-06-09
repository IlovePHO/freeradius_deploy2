Ext.define('Rd.view.softEtherNetworkBridges.gridSoftEtherNetworkBridges', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherNetworkBridges',
    multiSelect: true,
    store: 'sSoftEtherNetworkBridges',
    stateful: true,
    stateId: 'StateGridSoftEtherNetworkBridges',
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
    urlMenu: '/cake3/rd_cake/soft-ether-network-bridges/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StateGridSENetworkBridges0'},
            {
                text: i18n('sName'),
                dataIndex: 'bridge_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSENetworkBridges1'
            },
            {
                text: i18n('sInterface'),
                dataIndex: 'if_name_list',
                tdCls: 'gridTree',
                flex: 1,
                xtype: 'templatecolumn',
                tpl: new Ext.XTemplate(
                    '<tpl for="if_name_list">',
                    "<div>{.}</div>",
                    '</tpl>'
                ),
                filter: {
                    type: 'list',
                },
                stateId: 'StateGridSENetworkBridges2'
            },
            {
                text: i18n('sStatus'),
                dataIndex: 'status',
                flex: 1,
                filter: {
                    type: 'string'
                },
                xtype:  'templatecolumn', 
                tpl:    new Ext.XTemplate(
                            "<tpl if='status == true'><div class=\"fieldGreen\">"+i18n("sRunning")+"</div></tpl>",
                            "<tpl if='status == false'><div class=\"fieldRed\">"+i18n("sStop")+"</div></tpl>"
                ),
                filter      : {
                    type    : 'boolean',
                    defaultValue   : false,
                    yesText : i18n("sRunning"),
                    noText  : i18n("sStop")
                },
                stateId: 'StateGridSENetworkBridges3'
            },
            {
                text: i18n('sIP_Address'),
                dataIndex: 'ip_address',
                tdCls: 'gridTree',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSENetworkBridges4'
            },
            {
                text: i18n('sSubnet_mask'),
                dataIndex: 'subnet_mask',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSENetworkBridges5'
            },

        ];
        me.callParent(arguments);
    }
});
