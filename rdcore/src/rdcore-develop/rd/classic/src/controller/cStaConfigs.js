Ext.define('Rd.controller.cStaConfigs', {
    extend: 'Ext.app.Controller',

    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridStaConfigs',
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
        'staConfigs.gridStaConfigs',
        'staConfigs.winStaConfigAddWizard',
        'staConfigs.pnlStaConfig',
    ],
    stores: [
        'sStaConfigs',
    ],
    models: [
        'mStaConfig',
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck: '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd: '/cake3/rd_cake/sta-configs/add.json',
        urlDelete: '/cake3/rd_cake/sta-configs/delete.json',
        urlEdit: '/cake3/rd_cake/sta-configs/edit.json',
    },
    refs: [{
        ref: 'grid',
        selector: 'gridStaConfigs'
    }, ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            'gridStaConfigs #reload': {
                click: me.reload
            },
            'gridStaConfigs #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            },
            'gridStaConfigs #add': {
                click: me.add
            },
            'gridStaConfigs #edit': {
                click: me.edit
            },
            'gridStaConfigs #delete': {
                click: me.del
            },
            'gridStaConfigs actioncolumn': {
                itemClick: me.onActionColumnItemClick
            },
            'winStaConfigAddWizard #btnTreeNext': {
                click: me.btnTreeNext
            },
            'winStaConfigAddWizard #btnDataPrev': {
                click: me.btnDataPrev
            },
            'winStaConfigAddWizard #btnDataNext': {
                click: me.btnDataNext
            },

            'pnlStaConfigBasic #save': {
                click: me.btnEditSave
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
        if (action == 'update') {
            me.edit();
        }
        if (action == 'delete') {
            me.del();
        }
    },
    add: function (button) {
        var me = this;
        // We need to do a check to determine if this user (be it admin or acess provider has the ability to add to children)
        // admin/root will always have, an AP must be checked if it is the parent to some sub-providers. If not we will simply show the add window
        // if it does have, we will show the add wizard.
        Ext.Ajax.request({
            url: me.getUrlApChildCheck(),
            method: 'GET',
            success: function (response) {
                var jsonData = Ext.JSON.decode(response.responseText);
                if (jsonData.success) {
                    if (jsonData.items.tree == true) {
                        if (!Ext.WindowManager.get('winStaConfigAddWizardId')) {
                            var w = Ext.widget('winStaConfigAddWizard', {
                                id: 'winStaConfigAddWizardId'
                            });
                            w.show();
                        }
                    } else {
                        if (!Ext.WindowManager.get('winStaConfigAddWizardId')) {
                            var w = Ext.widget('winStaConfigAddWizard', {
                                id: 'winStaConfigAddWizardId',
                                startScreen: 'scrnData',
                                user_id: '0',
                                owner: i18n('sLogged_in_user'),
                                no_tree: true
                            });
                            w.show();
                        }
                    }
                }
            },
            scope: me
        });
    },
    btnTreeNext: function (button) {
        var me = this;
        var tree = button.up('treepanel');
        //Get selection:
        var sr = tree.getSelectionModel().getLastSelected();
        if (sr) {
            var win = button.up('winStaConfigAddWizard');
            win.down('#owner').setValue(sr.get('username'));
            win.down('#user_id').setValue(sr.getId());
            win.getLayout().setActiveItem('scrnData');
        } else {
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_owner'),
                i18n('sFirst_select_an_Access_Provider_who_will_be_the_owner'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        }
    },
    btnDataPrev: function (button) {
        var me = this;
        var win = button.up('winStaConfigAddWizard');
        win.getLayout().setActiveItem('scrnApTree');
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
                var tab_id = 'StaConfigTab_' + id;
                var nt = tp.down('#' + tab_id);
                if (nt) {
                    tp.setActiveTab(tab_id); //Set focus on  Tab
                    return;
                }

                //var tab_name = me.selectedRecord.get('name');
                var tab_name = sr.get('name');
                //Tab not there - add one
                tp.add({
                    title: tab_name,
                    itemId: tab_id,
                    closable: true,
                    glyph: Rd.config.icnEdit,
                    xtype: 'pnlStaConfig',
                    stsConf_id: id,
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
        // var tab = button.up('pnlStaConfig');
        form.submit({
            clientValidation: true,
            url: me.getUrlEdit(),
            success: function (form, action) {
                // tab.close();
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

});
