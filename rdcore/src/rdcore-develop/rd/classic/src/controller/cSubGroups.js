Ext.define('Rd.controller.cSubGroups', {
    extend: 'Ext.app.Controller',
    views: [
        'subGroups.gridSubGroups',
        'subGroups.winSubGroupsAddWizard',
        'subGroups.pnlSubGroupsEdit',
        'components.winSelectOwner'
    ],
    stores: [
        'sAccessProvidersTree',
        'sSubGroups',
        'sProfiles',
    ],
    models: [
        'mAccessProviderTree',
        'mSubGroup',
    ],
    config: {
        urlApChildCheck : '/cake3/rd_cake/access-providers/child-check.json',
        urlApProfileAddApAction: '/cake3/rd_cake/ap-actions/add.json',
        urlAdd      : '/cake3/rd_cake/sub-groups/add.json',
        urlEdit     : '/cake3/rd_cake/sub-groups/edit.json',
        urlDelete   : '/cake3/rd_cake/sub-groups/delete.json',
        urlPrepare  : '/cake3/rd_cake/google-ws-links/prepare.json',
        urlSync     : '/cake3/rd_cake/google-ws-links/synchronize.json',
    },
    refs: [
        {  ref: 'grid',  selector: 'gridSubGroups' }
    ],

    selectedRecord: null,
    syncDat: null,
    authWin: null,
    authTimer: null,
    init: function () {
        var me = this;
        if (me.inited) {
            // console.log('cSubGroups inited: '+me.inited);
            return;
        }
        me.inited = true;
        me.control({
            'gridSubGroups #reload': {
                click: me.reload
            },
            'gridSubGroups #reload menuitem[group=refresh]': {
                click: me.reloadOptionClick
            }, 
            'gridSubGroups #add'   : {
                click: me.add
            },
            'gridSubGroups #edit'   : {
                click: me.edit
            },
            'gridSubGroups #delete': {
                click: me.del
            },
            'gridSubGroups #synchronize': {
                click: me.sync
            },
            'gridSubGroups actioncolumn': { 
                 itemClick  : me.onActionColumnItemClick
            },
            'gridSubGroups'   : {
                menuItemClick   : me.onActionColumnMenuItemClick 
            },
            'winSubGroupsAddWizard #btnTreeNext' : {
                click:  me.btnTreeNext
            },
            'winSubGroupsAddWizard #btnDataPrev' : {
                click:  me.btnDataPrev
            },
            'winSubGroupsAddWizard #btnDataNext' : {
                click:  me.btnDataNext
            },
            'pnlSubGroupsEdit #save': {
                click: me.btnEditSave
            }
        });
    },
    actionIndex: function (tp, paem) {
        var me = this;
        var tabid = 'tabSubGroups';
        var tabName = i18n('sOrganization')+'('+paem.name+')';
        if( paem.realm_id ){
            tabid += 'Realm'+ paem.realm_id;
        }
        if( paem.idp_id ){
            tabid += 'Idp'+ paem.idp_id;
        }
        
        var newTab = tp.items.findBy(function (tab) {
            return tab.getItemId() === tabid;
        });

        if (!newTab) {
            newTab = tp.add({
                title: tabName,
                closable: true,
                layout: 'fit',
                xtype: 'gridSubGroups',
                glyph: Rd.config.icnFolder,
                padding: Rd.config.gridPadding,
                itemId: tabid,
                realm_id : paem.realm_id,
                idp_id : paem.idp_id,
            });
            //console.log('Add '+tabid);
            newTab.on({activate : me.gridActivate, scope: me, destroy:me.appClose });
        }
        tp.setActiveTab(newTab);
        me.populated = true;
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
    appClose: function (tab) {
        //console.log('close '+tab.getItemId());
        if(tab.autoReload != undefined){
            clearInterval(tab.autoReload);
        }
    },
    reload: function (b) {
        var me = this;
        var grid =null;
        if( Ext.isString(b) && Ext.ComponentQuery.query('#'+b).length>0){
            grid = Ext.ComponentQuery.query('#'+b)[0];
        }else{
            grid = b.up('gridSubGroups');
        }
        if(grid){
            grid.reload();
        }
    },
    reloadOptionClick: function(menu_item){
        var me      = this;
        var n       = menu_item.getItemId();
        var b       = menu_item.up('button'); 
        var grid = menu_item.up('gridSubGroups');
        var interval= 30000; //default
        clearInterval(grid.autoReload);   //Always clear
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
        grid.autoReload = setInterval(function(){
            grid.reload();
        },  interval);
    },
    onActionColumnItemClick: function(view, rowIndex, colIndex, item, e, record, row, action){
        var me = this;
        var grid = view.up('grid');
        var button = grid.down('button');
        grid.setSelection(record);
        if(action == 'update'){
            me.edit(button); 
        }
        if(action == 'delete'){
            me.del(button); 
        }
    },
    onActionColumnMenuItemClick: function(grid,action){
        var me = this;
        grid.setSelection(grid.selRecord);
        if(action == 'synchronize'){
            var button = grid.down('#synchronize');
            me.sync(button); 
        }
       
    },
    add: function(button){
        var me       = this;

        Ext.Ajax.request({
            url: me.getUrlApChildCheck(),
            method: 'GET',
            success: function(response){
                var grid     = me.getGrid();

                var jsonData    = Ext.JSON.decode(response.responseText);
                if(jsonData.success){
                    if(jsonData.items.tree == true){
                        if(!Ext.WindowManager.get('winSubGroupsAddWizardId')){
                            var w = Ext.widget('winSubGroupsAddWizard',
                            {
                                id          :'winSubGroupsAddWizardId',
                                realm_id    : grid.realm_id,
                                idp_id      : grid.idp_id,
                            });
                            w.show();
                        }
                    }else{
                        if(!Ext.WindowManager.get('winSubGroupsAddWizardId')){
                            var w   = Ext.widget('winSubGroupsAddWizard',
                            {
                                id          : 'winSubGroupsAddWizardId',
                                startScreen : 'scrnData',
                                user_id     : '0',
                                owner       : i18n('sLogged_in_user'),
                                no_tree     : true,
                                realm_id    : grid.realm_id,
                                idp_id      : grid.idp_id,
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
            var win = button.up('winSubGroupsAddWizard');
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
        var win     = button.up('winSubGroupsAddWizard');
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
                grid = me.getGrid();
                grid.reload(); //Reload from server
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
        var grid = button.up('gridSubGroups');
        
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
                            me.reload(button); //Reload from server
                        },
                        failure: Ext.ux.ajaxFail
                    });
                }
            });
    }},
    edit: function(button){
        var me = this;
        var grid = button.up('gridSubGroups');
        //Find out if there was something selected
        if(grid.getSelectionModel().getCount() == 0){
             Ext.ux.Toaster.msg(
                        i18n('sSelect_an_item'),
                        i18n('sFirst_select_an_item'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
            );
        }else{
            var selected    = grid.getSelectionModel().getSelection();
            var count       = selected.length;
            Ext.each(selected, function(sr,index){
                //Check if the node is not already open; else open the node:
                var tp      = grid.up('tabpanel');
                var id      = sr.getId();
                var tab_id  = 'SubGroupTab_'+id;
                var nt      = tp.down('#'+tab_id);
                if(nt){
                    tp.setActiveTab(tab_id); //Set focus on  Tab
                    return;
                }

                //var tab_name = me.selectedRecord.get('name');
                var tab_name = sr.get('name');
                var this_grid = button.up('gridSubGroups').getItemId();
                //Tab not there - add one
                tp.add({ 
                    title       : tab_name,
                    itemId      : tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnEdit, 
                    xtype       : 'pnlSubGroupsEdit',
                    sg_id       : id,
                    from_grid   : this_grid,
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
        var tab     = button.up('pnlSubGroupsEdit'); 
        var tp =  tab.up('tabpanel');
        var from_tab = tp.down('#'+tab.from_grid);
        form.submit({
            clientValidation: true,
            url: me.getUrlEdit(),
            success: function(form, action) {
                if(from_tab){
                    from_tab.reload();
                }
                tab.close();
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
        //同期実施前check 対象1件、認証の必要判定
        var me = this;
        var grid = button.up('gridSubGroups');
        var sel_count = grid.getSelectionModel().getCount();
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
                var selected = grid.getSelectionModel().getLastSelected();
                var params ={
                    realm_id: selected.get('realm_id'),
                    idp_id: selected.get('idp_id'),
                    orgunit_id: selected.get('path'),
                };
                
                me.syncDat = {sync_target:params};
                // 認証 要/不要判定
                me.syncPrepare(params,null,grid.itemId);

            }
        }
    },
    syncPrepare: function(target,state,tab_id){
        var me = this;
        var params={
            idp_id:target.idp_id,
            realm_id:target.realm_id,
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
                        me.synchronize(target,tab_id);
                        return;
                    }else{
                        if( !state ){//初回
                           //再チェック中ではない 確認後、認証winを開く
                            Ext.MessageBox.confirm(i18n('sConfirm'), 
                                i18n('sOAuth_required_Open_an_authentication_window'),
                                function (val) {
                                    if (val == 'yes') {
                                        me.syncDat.auth = {auth_uri:jsonData.auth_uri,state:jsonData.state};
                                        me.authWinOpen(target,jsonData.auth_uri,jsonData.state,tab_id);
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
    authWinOpen: function(target,auth_uri, state, tab_id){
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
            me.syncPrepare(target, state, tab_id);
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
    synchronize: function(params,tab_id){
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
                    me.reload(tab_id); //Reload from server
            },
            failure: Ext.ux.ajaxFail
        });
    },
});
