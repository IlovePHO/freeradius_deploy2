Ext.define('Rd.controller.cStaInfos', {
    extend: 'Ext.app.Controller',

    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridStaInfos',
            padding: Rd.config.gridPadding,
            border: false,
            plain: true
        });
        
        pnl.on({activate : me.gridActivate,scope: me});
        me.populated = true;

    },
    views: [
        'staInfos.gridStaInfos',
    ],
    stores: [
        'sStaInfos',
    ],
    models: [
        'mStaInfo',
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck : '/cake3/rd_cake/access-providers/child-check.json',
        urlDelete: '/cake3/rd_cake/sta-infos/delete.json',
    },
    refs: [
        { ref: 'grid',  selector: 'gridStaInfos' }, ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            'gridStaInfos #reload': {
                click: me.reload
            },
            'gridStaInfos #delete': {
                click: me.del
            },
            'gridStaInfos actioncolumn': { 
                 itemClick  : me.onActionColumnItemClick
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
    appClose:   function(){
        var me          = this;
        me.populated    = false;
    },
    reload: function () {
        var me = this;
        me.getGrid().getSelectionModel().deselectAll(true);
        me.getGrid().getStore().load();
    },
    onActionColumnItemClick: function(view, rowIndex, colIndex, item, e, record, row, action){
        var me = this;
        var grid = view.up('grid');
        grid.setSelection(record);
        if(action == 'delete'){
            me.del(); 
        }
    },
    

    del: function(button){
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
                        success: function(response){
                            var jsonData    = Ext.JSON.decode(response.responseText);
                            if(jsonData.success){
                                //console.log('success');
                                Ext.ux.Toaster.msg(
                                    i18n('sItem_deleted'),
                                    i18n('sItem_deleted_fine'),
                                    Ext.ux.Constants.clsInfo,
                                    Ext.ux.Constants.msgInfo
                                );
                            }else{
                                //Server error
                                var message = jsonData.message? ( jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
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
    }},
});
