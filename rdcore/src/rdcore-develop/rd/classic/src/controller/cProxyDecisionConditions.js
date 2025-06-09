Ext.define('Rd.controller.cProxyDecisionConditions', {
    extend: 'Ext.app.Controller',
    owner: undefined,
    user_id: undefined,
    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridProxyDecisionConditions',
            padding: Rd.config.gridPadding,
            border: false,
            plain: true
        });
        
        pnl.on({activate : me.gridActivate,scope: me});
        me.populated = true;

    },
    views: [
        'proxyDecisionConditions.gridProxyDecisionConditions',
        'proxyDecisionConditions.winProxyDecisionConditionAddWizard',
        'proxyDecisionConditions.pnlProxyDecisionConditionAddEdit',
    ],
    stores: [
        'sProxyDecisionConditions',
        //'sProxies',
    ],
    models: [
        'mProxyDecisionCondition',
        //'mProxie',
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck : '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd   : '/cake3/rd_cake/proxy-decision-conditions/add.json',
        urlDelete: '/cake3/rd_cake/proxy-decision-conditions/delete.json',
        urlEdit  : '/cake3/rd_cake/proxy-decision-conditions/edit.json',
        urlView  : '/cake3/rd_cake/proxy-decision-conditions/view.json',
    },
    refs: [{
        ref: 'grid',
        selector: 'gridProxyDecisionConditions'
    }, ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            '#tabProxyDecisionConditions'    : {
                destroy: me.appClose
            },
            'gridProxyDecisionConditions #reload': {
                click: me.reload
            },
            'gridPermanentUsers #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            }, 
            'gridProxyDecisionConditions #add': {
                click: me.add
            },
            'gridProxyDecisionConditions #edit'   : {
                click: me.edit
            },
            'gridProxyDecisionConditions #delete': {
                click: me.del
            },
            'winProxyDecisionConditionAddWizard #btnDataNext' : {
                click:  me.btnDataNext
            },
            'pnlProxyDecisionConditionAddEdit #save': {
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

    reload: function () {
        var me = this;
        me.getGrid().getSelectionModel().deselectAll(true);
        me.getGrid().getStore().load();
    },
    reloadOptionClick: function(menu_item){
        var me      = this;
        var n       = menu_item.getItemId();
        var b       = menu_item.up('button'); 
        var interval= 30000; //default
        clearInterval(me.autoReload);   //Always clear
        b.setGlyph(Rd.config.icnTime);

        if(n == 'mnuRefreshCancel'){
            b.setGlyph(Rd.config.icnReload);
            return;
        }
        
        if(n == 'mnuRefresh1m'){
           interval = 60000
        }

        if(n == 'mnuRefresh5m'){
           interval = 360000
        }
        me.autoReload = setInterval(function(){
            me.reload();
        },  interval);  
    },

    add: function (button) {
        var me = this;
        
        if(!Ext.WindowManager.get('winProxyDecisionConditionAddWizardId')){
            var w = Ext.widget('winProxyDecisionConditionAddWizard',{id:'winProxyDecisionConditionAddWizardId' });
            w.show();
        }
        
    },

    btnDataNext:  function(button){
        var me      = this;
        var win     = button.up('window');
        var form    = win.down('form');
        form.submit({
            clientValidation: true,
            url: me.getUrlAdd(),
            success: function(form, action) {
                win.close();
                me.getStore('sProxyDecisionConditions').load();
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
    edit: function (button) {
        var me = this;
        var me = this;
        //See if there are anything selected... if not, inform the user
        var sel_count = me.getGrid().getSelectionModel().getCount();
        if(sel_count == 0){
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_item'),
                i18n('sFirst_select_an_item'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        }else{
            var selected    =  me.getGrid().getSelectionModel().getSelection();
            var count       = selected.length;

            Ext.each(me.getGrid().getSelectionModel().getSelection(), function(sr,index){
                //Check if the node is not already open; else open the node:
                var tp          = me.getGrid().up('tabpanel');
                var proxyDC_id      = sr.getId();
                var proxyDc_tab_id  = 'proxyDcTab_'+proxyDC_id;
                var nt          = tp.down('#'+proxyDc_tab_id);
                if(nt){
                    tp.setActiveTab(proxyDc_tab_id); //Set focus on  Tab
                    return;
                }

                var proxyDc_tab_name = sr.get('ssid');
                //Tab not there - add one
                tp.add({ 
                    title       : proxyDc_tab_name,
                    itemId      : proxyDc_tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnEdit,
                    layout      : 'fit', 
                    items       : {'xtype' : 'pnlProxyDecisionConditionAddEdit', proxyDC_id: proxyDC_id}
                });
                tp.setActiveTab(proxyDc_tab_id); //Set focus on Add Tab
            });
        }
    },
	btnEditSave:  function(button){
        var me      = this;
        
        var form    = button.up("pnlProxyDecisionConditionAddEdit");
        var tab     = form.up('panel');
        
        form.submit({
            clientValidation: true,
            url: me.getUrlEdit(),
            success: function(form, action) {
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
    del: function () {
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
                                console.log('success');
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
        }
    },
    
});
