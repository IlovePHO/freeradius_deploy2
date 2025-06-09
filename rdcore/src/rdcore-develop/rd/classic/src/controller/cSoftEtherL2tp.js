Ext.define('Rd.controller.cSoftEtherL2tp', {
    extend: 'Ext.app.Controller',
    views: [
        'softEtherL2tp.pnlSoftEtherL2tp'
    ],
    config: {
        urlSave: '/cake3/rd_cake/soft-ether-l2tp/save.json',
    }, 
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        
        me.control({
            'pnlSoftEtherL2tp #save': {
                click: me.btnSave
            },
        });
        
    },
    actionIndex: function (pnl) {
        var me = this;
        if (me.populated) {
            return; 
        } 
        pnl.add({
            xtype   : 'pnlSoftEtherL2tp',
            border  : false,
            itemId  : 'tabSoftEtherL2tp',
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
    }
});
