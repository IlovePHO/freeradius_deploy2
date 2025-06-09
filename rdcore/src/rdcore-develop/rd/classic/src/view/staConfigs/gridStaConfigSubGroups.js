Ext.define('Rd.view.staConfigs.gridStaConfigSubGroups', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridStaConfigSubGroups',
    stateful: true,
    stateId: 'StateGridStaConfigSubGroups',
    stateEvents: ['groupclick', 'columnhide'],
    border: false,
    requires: ['Rd.view.components.advCheckColumn'],
    stsConf_id: null,
    selected: [],// Selected from view.json loadSettings()
    config: {
        urlView: '/cake3/rd_cake/sta-configs/view.json',
        urlList: '/cake3/rd_cake/sub-groups/index.json',
        urlEdit: '/cake3/rd_cake/sta-configs/edit-sub-groups.json',
    },
    listeners: {
        show: 'loadSettings', //Trigger a load of the settings
        // afterrender: 'loadSettings',
    },
    bbar: [{
        xtype: 'component',
        itemId: 'count',
        tpl: i18n('sResult_count_{count}'),
        style: 'margin-right:5px',
        cls: 'lblYfi'
    }],
    loadSettings: function () {
        var me = this;

        Ext.Ajax.request({
            url: me.getUrlView(),
            method: 'GET',
            params: {
                sta_config_id: me.stsConf_id
            },
            success: function (response) {
                var jsonData = Ext.JSON.decode(response.responseText);
                if (jsonData.success) {

                    if (jsonData.data.sub_groups) {
                        me.selected = jsonData.data.sub_groups;
                        me.getStore().load();
                    }
                }

            }
        });
    },
    initComponent: function () {

        var me = this;

        me.tbar = [{
            xtype: 'button',
            iconCls: 'b-reload',
            glyph: Rd.config.icnReload,
            scale: 'large',
            itemId: 'reload',
            tooltip: i18n('sReload'),
            scope: this,
            listeners: {
                click: function(b){
                   me.loadSettings();
                }
            }
        }];

        //Create a store specific to this Access Provider
        me.store = Ext.create(Ext.data.Store, {
            fields: [
                {
                    name: 'id',
                    type: 'int'
                },
                {
                    name: 'name',
                    type: 'string'
                },
                {
                    name: 'enable',
                    type: 'bool',
                    defaultValue: false
                }
            ],
            proxy: {
                type: 'ajax',
                format: 'json',
                batchActions: true,
                url: me.getUrlList(),
                reader: {
                    type: 'json',
                    rootProperty: 'items',
                    messageProperty: 'message'
                },
            },
            listeners: {
                load: function (store, records, successful) {
                    if (!successful) {
                        Ext.ux.Toaster.msg(
                            'Error encountered',
                            store.getProxy().getReader().rawData.message.message,
                            Ext.ux.Constants.clsWarn,
                            Ext.ux.Constants.msgWarn
                        );
                    } else {
                        // Create enable Flg
                        records.forEach(function (record) {
                            var enable = me.chk_selcted(record);
                            record.set("enable", enable, {dirty:false});
                        });
                        // to bottom-bar total
                        var count = store.getTotalCount();
                        me.down('#count').update({
                            count: count
                        });
                    }
                },
                scope: this
            },
            autoLoad: false
        });


        me.columns = [{
                text: i18n('sRealm'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 1
            },
            {
                xtype: 'advCheckColumn',
                itemId: 'advCheck',
                text: i18n('sEnable'),
                dataIndex: 'enable',
                renderer: function (value, meta, record) {
                    var cssPrefix = Ext.baseCSSPrefix,
                        cls = [cssPrefix + 'grid-checkheader'],
                        disabled = false;
                    if (value && disabled) {
                        cls.push(cssPrefix + 'grid-checkheader-checked-disabled');
                    } else if (value) {
                        cls.push(cssPrefix + 'grid-checkheader-checked');
                    } else if (disabled) {
                        cls.push(cssPrefix + 'grid-checkheader-disabled');
                    }
                    return '<div class="' + cls.join(' ') + '">&#160;</div>';
                },
                listeners: {
                    checkchange: me.onCheckchange
                }
            },

        ];
        me.callParent(arguments);
    },
    chk_selcted: function (record) {
        var me = this;
        //console.log('chk_selcted', record);
        if (me.selected.indexOf(record.id) >= 0) {
            return true;
        }
        return false;
    },
    onCheckchange: function (c, rowIndex, checked, record) {
        //var me = this;
        var grid = c.up('grid');
       
        
        //console.log('onCheckchange', c, rowIndex, checked, record);
        var params = {
            id: grid.stsConf_id,
            sub_group_id: record.id,
            enable: checked
        };

        Ext.Ajax.request({
            url: grid.getUrlEdit(),
            method: 'POST',
            jsonData: params,
            success: function (response) {
                var jsonData = Ext.JSON.decode(response.responseText);
                if (jsonData.success) {
                    Ext.ux.Toaster.msg(
                        i18n('sItem_updated'),
                        i18n('sItem_updated_fine'),
                        Ext.ux.Constants.clsInfo,
                        Ext.ux.Constants.msgInfo
                    );
                    record.commit(); // dirty flag OFF
                    // console.log(record,c);
                } else {
                    //Server error
                    var message = jsonData.message ? (jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
                    Ext.ux.Toaster.msg(
                        i18n('sProblems_updating_the_item'),
                        message,
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
                    );
                }
            },
            failure: Ext.ux.ajaxFail
        });
    },
});
