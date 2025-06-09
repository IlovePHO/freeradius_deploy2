Ext.define('Rd.view.permanentUsers.vcPermanentUserGeneric', {
    extend  : 'Ext.app.ViewController',
    alias   : 'controller.vcPermanentUserGeneric',
    config : {
    },
    init: function() {
        var me = this;
    },
    control: {
    },
    stores      : [	
		'sPermanentUsers'
    ],
	onCmbRealmChange: function(cmb){
		var me				= this;
		var val				= cmb.getValue();
        var form			= cmb.up('form');
		var cmb_sub_groups	= form.down('cmbSubGroups');

        cmb_sub_groups.realmId = val;
        cmb_sub_groups.initComponent();
	}
});
