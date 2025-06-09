Ext.define('Rd.view.homeServerPools.vcHomeServer', {
    extend  : 'Ext.app.ViewController',
    alias   : 'controller.vcHomeServer',
    config : {
//        urlAdvancedSettingsForModel : '/cake3/rd_cake/ap-profiles/advanced_settings_for_model.json',
//        urlViewAp                   : '/cake3/rd_cake/ap-profiles/ap_profile_ap_view.json'
        //urlView         : '/cake3/rd_cake/home-servers/index.json'
    },
    init: function() {
        var me = this;
    },
    loadSettings: function(panel){ 
	    var me = this;
        var w  = me.getView();
        var hsp_id  = w.hsp_id;

        w.getStore().getProxy().setExtraParam('home_server_pool_id', hsp_id);
        
	},
});
