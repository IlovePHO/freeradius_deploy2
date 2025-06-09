Ext.define('Rd.controller.cHomeServerPools', {
    extend: 'Ext.app.Controller',
    actionIndex: function(tp){

        var me = this;
        if (me.populated) {
            return; 
        }
        //Existing tab check Dynamic addition determination
        var tab = tp.items.findBy(function (tab){
            return tab.getXType() === 'gridHomeServerPools';
        });
        if (!tab){
            tab = tp.add({
                xtype   : 'gridHomeServerPools',
                itemId  : 'tabHomeServerPools',
                padding : Rd.config.gridPadding,
                border  : false,
                glyph   : Rd.config.icnComponent,
                title   : i18n('sHome_server_pool'),
                plain	: true,
                closable: true, 
                tabConfig: {
                    ui: Rd.config.tabDevices
                }
            });
            tab.on({activate : me.gridActivate,scope: me});
        }else{
            tp.add({
                xtype   : 'gridHomeServerPools',
                padding : Rd.config.gridPadding,
                border  : false,
                plain	: true
            });      
            tab.on({activate : me.gridActivate,scope: me});
        }
        tp.setActiveTab(tab);
        me.populated = true;
    },

    views:  [
        'homeServerPools.gridHomeServerPools',
        'homeServerPools.winHomeServerPoolAddWizard',
        'homeServerPools.pnlHomeServerPoolAddEdit',
        'homeServerPools.gridHomeServers',
        'components.winSelectOwner'
    ],
    stores: [
        'sAccessProvidersTree',
        'sHomeServerPools',
    ],
    models: [
        'mAccessProviderTree',
        'mHomeServerPool',
        //'mHomeServer'
    ],
    selectedRecord: null,
    config: {
        urlApChildCheck : '/cake3/rd_cake/access-providers/child-check.json',
        urlAdd          : '/cake3/rd_cake/home-server-pools/add.json',
        urlDelete       : '/cake3/rd_cake/home-server-pools/delete.json',
        urlEdit         : '/cake3/rd_cake/home-server-pools/edit.json',
        urlView         : '/cake3/rd_cake/home-server-pools/view.json',
        //HomeServers
        urlHsAdd        : '/cake3/rd_cake/home-servers/add.json',
        urlHsDelete     : '/cake3/rd_cake/home-servers/delete.json',
        urlHsEdit       : '/cake3/rd_cake/home-servers/edit.json',
        urlHsView       : '/cake3/rd_cake/home-servers/view.json',
    },
    refs: [
        {  ref: 'grid',  selector: 'gridHomeServerPools' }
    ],
    init: function() {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            '#tabHomeServerPools'    : {
                destroy   :      me.appClose
            },
            'gridHomeServerPools #reload': {
                click:      me.reload
            }, 
            'gridHomeServerPools #add'   : {
                click:      me.add
            },
            'gridHomeServerPools #delete'	: {
                click:      me.del
            },
            'gridHomeServerPools #edit'   : {
                click:      me.edit
            },
            'gridHomeServerPools #home_servers'  : {
                click:      me.home_servers
            },
            'gridHomeServerPools'   : {
                select:      me.select
            },
			'winHomeServerPoolAddWizard #btnTreeNext' : {
                click:  me.btnTreeNext
            },
            'winHomeServerPoolAddWizard #btnDataPrev' : {
                click:  me.btnDataPrev
            },
            'winHomeServerPoolAddWizard #btnDataNext' : {
                click:  me.btnDataNext
            },
			'pnlHomeServerPoolAddEdit #save': {
                click: me.btnEditSave
            },
            //HomeServers
            'gridHomeServers #reload': {
                click: me.HsReload
            }, 
            'gridHomeServers #reload menuitem[group=refresh]': {
                click: me.HsReloadOptionClick
            }, 
            'gridHomeServers #add'   : {
                click: me.HsAdd
            },
            'gridHomeServers #delete'	: {
                click: me.HsDel
            },
            'gridHomeServers #edit'   : {
                click: me.HsEdit
            },
            'winHomeServerAddWizard #btnDataNext' : {
                click:  me.btnHsDataNext
            },
            'winHomeServerAddWizard #type':{
                change: me.cmbProtocolChange
            },
            'pnlHomeServerAddEdit #save': {
                click: me.btnHsEditSave
            },
            'pnlHomeServerAddEdit  #type':{
                change: me.cmbProtocolChange
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
	reload: function(){
        var me =this;
        me.getGrid().getSelectionModel().deselectAll(true);
        me.getGrid().getStore().load();
    },
    add: function(button){
        
        var me = this;
        //We need to do a check to determine if this user (be it admin or acess provider has the ability to add to children)
        //admin/root will always have, an AP must be checked if it is the parent to some sub-providers. If not we will 
        //simply show the nas connection typer selection 
        //if it does have, we will show the tree to select an access provider.
        Ext.Ajax.request({
            url: me.getUrlApChildCheck(),
            method: 'GET',
            success: function(response){
                var jsonData    = Ext.JSON.decode(response.responseText);
                if(jsonData.success){
                        
                    if(jsonData.items.tree == true){
                        if(!Ext.WindowManager.get('winHomeServerPoolAddWizardId')){
                            var w = Ext.widget('winHomeServerPoolAddWizard',{id:'winHomeServerPoolAddWizardId'});
                            w.show();
                        }
                    }else{
                        if(!Ext.WindowManager.get('winHomeServerPoolAddWizardId')){
                            var w = Ext.widget('winHomeServerPoolAddWizard',
                                {id:'winHomeServerPoolAddWizardId',startScreen: 'scrnData',user_id:'0',owner: i18n('sLogged_in_user'), no_tree: true}
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
            var win = button.up('winHomeServerPoolAddWizard');
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
        var win     = button.up('winHomeServerPoolAddWizard');
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
                me.getStore('sHomeServerPools').load();
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
    select: function(dv,record,eOpt){
        var me = this;
        //Adjust the Edit and Delete buttons accordingly...
        //Dynamically update the top toolbar
        var grid = dv.view.up('grid');
        var tb = grid.down('toolbar[dock=top]');

        var edit = record.get('update');
        if(edit == true){
            if(tb.down('#edit') != null){
                tb.down('#edit').setDisabled(false);
            }
            if(tb.down('#home_servers') != null){
                tb.down('#home_servers').setDisabled(false);
            }
        }else{
            if(tb.down('#edit') != null){
                tb.down('#edit').setDisabled(true);
            }
            if(tb.down('#home_servers') != null){
                tb.down('#home_servers').setDisabled(true);
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
    del:   function(){
        var me      = this;     
        //Find out if there was something selected
        if(me.getGrid().getSelectionModel().getCount() == 0){
             Ext.ux.Toaster.msg(
                        i18n('sSelect_an_item'),
                        i18n('sFirst_select_an_item_to_delete'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
            );
        }else{
            Ext.MessageBox.confirm(i18n('sConfirm'), i18n('sAre_you_sure_you_want_to_do_that_qm'), function(val){
                if(val== 'yes'){
                    var selected    = me.getGrid().getSelectionModel().getSelection();
                    var list        = [];
                    Ext.Array.forEach(selected,function(item){
                        var id = item.getId();
                        Ext.Array.push(list,{'id' : id});
                    });
                    Ext.Ajax.request({
                        url: me.getUrlDelete(),
                        method: 'POST',          
                        jsonData: list,
                        success: function(batch,options){console.log('success');
                            Ext.ux.Toaster.msg(
                                i18n('sItem_deleted'),
                                i18n('sItem_deleted_fine'),
                                Ext.ux.Constants.clsInfo,
                                Ext.ux.Constants.msgInfo
                            );
                            me.reload(); //Reload from server
                        },                                    
                        failure: function(batch,options){
                            console.log("Could not delete!");
                            me.reload(); //Reload from server
                        }
                    });
                }
            });
        }
    },
    edit:   function(){
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
                var hsp_id      = sr.getId();
                var hsp_tab_id  = 'hspTab_'+hsp_id;
                var nt          = tp.down('#'+hsp_tab_id);
                if(nt){
                    tp.setActiveTab(hsp_tab_id); //Set focus on  Tab
                    return;
                }

                var hsp_tab_name = sr.get('name');
                //Tab not there - add one
                tp.add({ 
                    title       : hsp_tab_name,
                    itemId      : hsp_tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnEdit,
                    layout      : 'fit', 
                    items       : {'xtype' : 'pnlHomeServerPoolAddEdit',hsp_id: hsp_id, hsp_name: hsp_tab_name, record: sr}
                });
                tp.setActiveTab(hsp_tab_id); //Set focus on Add Tab*/
            });
        }
    },
	btnEditSave:  function(button){
        var me      = this;
        var form    = button.up("pnlHomeServerPoolAddEdit");
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
    home_servers: function(){
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
                var tp          = me.getGrid().up('tabpanel');
                var hsp_id      = sr.getId();
                var hsp_name    = sr.get('name');
                var hsg_tab_id  = 'hsgTab_'+hsp_id;
                var nt          = tp.down('#'+hsg_tab_id);
                if(nt){
                    tp.setActiveTab(hsg_tab_id); //Set focus on  Tab
                    return;
                }

                var hsg_tab_name = i18n('sHome_server')+'('+sr.get('name')+')';
                //Tab not there - add one
                tp.add({ 
                    title       : hsg_tab_name,
                    itemId      : hsg_tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnDotCircleO,
                    layout      : 'fit',
                    padding: Rd.config.gridPadding,
                    items       : {'xtype' : 'gridHomeServers', hsp_id: hsp_id, hsp_name: hsp_name }
                });
                tp.setActiveTab(hsg_tab_id); //Set focus on Add Tab*/
        });
        }
    },
    appClose:   function(){
        var me          = this;
        me.populated    = false;
    },
    
    
      //HomeServers
    HsReload: function (b) {
        //var me = this;
        var g = b.up('gridHomeServers');
        g.reload();
        //console.log(b,g);
        //.getSelectionModel().deselectAll(true);
        //g.getStore().load();
    },
    HsReloadOptionClick: function(menu_item){
        //var me      = this;
        var n       = menu_item.getItemId();
        var b       = menu_item.up('button'); 
        var g = b.up('gridHomeServers');
        var interval= 30000; //default
        
        clearInterval(g.autoReload);   //Always clear
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
        g.autoReload = setInterval(function(){
            g.reload();
        },  interval);
    },
    cmbProtocolChange:function(cmb){
        var me = this;
        var type = cmb.getValue();
        var form = cmb.up('form');
        var portNo = form.down('#portNo');
        var portValue ={
            'auth+acct':1812,
            'auth':1812,
            'acct':1813
        };
        if(portNo && portValue[type]){
            portNo.setValue(portValue[type]);
        }
    },
    HsAdd: function (b) {
        //var me = this;
        var g = b.up('gridHomeServers');
        if(!Ext.WindowManager.get('winHomeServerAddWizardId')){
            var w = Ext.widget('winHomeServerAddWizard',{id:'winHomeServerAddWizardId', hsp_id:g.hsp_id, grid_id:g.getItemId() });
            w.show();
        }
    },
    btnHsDataNext:  function(button){
        var me   = this;
        var win  = button.up('window');
        var form = win.down('form');
        var tp   = me.getGrid().up('tabpanel');
        var grid = tp.down('#'+win.grid_id);
        form.submit({
            clientValidation: true,
            url: me.getUrlHsAdd(),
            success: function(form, action) {
                win.close();
                grid.reload();
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
    HsDel: function (b) {
        var me = this;
        var g = b.up('gridHomeServers');
        
        //Find out if there was something selected
        if(g.getSelectionModel().getCount() == 0){
             Ext.ux.Toaster.msg(
                        i18n('sSelect_an_item'),
                        i18n('sFirst_select_an_item_to_delete'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
            );
        }else{
            Ext.MessageBox.confirm(i18n('sConfirm'), i18n('sAre_you_sure_you_want_to_do_that_qm'), function(val){
                if(val== 'yes'){
                    var selected    = g.getSelectionModel().getSelection();
                    var list        = [];
                    Ext.Array.forEach(selected,function(item){
                        var id = item.getId();
                        Ext.Array.push(list,{'id' : id});
                    });
                    Ext.Ajax.request({
                        url: me.getUrlHsDelete(),
                        method: 'POST',
                        jsonData: list,
                        success: function(batch,options){console.log('success');
                            Ext.ux.Toaster.msg(
                                i18n('sItem_deleted'),
                                i18n('sItem_deleted_fine'),
                                Ext.ux.Constants.clsInfo,
                                Ext.ux.Constants.msgInfo
                            );
                            g.reload(); //Reload from server
                        }, 
                        failure: function(batch,options){
                            console.log("Could not delete!");
                            g.reload(); //Reload from server
                        }
                    });
                }
            });
        }
        
    },
    HsEdit: function (b) {
        var grid = b.up('gridHomeServers');
        var me = this;
        //See if there are anything selected... if not, inform the user
        var sel_count = grid.getSelectionModel().getCount();
        if(sel_count == 0){
            Ext.ux.Toaster.msg(
                        i18n('sSelect_an_item'),
                        i18n('sFirst_select_an_item'),
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
            );
        }else{
            var selected    = grid.getSelectionModel().getSelection();
            var count       = selected.length;         
            Ext.each(grid.getSelectionModel().getSelection(), function(sr,index){
                //Check if the node is not already open; else open the node:
                var tp          = grid.up('tabpanel');
                var hsp_id      = grid.hsp_id;
                var hsv_id      = sr.getId();
                var hsv_tab_id  = 'hsvTab_'+hsv_id;
                var nt          = tp.down('#'+hsv_tab_id);
                if(nt){
                    tp.setActiveTab(hsv_tab_id); //Set focus on  Tab
                    return;
                }
                var hsv_tab_name = sr.get('name');
                //Tab not there - add one
                tp.add({ 
                    title       : hsv_tab_name,
                    itemId      : hsv_tab_id,
                    closable    : true,
                    glyph       : Rd.config.icnEdit,
                    layout      : 'fit', 
                    items       : {'xtype' : 'pnlHomeServerAddEdit', hsv_id: hsv_id, grid_id:grid.getItemId() }
                });
                tp.setActiveTab(hsv_tab_id); //Set focus on Add Tab
            });
        }
    },
    btnHsEditSave: function(button){
        var me      = this;
        var form    = button.up("pnlHomeServerAddEdit");
        var tab     = form.up('panel');
        var tp      = tab.up('tabpanel');
        var grid = tp.down('#'+form.grid_id);
        form.submit({
            clientValidation: true,
            url: me.getUrlHsEdit(),
            success: function(form, action) {
                tab.close();
                grid.reload(); //Reload from server
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
