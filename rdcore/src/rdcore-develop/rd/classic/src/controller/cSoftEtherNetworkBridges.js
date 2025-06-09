Ext.define('Rd.controller.cSoftEtherNetworkBridges', {
    extend: 'Ext.app.Controller',
    actionIndex: function (pnl) {
        var me = this;
        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridSoftEtherNetworkBridges',
            padding: Rd.config.gridPadding,
            border: false,
            plain: true
        });

        pnl.on({
            activate: me.gridActivate,
            scope: me
        });
        me.populated = true;

    },
    views: [
        'softEtherNetworkBridges.gridSoftEtherNetworkBridges',
        'softEtherNetworkBridges.winSoftEtherNetworkBridgeAdd',
        'softEtherNetworkBridges.pnlSoftEtherNetworkBridge',
        'softEtherNetworkBridges.gridSoftEtherNetworkBridgesInterfaces',
        'softEtherNetworkBridges.pnlSoftEtherNetworkBridgeStatus',
        'softEtherNetworkBridges.pnlSoftEtherNetworkBridgeAddress',
        'softEtherNetworkBridges.winSoftEtherNetworkBridgesInterfacesAdd',
        'softEtherNetworkBridges.cmbEtherNetworkBridgeInterfaces',
    ],
    stores: [
        'sSoftEtherNetworkBridges',
        'sSoftEtherNetworkBridgeInterfaces',
    ],
    models: [
        'mSoftEtherNetworkBridge',
        'mSoftEtherNetworkBridgeInterface'
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck: '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd: '/cake3/rd_cake/soft-ether-network-bridges/add.json',
        urlDelete: '/cake3/rd_cake/soft-ether-network-bridges/delete.json',
        urlEditStatus: '/cake3/rd_cake/soft-ether-network-bridges/edit-status.json',
        urlEditAddress: '/cake3/rd_cake/soft-ether-network-bridges/edit-address.json',
        urlAddInterfaces: '/cake3/rd_cake/soft-ether-network-bridges/add-interfaces.json',
        urlDeleteInterfaces: '/cake3/rd_cake/soft-ether-network-bridges/delete-interfaces.json'
    },
    refs: [
        { ref: 'grid',  selector: 'gridSoftEtherNetworkBridges' },
    ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            'gridSoftEtherNetworkBridges #reload': {
                click: me.reload
            },
            'gridSoftEtherNetworkBridges #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            },
            'gridSoftEtherNetworkBridges #add': {
                click: me.add
            },
            'gridSoftEtherNetworkBridges #edit': {
                click: me.edit
            },
            'gridSoftEtherNetworkBridges #delete': {
                click: me.del
            },
            'gridSoftEtherNetworkBridges #synchronize': {
                click: me.sync
            },
            'gridSoftEtherNetworkBridges actioncolumn': {
                itemClick: me.onActionColumnItemClick
            },
            'winSoftEtherNetworkBridgeAdd #btnDataNext': {
                click: me.btnDataNext
            },
            'pnlSoftEtherNetworkBridgeStatus #save': {
                click: me.btnSaveStatus
            },
            'pnlSoftEtherNetworkBridgeAddress #save': {
                click: me.btnSaveAddress
            },
            'gridSoftEtherNetworkBridgesInterfaces #reload': {
                click: me.reloadInterfaces
            },
            'gridSoftEtherNetworkBridgesInterfaces #add': {
                click: me.addInterfaces
            },
            'gridSoftEtherNetworkBridgesInterfaces #delete': {
                click: me.delInterfaces
            },
            'winSoftEtherNetworkBridgesInterfacesAdd #btnDataNext': {
                click: me.btnSaveInterfaces
            }
        });
    },
    gridActivate: function (g) {
        var me = this;
        var grid = g.down('grid');
        if (grid) {
            grid.getStore().load();
        } else {
            g.getStore().load();
        }
    },
    appClose: function () {
        var me = this;
        me.populated = false;
    },
    reload: function () {
        var me = this;
        me.getGrid().getSelectionModel().deselectAll(true);
        me.getGrid().getStore().load();
    },
    reloadOptionClick: function (menu_item) {
        var me = this;
        var n = menu_item.getItemId();
        var b = menu_item.up('button');
        var interval = 30000; //default
        clearInterval(me.autoReload); //Always clear
        b.setGlyph(Rd.config.icnTime);

        if (n == 'mnuRefreshCancel') {
            b.setGlyph(Rd.config.icnReload);
            return;
        }

        if (n == 'mnuRefresh1m') {
            interval = 60000
        }

        if (n == 'mnuRefresh5m') {
            interval = 360000
        }
        me.autoReload = setInterval(function () {
            me.reload();
        }, interval);
    },

    onActionColumnItemClick: function (view, rowIndex, colIndex, item, e, record, row, action) {
        var me = this;
        var grid = view.up('grid');
        grid.setSelection(record);
        var button = grid.down('button');
        if(action == 'update'){
            me.edit(button); 
        }
        if(action == 'delete'){
            me.del(button); 
        }
        if (action == 'sync') {
            me.sync(button);
        }
    },
    add: function (button) {
        var me = this;
        if (!Ext.WindowManager.get('winSoftEtherNetworkBridgeAddId')) {
            var w = Ext.widget('winSoftEtherNetworkBridgeAdd', {
                id: 'winSoftEtherNetworkBridgeAddId'
            });
            w.show();
        }
    },

    btnDataNext: function (button) {
        var me = this;
        var win = button.up('window');
        var form = win.down('form');
        form.submit({
            clientValidation: true,
            url: me.getUrlAdd(),
            success: function (form, action) {
                win.close();
                me.reload(); //Reload from server
                Ext.ux.Toaster.msg(
                    i18n('sNew_item_created'),
                    i18n('sItem_created_fine'),
                    Ext.ux.Constants.clsInfo,
                    Ext.ux.Constants.msgInfo
                );
            },
            failure: Ext.ux.formFail
        });
    },


    del: function (button) {
        var me = this;
        //Find out if there was something selected
        if (me.getGrid().getSelectionModel().getCount() == 0) {
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_item'),
                i18n('sFirst_select_an_item_to_delete'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        } else {
            Ext.MessageBox.confirm(i18n('sConfirm'), i18n('sAre_you_sure_you_want_to_do_that_qm'), function (val) {
                if (val == 'yes') {
                    var selected = me.getGrid().getSelectionModel().getSelection();
                    var list = [];
                    Ext.Array.forEach(selected, function (item) {
                        var id = item.getId();
                        Ext.Array.push(list, {
                            'id': id
                        });
                    });
                    Ext.Ajax.request({
                        url: me.getUrlDelete(),
                        method: 'POST',
                        jsonData: list,
                        success: function (response) {
                            var jsonData = Ext.JSON.decode(response.responseText);
                            if (jsonData.success) {
                                //console.log('success');
                                Ext.ux.Toaster.msg(
                                    i18n('sItem_deleted'),
                                    i18n('sItem_deleted_fine'),
                                    Ext.ux.Constants.clsInfo,
                                    Ext.ux.Constants.msgInfo
                                );
                            } else {
                                //Server error
                                var message = jsonData.message ? (jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
                                Ext.ux.Toaster.msg(
                                    i18n('sProblems_deleting_item'),
                                    message,
                                    Ext.ux.Constants.clsWarn,
                                    Ext.ux.Constants.msgWarn
                                );
                            }
                            me.reload(); //Reload from server
                        },
                        failure: Ext.ux.ajaxFail
                    });
                }
            });
        }
    },
    edit: function (button) {
        var me = this;
        var grid = me.getGrid();
        //Find out if there was something selected
        if (me.getGrid().getSelectionModel().getCount() == 0) {
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_item'),
                i18n('sFirst_select_an_item'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        } else {
            var selected = me.getGrid().getSelectionModel().getSelection();
            var count = selected.length;
            Ext.each(me.getGrid().getSelectionModel().getSelection(), function (sr, index) {
                //Check if the node is not already open; else open the node:
                var tp = me.getGrid().up('tabpanel');
                var id = sr.getId();
                var tab_id = 'SoftEtherNetworkBridgeTab_' + id;
                var nt = tp.down('#' + tab_id);
                if (nt) {
                    tp.setActiveTab(tab_id); //Set focus on  Tab
                    return;
                }

                
                var tab_name = sr.get('ip_address');
                
                //Tab not there - add one
                tp.add({
                    title: tab_name,
                    itemId: tab_id,
                    closable: true,
                    glyph: Rd.config.icnEdit,
                    xtype: 'pnlSoftEtherNetworkBridge',
                    record: sr,
                    tabConfig: {
                        ui: me.ui
                    }
                });
                tp.setActiveTab(tab_id); //Set focus on Add Tab

            });
        }
    },

    btnSaveStatus: function (button) {
        var me = this;
        var form = button.up('form');
        var tab = button.up('pnlSoftEtherNetworkBridge');
        form.submit({
            clientValidation: true,
            url: me.getUrlEditStatus(),
            success: function (form, action) {
                tab.close();
                me.reload(); //Reload from server
                Ext.ux.Toaster.msg(
                    i18n('sItem_updated'),
                    i18n('sItem_updated_fine'),
                    Ext.ux.Constants.clsInfo,
                    Ext.ux.Constants.msgInfo
                );
            },
            failure: Ext.ux.formFail
        });
    },
    btnSaveAddress: function (button) {
        var me = this;
        var form = button.up('form');
        var tab = button.up('pnlSoftEtherNetworkBridge');
        form.submit({
            clientValidation: true,
            url: me.getUrlEditAddress(),
            success: function (form, action) {
                tab.close();
                me.reload(); //Reload from server
                Ext.ux.Toaster.msg(
                    i18n('sItem_updated'),
                    i18n('sItem_updated_fine'),
                    Ext.ux.Constants.clsInfo,
                    Ext.ux.Constants.msgInfo
                );
            },
            failure: Ext.ux.formFail
        });
    },

    reloadInterfaces: function (b) {
        var me = this;
        var grid = null;
        if (b.isXType('grid')) {
            grid = b;
        } else {
            grid = b.up('grid');
        }
        if (grid) {
            grid.getSelectionModel().deselectAll(true);
            grid.getStore().load();
        }
    },
    addInterfaces: function (button) {
        var me = this;
        var grid = button.up('grid');
        var network_bridge_id=  grid.network_bridge_id;
        var win_id = 'winSoftEtherNetworkBridgesInterfacesAdd'+network_bridge_id;
        if (!Ext.WindowManager.get(win_id)) {
            var w = Ext.widget('winSoftEtherNetworkBridgesInterfacesAdd', {
                id: win_id,
                 network_bridge_id:network_bridge_id,
                grid_id :grid.getItemId(), // grid Interfaces
            });
            w.show();
        }
    },
    btnSaveInterfaces: function (button) {
        var me = this;
        var win = button.up('window');
        var form = win.down('form');
        var tp   = me.getGrid().up('tabpanel');
        var grid = tp.down('#'+win.grid_id); // grid Interfaces
        form.submit({
            clientValidation: true,
            url: me.getUrlAddInterfaces(),
            success: function (form, action) {
                win.close();
                me.reloadInterfaces(grid); //Reload from server
                Ext.ux.Toaster.msg(
                    i18n('sNew_item_created'),
                    i18n('sItem_created_fine'),
                    Ext.ux.Constants.clsInfo,
                    Ext.ux.Constants.msgInfo
                );
            },
            failure: Ext.ux.formFail
        });
    },
    delInterfaces: function (button) {
        var me = this;
        var grid = button.up('grid');
        //Find out if there was something selected
        if (grid.getSelectionModel().getCount() == 0) {
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_item'),
                i18n('sFirst_select_an_item_to_delete'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        } else {
            Ext.MessageBox.confirm(i18n('sConfirm'), i18n('sAre_you_sure_you_want_to_do_that_qm'), function (val) {
                if (val == 'yes') {
                    var selected = grid.getSelectionModel().getSelection();
                    var list = [];
                    Ext.Array.forEach(selected, function (item) {
                        var id = item.getId();
                        Ext.Array.push(list, {
                            'id': id
                        });
                    });
                    Ext.Ajax.request({
                        url: me.getUrlDeleteInterfaces(),
                        method: 'POST',
                        jsonData: list,
                        success: function (response) {
                            var jsonData = Ext.JSON.decode(response.responseText);
                            if (jsonData.success) {
                                //console.log('success');
                                Ext.ux.Toaster.msg(
                                    i18n('sItem_deleted'),
                                    i18n('sItem_deleted_fine'),
                                    Ext.ux.Constants.clsInfo,
                                    Ext.ux.Constants.msgInfo
                                );
                            } else {
                                //Server error
                                var message = jsonData.message ? (jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
                                Ext.ux.Toaster.msg(
                                    i18n('sProblems_deleting_item'),
                                    message,
                                    Ext.ux.Constants.clsWarn,
                                    Ext.ux.Constants.msgWarn
                                );
                            }
                            me.reloadInterfaces(button); //Reload from server
                        },
                        failure: Ext.ux.ajaxFail
                    });
                }
            });
        }
    },
});
