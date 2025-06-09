Ext.define('Rd.controller.cIdps', {
    extend: 'Ext.app.Controller',

    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridIdps',
            padding: Rd.config.gridPadding,
            border: false,
            plain: true
        });
        
        pnl.on({activate : me.gridActivate,scope: me});
        me.populated = true;

    },
    views: [
        'idps.gridIdps',
        'idps.winIdpAddWizard',
        'idps.pnlIdpEdit',
        'components.cmbRealm',
        'components.winSelectOwner'
    ],
    stores: [
        'sAccessProvidersTree',
        'sIdps',
        'sIdpsTypes',
        'sIdpsAuthTypes',
    ],
    models: [
        'mAccessProviderTree',
        'mIdps',
    ],
    selectedRecord: null,
    syncDat: null,
    authWin: null,
    authTimer: null,
    config: {
        urlApChildCheck : '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd   : '/cake3/rd_cake/idps/add.json',
        urlDelete: '/cake3/rd_cake/idps/delete.json',
        urlEdit  : '/cake3/rd_cake/idps/edit.json',
        urlView  : '/cake3/rd_cake/idps/view.json',
        urlPrepare  : '/cake3/rd_cake/google-ws-links/prepare.json',
        urlSync  : '/cake3/rd_cake/google-ws-links/synchronize.json',
    },
    refs: [{
        ref: 'grid',
        selector: 'gridIdps'
    }, ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            'gridIdps #reload': {
                click: me.reload
            },
            'gridIdps #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            }, 
            'gridIdps #add': {
                click: me.add
            },
            'gridIdps #edit'   : {
                click: me.edit
            },
            'gridIdps #delete': {
                click: me.del
            },
            'gridIdps #synchronize': {
                click: me.sync
            },
            'gridIdps #subGroups': {
                click: me.subGroups
            },
            'gridIdps actioncolumn': { 
                 itemClick  : me.onActionColumnItemClick
            },
            'gridIdps'   : {
                menuItemClick   : me.onActionColumnMenuItemClick 
            },
            'winIdpAddWizard #btnTreeNext' : {
                click:  me.btnTreeNext
            },
            'winIdpAddWizard #btnDataPrev' : {
                click:  me.btnDataPrev
            },
            'winIdpAddWizard #btnDataNext' : {
                click:  me.btnDataNext
            },
            'pnlIdpEdit #save': {
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
    appClose:   function(){
        var me          = this;
        me.populated    = false;
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
    onActionColumnItemClick: function(view, rowIndex, colIndex, item, e, record, row, action){
        var me = this;
        var grid = view.up('grid');
        grid.setSelection(record);
        if(action == 'update'){
            me.edit(); 
        }
        if(action == 'delete'){
            me.del(); 
        }
    },
    onActionColumnMenuItemClick: function(grid,action){
        var me = this;
        grid.setSelection(grid.selRecord);
        if(action == 'synchronize'){
            me.sync(); 
        }
        if(action == 'subGroups'){
            me.subGroups(); 
        }
    },
    add: function(button){
        var me = this;
        //We need to do a check to determine if this user (be it admin or acess provider has the ability to add to children)
        //admin/root will always have, an AP must be checked if it is the parent to some sub-providers. If not we will simply show the add window
        //if it does have, we will show the add wizard.

        Ext.Ajax.request({
            url: me.getUrlApChildCheck(),
            method: 'GET',
            success: function(response){
                var jsonData    = Ext.JSON.decode(response.responseText);
                if(jsonData.success){
                    if(jsonData.items.tree == true){
                        if(!Ext.WindowManager.get('winIdpAddWizardId')){
                            var w = Ext.widget('winIdpAddWizard',
                            {
                                id          :'winIdpAddWizardId'
                            });
                            w.show();
                        }
                    }else{
                        if(!Ext.WindowManager.get('winIdpAddWizardId')){
                            var w   = Ext.widget('winIdpAddWizard',
                            {
                                id          : 'winIdpAddWizardId',
                                startScreen : 'scrnData',
                                user_id     : '0',
                                owner       : i18n('sLogged_in_user'),
                                no_tree     : true
                            });
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
            var win = button.up('winIdpAddWizard');
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
        var win     = button.up('winIdpAddWizard');
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
    
    edit: function(button){
        var me = this;
        var grid    = me.getGrid();  
        //Find out if there was something selected
        if(me.getGrid().getSelectionModel().getCount() == 0){
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
                var tp      = me.getGrid().up('tabpanel');
                var id      = sr.getId();
                var tab_id  = 'idpTab_'+id;
                var nt      = tp.down('#'+tab_id);
                if(nt){
                    tp.setActiveTab(tab_id); //Set focus on  Tab
                    return;
                }

                //var tab_name = me.selectedRecord.get('name');
                var tab_name = sr.get('name');
                //Tab not there - add one
                tp.add({ 
                    title       : tab_name,
                    itemId      : tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnEdit, 
                    xtype       : 'pnlIdpEdit',
                    idp_id      : id,
                    tabConfig : {
                        ui : me.ui
                    }
                });
                tp.setActiveTab(tab_id); //Set focus on Add Tab
            });
        }
    },
    btnEditSave: function(button){
        var me      = this;
        var form    = button.up('form');
        var tab     = button.up('pnlIdpEdit'); 
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
    
    sync: function(button){
        var me = this;
        var sel_count = me.getGrid().getSelectionModel().getCount();
        if(sel_count == 0){
             Ext.ux.Toaster.msg(
                        i18n('sSelect_an_item'),
                        i18n('sFirst_select_an_item'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
            );
        }else{
            if(sel_count > 1){
                Ext.ux.Toaster.msg(
                        i18n('sLimit_the_selection'),
                        i18n('sSelection_limited_to_one'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
                );
            }else{
                //Determine the selected record:
                var selected = me.getGrid().getSelectionModel().getLastSelected();
                var params ={
                    idp_id: selected.getId(),
                    realm_id: selected.get('realm_id'),
                };
                me.syncDat = {sync_target:params};
                // 認証 要/不要判定
                me.syncPrepare(params,null);
                
            }
        }
    },
    syncPrepare: function(target,state){
        var me = this;
        var params={
            idp_id:target.idp_id,
            realm_id:target.realm_id
        };
        if( state ){
            params.state=state;
        }
        Ext.Ajax.request({
            url: me.getUrlPrepare(),
            /*method: 'POST',
            jsonData: params,*/
            method: 'GET',
            params: params,
            success: function(response){
            var jsonData    = Ext.JSON.decode(response.responseText);
                // console.log( 'Prepare', jsonData);
                if(jsonData.success){
                    if(jsonData.complete==true){
                        me.authWinClose();
                        //同期実行
                        me.synchronize(target);
                        return;
                    }else{
                        if( !state ){//初回
                           //再チェック中ではない 確認後、認証winを開く
                            Ext.MessageBox.confirm(i18n('sConfirm'), 
                                i18n('sOAuth_required_Open_an_authentication_window'),
                                function (val) {
                                    if (val == 'yes') {
                                        me.syncDat.auth = {auth_uri:jsonData.auth_uri,state:jsonData.state};
                                        me.authWinOpen(target,jsonData.auth_uri,jsonData.state);
                                    }else{
                                        //認証 Cancel!
                                        me.authWinClose();
                                        me.syncDat=null;
                                    }
                                }
                            );
                        }
                    }
                }else{
                    //Server error
                    var message = jsonData.message? ( jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
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
    authWinOpen: function(target,auth_uri, state){
        var me = this;
        //認証用別Windowを開く
        if( me.authWin && !me.authWin.closed){
            if( state == me.syncDat.auth.state ){
                //継続中
                me.authWin.focus();
                return;
            }
        }
        me.authWinClose();
        var win = window.open(auth_uri,'authWin');
        console.log('authWin open!',auth_uri);
        /*下記の検出方法はNG! window の"unload"イベントは、外部サーバへのaccessのため、クロスオリジンエラーになる
        win.addEventListener("unload", function(event){
            console.log('authWin unload! ',win.closed,win.location.href);
            if(!win.closed) return; //継続中
            me.syncPrepare(target,state);
            me.authWinClose();
        });*/
        
        // 認証Windowのcosecheck Timer
        me.authTimer= setInterval( function(){
            if(!win.closed) return; //継続中
            
            me.authWinClose();// stop Timer
            me.syncPrepare(target, state);
        },500);
        win.focus();
        me.authWin = win;

    },
    authWinClose: function(){
        //認証用Windowの終了 intervalタイマ使うなら、タイマーの終了
        var me = this;
        if( me.authWin ){
            if(!me.authWin.closed){
                me.authWin.close();
            }
            me.authWin=null;
        }
        if( me.authTimer ){
            clearInterval(me.authTimer);
            me.authTimer=null;
        }
    },
    synchronize: function(params){
        var me = this;
        //同期実行
        Ext.Ajax.request({
            url: me.getUrlSync(),
            /*method: 'POST',
            jsonData: params,*/
            method: 'GET',
            params: params,
            success: function(response){
                    var jsonData    = Ext.JSON.decode(response.responseText);
                    if(jsonData.success){
                        console.log('synchronize success');
                        Ext.ux.Toaster.msg(
                            i18n('sItem_updated'),
                            i18n('sItem_updated_fine'),
                            Ext.ux.Constants.clsInfo,
                            Ext.ux.Constants.msgInfo
                        );
                    }else{
                        //Server error
                        var message = jsonData.message? ( jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
                        Ext.ux.Toaster.msg(
                            i18n('sProblems_updating_the_item'),
                            message,
                            Ext.ux.Constants.clsWarn,
                            Ext.ux.Constants.msgWarn
                        );
                    }
                    me.reload(); //Reload from server
            },
            failure: Ext.ux.ajaxFail
        });
    },
    subGroups: function(button){
        var me = this;
        var grid    = me.getGrid();  
        //Find out if there was something selected
        if(me.getGrid().getSelectionModel().getCount() == 0){
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
                var tp      = me.getGrid().up('tabpanel');
                var id      = sr.getId();
                var name = sr.get('name');
                var realm_id = sr.get('realm_id');
                me.application.runAction('cSubGroups','Index',tp,{name:name,idp_id:id, realm_id:realm_id});
            });
        }
    },
});
