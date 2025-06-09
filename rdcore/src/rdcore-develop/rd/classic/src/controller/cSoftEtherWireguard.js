Ext.define('Rd.controller.cSoftEtherWireguard', {
    extend: 'Ext.app.Controller',
    views: [
        'softEtherWireguard.pnlSoftEtherWireguard',
        'softEtherWireguard.pnlSoftEtherWireguardEdit',
        'softEtherWireguard.gridSoftEtherWireguardPublicKeys',
        'softEtherWireguard.winSoftEtherWireguardPublicKeyAdd',
        'softEtherWireguard.cmbWireguardVirtualHub',
        'softEtherWireguard.cmbWireguardUsers',
    ],
    stores:[
        'sSoftEtherWireguardPublicKeys'
    ],
    models:[
        'mSoftEtherWireguardPublicKey'
    ],
    config: {
        urlSave: '/cake3/rd_cake/soft-ether-wireguard/save.json',
        urlAddKey: '/cake3/rd_cake/soft-ether-wireguard/add-public-key.json',
        urlDeleteKey: '/cake3/rd_cake/soft-ether-wireguard/delete-public-key.json',
    }, 
    refs: [
        { ref: 'grid',      selector: 'gridSoftEtherWireguardPublicKeys' },
    ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        
        me.control({
            'pnlSoftEtherWireguardEdit #save': {
                click: me.btnSave
            },
            'gridSoftEtherWireguardPublicKeys #reload': {
                click: me.reload
            },
            'gridSoftEtherWireguardPublicKeys #add': {
                click: me.add
            },
            'gridSoftEtherWireguardPublicKeys #delete': {
                click: me.del
            },
            'winSoftEtherWireguardPublicKeyAdd #btnDataNext' : {
                click:  me.btnDataNext
            },
        });
        
    },
    actionIndex: function (pnl) {
        var me = this;
        if (me.populated) {
            return; 
        } 
        pnl.add({
            xtype   : 'pnlSoftEtherWireguard',
            border  : false,
            itemId  : 'tabSoftEtherWireguard',
            plain   : true
        });
        me.populated = true;
    },
    btnSave: function (button) {
        var me = this;
        var form = button.up('form');
        form.submit({
            clientValidation: true,
            url: me.getUrlSave(),
            success: function (form, action) {
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
   //PublicKeys
    reload: function () {
        var me = this;
        me.getGrid().getSelectionModel().deselectAll(true);
        me.getGrid().getStore().load();
    },
    
    add: function (button) {
        var me = this;
        
        if (!Ext.WindowManager.get('winSoftEtherWireguardPublicKeyAddId')) {
            var w = Ext.widget('winSoftEtherWireguardPublicKeyAdd', {
                id: 'winSoftEtherWireguardPublicKeyAddId'
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
            url: me.getUrlAddKey(),
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
                        url: me.getUrlDeleteKey(),
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
});
