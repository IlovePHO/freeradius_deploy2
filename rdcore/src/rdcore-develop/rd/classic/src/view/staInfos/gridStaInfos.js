Ext.define('Rd.view.staInfos.gridStaInfos', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridStaInfos',
    multiSelect: true,
    store: 'sStaInfos',
    stateful: true,
    stateId: 'StategridStaInfos',
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
    urlMenu: '/cake3/rd_cake/sta-infos/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StategridStaInfos0'},
            {
                text: i18n('sOwner'),
                dataIndex: 'username',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaInfos1'
            },
            {
                text: i18n('sOwner_type'),
                dataIndex: 'user_type',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaInfos2'
            },
            {
                text: i18n('sDevice_type'),
                dataIndex: 'device_type',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaInfos3'
            },
            {
                text: i18n('sShort_unique_id'),
                dataIndex: 'short_unique_id',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaInfos4'
            },
            {
                text: i18n('sSta_configs'),
                sortable: false,
                flex: 1,
                xtype: 'templatecolumn',
                tpl: new Ext.XTemplate(
                    '<tpl for="sta_configs">',
                    "<div>{name}</div>",
                    '</tpl>'
                ),
                dataIndex: 'sta_configs',
                filter: {
                    type: 'list',
                    store: 'sStaConfigs'
                },
                stateId: 'StategridStaInfos6'
            },
            {
                text: i18n('sExpire'),
                sortable: false,
                flex: 1,
                xtype: 'templatecolumn',
                tpl: new Ext.XTemplate(
                    '<tpl for="sta_configs">',
                    "<div>{expire}</div>",
                    '</tpl>'
                ),
                dataIndex: 'sta_configs',
                filter: {
                    type: 'list',
                    store: 'sStaConfigs'
                },
                stateId: 'StategridStaInfos7'
            },
            {
                xtype: 'actioncolumn',
                text: 'Actions',
                width: 80,
                stateId: 'StategridStaInfos8',
                items: [{
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: 'Delete',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridStaInfos').down('#delete')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'delete');
                        }
                    },

                ]
            }
        ];
        me.callParent(arguments);
    }
});
