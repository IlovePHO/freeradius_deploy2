Ext.define('Rd.controller.cProxies', {
    extend: 'Ext.app.Controller',
    owner: undefined,
    user_id: undefined,
    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridProxies',
            padding: Rd.config.gridPadding,
            border: false,
            plain: true
        });
        
        pnl.on({activate : me.gridActivate,scope: me});
        me.populated = true;

    },
    views: [
        'proxies.gridProxies',
        'proxies.winProxieAddWizard',
        'proxies.pnlProxieAddEdit',
        'components.winSelectOwner'
    ],
    stores: [
        'sAccessProvidersTree',
        'sProxies',
    ],
    models: [
        'mAccessProviderTree',
        'mProxie',
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck : '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd   : '/cake3/rd_cake/proxies/add.json',
        urlDelete: '/cake3/rd_cake/proxies/delete.json',
        urlEdit  : '/cake3/rd_cake/proxies/edit.json',
        urlView  : '/cake3/rd_cake/proxies/view.json',
    },
    refs: [{
        ref: 'grid',
        selector: 'gridProxies'
    }, ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            '#tabProxies'    : {
                destroy: me.appClose
            },
            'gridProxies #reload': {
                click: me.reload
            },
            'gridProxies #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            }, 
            'gridProxies #add': {
                click: me.add
            },
            'gridProxies #edit'   : {
                click: me.edit
            },
            'gridProxies #delete': {
                click: me.del
            },
            'gridProxies #home_server_pools'  : {
                click:      me.homeServerPools
            },
            'winProxieAddWizard #btnTreeNext' : {
                click:  me.btnTreeNext
            },
            'winProxieAddWizard #btnDataPrev' : {
                click:  me.btnDataPrev
            },
            'winProxieAddWizard #btnDataNext' : {
                click:  me.btnDataNext
            },
            'pnlProxieAddEdit #save': {
                click: me.btnEditSave
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

    select: function (grid, record) {
        var me = this;
        //Adjust the Edit and Delete buttons accordingly...

        //Dynamically update the top toolbar
        var tb = me.getGrid().down('toolbar[dock=top]');

        var edit = record.get('update');
        if(edit == true){
            if(tb.down('#edit') != null){
                tb.down('#edit').setDisabled(false);
            }
        }else{
            if(tb.down('#edit') != null){
                tb.down('#edit').setDisabled(true);
            }
        }

        var del = record.get('delete');
        if(del == true){
            if(tb.down('#delete') != null){
                tb.down('#delete').setDisabled(false);
            }
        }else{
            if(tb.down('#delete') != null){
                tb.down('#delete').setDisabled(true);
            }
        }
    },
    add: function (button) {
        var me = this;
        Ext.Ajax.request({
            url: me.getUrlApChildCheck(),
            method: 'GET',
            success: function(response){
                var jsonData    = Ext.JSON.decode(response.responseText);
                if(jsonData.success){
                        
                    if(jsonData.items.tree == true){
                        if(!Ext.WindowManager.get('winProxieAddWizardId')){
                            var w = Ext.widget('winProxieAddWizard',{id:'winProxieAddWizardId'});
                            w.show();
                        }
                    }else{
                        if(!Ext.WindowManager.get('winProxieAddWizardId')){
                            var w = Ext.widget('winProxieAddWizard',
                                {id:'winProxieAddWizardId',startScreen: 'scrnData',user_id:'0',owner: i18n('sLogged_in_user'), no_tree: true}
                            );
                            w.show();
                        }
                    }
                }
            },
            scope: me
        });
    },
    btnTreeNext: function(button){
        var me = this;
        var tree = button.up('treepanel');
        //Get selection:
        var sr = tree.getSelectionModel().getLastSelected();
        if(sr){    
            var win = button.up('winProxieAddWizard');
            win.down('#owner').setValue(sr.get('username'));
            win.down('#user_id').setValue(sr.getId());
            win.getLayout().setActiveItem('scrnData');
        }else{
            Ext.ux.Toaster.msg(
                        i18n('sSelect_an_owner'),
                        i18n('sFirst_select_an_Access_Provider_who_will_be_the_owner'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
            );
        }
    },
    btnDataPrev:  function(button){
        var me      = this;
        var win     = button.up('winProxieAddWizard');
        win.getLayout().setActiveItem('scrnApTree');
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
                me.getStore('sProxies').load();
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
                var proxy_id      = sr.getId();
                var proxy_tab_id  = 'proxyTab_'+proxy_id;
                var nt          = tp.down('#'+proxy_tab_id);
                if(nt){
                    tp.setActiveTab(proxy_tab_id); //Set focus on  Tab
                    return;
                }

                var proxy_tab_name = sr.get('name');
                //Tab not there - add one
                tp.add({ 
                    title       : proxy_tab_name,
                    itemId      : proxy_tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnEdit,
                    layout      : 'fit', 
                    items       : {'xtype' : 'pnlProxieAddEdit',proxy_id: proxy_id, proxy_name: proxy_tab_name}
                });
                tp.setActiveTab(proxy_tab_id); //Set focus on Add Tab*/
            });
        }
    },
	btnEditSave:  function(button){
        var me      = this;
        var form    = button.up("pnlProxieAddEdit");
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
    homeServerPools: function(b){
        var me = this;
        var tp = b.up('tabpanel');
        me.application.runAction('cHomeServerPools','Index',tp);
    },

});
