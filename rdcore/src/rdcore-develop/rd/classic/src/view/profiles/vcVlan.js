Ext.define('Rd.view.profiles.vcVlan', {
    extend  : 'Ext.app.ViewController',
    alias   : 'controller.vcVlan',
    init    : function() {
        var me = this;
    },
    sldrToggleChange: function(sldr){
		var me 		    = this;
		var pnl    	    = sldr.up('panel');
		var cnt         = pnl.down('#cntDetail');
        var value       = sldr.getValue();     
		if(value == 0){
		    cnt.hide();
		}else{
		    cnt.show();
		}
	}
});
