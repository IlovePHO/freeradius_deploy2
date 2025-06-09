Ext.define('Rd.view.softEtherVpnInstances.gridSoftEtherVpnInstances', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherVpnInstances',
    multiSelect: true,
    store: 'sSoftEtherVpnInstances',
    stateful: true,
    stateId: 'StategridSoftEtherVpnInstances',
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
    urlMenu: '/cake3/rd_cake/soft-ether-vpn-instances/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StategridSoftEtherVpnInstances0'},
            {
                text: i18n('sIP_Address'),
                dataIndex: 'ip_address',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVpnInstances1'
            },
            /*{
                text: i18n('sAdmin_name'),
                dataIndex: 'admin_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVpnInstances2'
            },*/
            {
                text: i18n('sPassword'),
                dataIndex: 'password',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVpnInstances2'
            },
            {
                text: i18n('sConfig_hash_value'),
                dataIndex: 'config_hash_value',
                tdCls: 'gridTree',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVpnInstances3'
            },

            {
                xtype: 'actioncolumn',
                text: 'Actions',
                width: 80,
                stateId: 'StategridSoftEtherVpnInstances4',
                items: [
                    {
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: i18n('sDelete'),
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridSoftEtherVpnInstances').down('#delete')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'delete');
                        }
                    },
                    {
                        iconCls: 'txtBlue x-fa fa-pen',
                        tooltip: i18n('sEdit'),
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('update') == true) {
                            if(grid.up('gridSoftEtherVpnInstances').down('#edit')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'update');
                        }
                    },
                    {
                        iconCls: 'txtMetal x-fa fa-random',
                        tooltip: i18n('sSynchronize'),
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            if(grid.up('gridSoftEtherVpnInstances').down('#synchronize')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'sync');
                        }
                    },
                ]
            }
        ];
        me.callParent(arguments);
    }
});
