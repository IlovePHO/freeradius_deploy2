Ext.define('Rd.view.encodingSchemes.gridEncodingSchemes', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridEncodingSchemes',
    multiSelect: true,
    store: 'sEncodingSchemes',
    stateful: true,
    stateId: 'StateGridEncodingSchemes',
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
    urlMenu: '/cake3/rd_cake/encoding-schemes/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StategridEncodingSchemes0'},
            
            
            {
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridEncodingSchemes1'
            },
            {
                text: i18n('sSuffix'),
                dataIndex: 'suffix',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridEncodingSchemes2'
            },
            {
                text: i18n('sExpire'),
                dataIndex: 'expire',
                tdCls: 'gridTree',
                flex: 1,
                xtype:'datecolumn',
                format: 'Y/m/d',
                filter: {
                    type: 'date'
                },
                stateId: 'StategridEncodingSchemes3'
            },

            {
                xtype: 'actioncolumn',
                text: 'Actions',
                width: 80,
                stateId: 'StategridEncodingSchemes4',
                items: [
                    {
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: 'Delete',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridEncodingSchemes').down('#delete')){
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
                            if(grid.up('gridEncodingSchemes').down('#edit')){
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
