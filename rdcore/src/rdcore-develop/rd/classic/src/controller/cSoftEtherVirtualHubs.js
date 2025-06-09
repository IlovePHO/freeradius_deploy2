Ext.define('Rd.controller.cSoftEtherVirtualHubs', {
    extend: 'Ext.app.Controller',

    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridSoftEtherVirtualHubs',
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
        'softEtherVirtualHubs.gridSoftEtherVirtualHubs',
        'softEtherVirtualHubs.winSoftEtherVirtualHubAdd',
        'softEtherVirtualHubs.pnlSoftEtherVirtualHub',
        'softEtherVirtualHubs.pnlSoftEtherVirtualHubBasic',
        'softEtherVirtualHubs.gridSoftEtherUsers',
        'softEtherVirtualHubs.winSoftEtherUserAdd',
        'softEtherVirtualHubs.pnlSoftEtherUserEdit',
        'softEtherVirtualHubs.pnlSoftEtherSecureNatsEdit',
    ],
    stores: [
        'sSoftEtherVirtualHubs',
        'sSoftEtherUsers'
    ],
    models: [
        'mSoftEtherVirtualHub',
        'mSoftEtherUser'
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck: '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd: '/cake3/rd_cake/soft-ether-virtual-hubs/add.json',
        urlDelete: '/cake3/rd_cake/soft-ether-virtual-hubs/delete.json',
        urlEdit: '/cake3/rd_cake/soft-ether-virtual-hubs/edit.json',
        urlAddUser: '/cake3/rd_cake/soft-ether-users/add.json',
        urlDeleteUser: '/cake3/rd_cake/soft-ether-users/delete.json',
        urlEditUser: '/cake3/rd_cake/soft-ether-users/edit.json',
        urlSaveNat: '/cake3/rd_cake/soft-ether-secure-nats/save.json',
    },
    refs: [
        { ref: 'grid',      selector: 'gridSoftEtherVirtualHubs' },
    ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            'gridSoftEtherVirtualHubs #reload': {
                click: me.reload
            },
            'gridSoftEtherVirtualHubs #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            },
            'gridSoftEtherVirtualHubs #add': {
                click: me.add
            },
            'gridSoftEtherVirtualHubs #edit': {
                click: me.edit
            },
            'gridSoftEtherVirtualHubs #delete': {
                click: me.del
            },
            'gridSoftEtherVirtualHubs actioncolumn': {
                itemClick: me.onActionColumnItemClick
            },
            'winSoftEtherVirtualHubAdd #btnDataNext': {
                click: me.btnDataNext
            },
            'pnlSoftEtherVirtualHubBasic #save': {
                click: me.btnEditSave
            },
            //users
            'gridSoftEtherUsers #reload': {
                click: me.reload
            },
            'gridSoftEtherUsers #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            },
            'gridSoftEtherUsers #add': {
                click: me.addUser
            },
            'gridSoftEtherUsers #edit': {
                click: me.editUser
            },
            'gridSoftEtherUsers #delete': {
                click: me.delUser
            },
            'winSoftEtherUserAdd #btnDataNext': {
                click: me.btnUserDataNext
            },
            'pnlSoftEtherUserEdit #save': {
                click: me.btnEditUserSave
            },
            //SecureNAT
            'pnlSoftEtherSecureNatsEdit #save': {
                click: me.btnEditNatSave
            },

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
    reload: function (b) {
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
            me.reload(b);
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
        
        if (!Ext.WindowManager.get('winSoftEtherVirtualHubAddId')) {
            var w = Ext.widget('winSoftEtherVirtualHubAdd', {
                id: 'winSoftEtherVirtualHubAddId'
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
                me.reload(me.getGrid()); //Reload from server
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
                            me.reload(button); //Reload from server
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
                var tab_id = 'SoftEtherVirtualHubTab_' + id;
                var nt = tp.down('#' + tab_id);
                if (nt) {
                    tp.setActiveTab(tab_id); //Set focus on  Tab
                    return;
                }

                var tab_name = sr.get('hub_name');
                
                //Tab not there - add one
                tp.add({
                    title: tab_name,
                    itemId: tab_id,
                    closable: true,
                    glyph: Rd.config.icnEdit,
                    xtype: 'pnlSoftEtherVirtualHub',
                    record: sr,
                    tabConfig: {
                        ui: me.ui
                    }
                });
                tp.setActiveTab(tab_id); //Set focus on Add Tab

            });
        }
    },

    btnEditSave: function (button) {
        var me = this;
        var form = button.up('form');
        var tab = button.up('pnlSoftEtherVirtualHub');
        form.submit({
            clientValidation: true,
            url: me.getUrlEdit(),
            success: function (form, action) {
                tab.close();
                me.reload(me.getGrid()); //Reload from server
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
    //Users
    addUser: function (button) {
        var me = this;
        var grid = button.up('gridSoftEtherUsers');
        var vHub_id = grid.vHub_id;
        var winId = 'winSoftEtherUserAdd'+vHub_id;
        if (!Ext.WindowManager.get(winId)) {
            var w = Ext.widget('winSoftEtherUserAdd', {
                id: winId,
                vHub_id: vHub_id,
                grid_id :grid.getItemId(), //users grid
            });
            w.show();
        }
    },
    btnUserDataNext: function (button) {
        var me = this;
        var win = button.up('window');
        var form = win.down('form');
        var tp   = me.getGrid().up('tabpanel');
        var grid = tp.down('#'+win.grid_id); //users grid
        form.submit({
            clientValidation: true,
            url: me.getUrlAddUser(),
            success: function (form, action) {
                win.close();
                me.reload(grid); //Reload from server
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
    delUser: function (button) {
        var me = this;
        var grid = button.up('gridSoftEtherUsers');
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
                        url: me.getUrlDeleteUser(),
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
                            me.reload(button); //Reload from server
                        },
                        failure: Ext.ux.ajaxFail
                    });
                }
            });
        }
    },
    editUser: function (button) {
        var me = this;
        var grid = button.up('gridSoftEtherUsers');
        var selected = grid.getSelectionModel().getSelection();
        //Find out if there was something selected
        if (selected.length == 0) {
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_item'),
                i18n('sFirst_select_an_item'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        } else {
            Ext.each(selected, function (sr, index) {
                //Check if the node is not already open; else open the node:
                var tp = grid.up('tabpanel');
                var vHub_id = grid.vHub_id;
                var id = sr.getId();
                var tab_id = 'pnlSoftEtherUserTab_' + id;
                var nt = tp.down('#' + tab_id);
                if (nt) {
                    tp.setActiveTab(tab_id); //Set focus on  Tab
                    return;
                }

                var tab_name = sr.get('user_name');
                
                //Tab not there - add one
                tp.add({
                    title: tab_name,
                    itemId: tab_id,
                    closable: true,
                    glyph: Rd.config.icnEdit,
                    xtype: 'pnlSoftEtherUserEdit',
                    vHub_id: vHub_id,
                    grid_id :grid.getItemId(), //users grid
                    record: sr,
                    tabConfig: {
                        ui: me.ui
                    }
                });
                tp.setActiveTab(tab_id); //Set focus on Add Tab

            });
        }
    },
    btnEditUserSave: function (button) {
        var me = this;
        var form = button.up('form');
        var tab  = button.up('pnlSoftEtherUserEdit');
        var tp   = tab.up('tabpanel');
        var grid = tp.down('#'+tab.grid_id); //users grid
        form.submit({
            clientValidation: true,
            url: me.getUrlEditUser(),
            success: function (form, action) {
                tab.close();
                me.reload(grid); //Reload from server
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
    btnEditNatSave: function (button) {
        var me = this;
        var form = button.up('form');
        var tab  = button.up('pnlSoftEtherVirtualHub');
        var tp   = tab.up('tabpanel');
        form.submit({
            clientValidation: true,
            url: me.getUrlSaveNat(),
            success: function (form, action) {
                tab.close();
                me.reload(me.getGrid()); //Reload from server
                Ext.ux.Toaster.msg(
                    i18n('sItem_updated'),
                    i18n('sItem_updated_fine'),
                    Ext.ux.Constants.clsInfo,
                    Ext.ux.Constants.msgInfo
                );
            },
            failure: Ext.ux.formFail
        });
    }
});
