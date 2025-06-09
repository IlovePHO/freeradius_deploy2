Ext.define('Rd.view.externalApiKeys.gridExternalApiKeys', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridExternalApiKeys',
    multiSelect: true,
    store: 'sExternalApiKeys',
    stateful: true,
    stateId: 'StateGridExternalApiKeys',
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
    urlMenu: '/cake3/rd_cake/external-api-keys/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StategridExternalApiKeys0'},
            {
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridExternalApiKeys1'
            },
            {
                text: i18n('sRealm'),
                dataIndex: 'realm_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridExternalApiKeys2'
            },
            {
                text: i18n('sProfile'),
                dataIndex: 'profile_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridExternalApiKeys3'
            },
            {
                text: i18n('sAdd_API_Key'),
                dataIndex: 'api_key',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridExternalApiKeys4'
            },

            {
                xtype: 'actioncolumn',
                text: 'Actions',
                width: 80,
                stateId: 'StategridExternalApiKeys5',
                items: [
                    {
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: 'Delete',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridExternalApiKeys').down('#delete')){
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
                        tooltip: 'Edit',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('update') == true) {
                            if(grid.up('gridExternalApiKeys').down('#edit')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'update');
                        }
                    },

                ]
            }
        ];
        me.callParent(arguments);
    }
});
