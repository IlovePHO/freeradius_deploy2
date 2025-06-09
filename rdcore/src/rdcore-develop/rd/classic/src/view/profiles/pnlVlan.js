Ext.define('Rd.view.profiles.pnlVlan', {
    extend      : 'Ext.panel.Panel',
    glyph       : Rd.config.icnGears,
    alias       : 'widget.pnlVlan',
    requires    : [
        'Rd.view.profiles.vcVlan',
        'Rd.view.components.rdSlider'
    ],
    controller  : 'vcVlan',
    layout      : { type: 'vbox'},
    //layout      : { type: 'vbox', align: 'center' },
    title       : "VLAN",
    initComponent: function(){
        var me      = this;
        var w_sec   = 350;
        var w_rd    = 68;
        me.width    = 550;
        me.padding  = 5;
        me.items    = [
			{
			    xtype       : 'sldrToggle',
			    fieldLabel  : i18n('sEnable_fs_Disable'),
			    userCls     : 'sldrDark',
			    name        : 'vlan_enabled',
			    itemId      : 'vlan_enabled',
			    value       : 1,
			    listeners   : {
					change  : 'sldrToggleChange'
				}
			},
			{ 
			    xtype       : 'container',
			    itemId      : 'cntDetail',
			    items       : [
			        {
			            xtype       : 'numberfield',
			            fieldLabel  : 'VLAN ID',
			            name        : 'vlan_id',
			            allowBlank  : true,
			            blankText   : i18n("sSupply_a_value"),
			            maxValue    : 4094,
			            minValue    : 1,
			            step        : 1,
			            maxText     : i18n("sThe_maximum_value_for_this_field_is"),
			            minText     : i18n("sThe_minimum_value_for_this_field_is"),
			            nanText     : i18n("sis_not_a_valid_number"),
			        },
                ]
            }
        ];       
        this.callParent(arguments);
    }
});
