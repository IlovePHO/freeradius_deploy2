Ext.define('Rd.view.homeServerPools.vcHomeServerPoolGeneric', {
    extend  : 'Ext.app.ViewController',
    alias   : 'controller.vcHomeServerPoolGeneric',
    config : {
        urlView         : '/cake3/rd_cake/home-server-pools/view.json'
    },
    init: function() {
        var me = this;
    },  
    onBtnPickOwnerClick: function(button){
        var me 		        = this;
        var pnl             = button.up('panel');
        var updateDisplay  = pnl.down('#displUser');
        var updateValue    = pnl.down('#hiddenUser'); 
        
        console.log("Clicked Change Owner");
        if(!Ext.WindowManager.get('winSelectOwnerId')){
            var w = Ext.widget('winSelectOwner',{id:'winSelectOwnerId',updateDisplay:updateDisplay,updateValue:updateValue});
            w.show();
        }
    },
	loadSettings: function(panel){ 
	    var me = this;
        var w  = me.getView();
        var hsp_id  = w.hsp_id;

        w.load({
            url         :me.getUrlView(), 
            method      :'GET',
            params      :{home_server_pool_id : hsp_id},
            success     : function(a,b,c){
                // console.log(b.result.data);
            }
        });
        
	},
});
