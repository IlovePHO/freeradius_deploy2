Ext.define('Rd.view.attributeConverts.frmAttributeConvert', {
	extend: 'Ext.form.Panel',
	alias: 'widget.frmAttributeConvert',
	border: false,
	layout: 'column',
	autoScroll: true,
	defaults: {
		layout: 'form',
		xtype: 'container',
		defaultType: 'textfield',
		style: 'width: 50%'
	},
	//items: [],　/*ここで設定するとi18nが効かない?*/
	marginSize: 5,
	initComponent: function () {
		var me = this;
		// アトリビュートのstoreの個別化
		var sSrcAttribute = Ext.create('Rd.store.sAttributes',{'storeId':'sSrcAttribute'});
		var sDstAttribute = Ext.create('Rd.store.sAttributes',{'storeId':'sDstAttribute'});

		me.items = [
			{items: [{
						itemId:'cmbSrcVendor',
						xtype: 'cmbVendor',
						fieldLabel: i18n('sSource_Vendor'),
						emptyText: i18n('sSelect_a_vendor'),
					},
					{
						itemId:'cmbSrcAttribute',
						xtype: 'cmbAttribute',
						fieldLabel:i18n('sSource_attribute'),
						emptyText: i18n('sSelect_an_attribute'),
						name:'src',
						store: sSrcAttribute,
					},
					{
						itemId:'cmbNasTypes',
						xtype: 'cmbNasTypes',
						allowBlank:false,
						fieldLabel: i18n('sNAS_Type'),
						emptyText: i18n('sSelect_a_NAS_Type'),
						name:'nas_type',
					}
				]
			},
			{items: [{
						itemId:'cmbDstVendor',
						xtype: 'cmbVendor',
						fieldLabel: i18n('sDestination_Vendor'),
						emptyText: i18n('sSelect_a_vendor'),
					},
					{
						itemId:'cmbDstAttribute',
						xtype: 'cmbAttribute',
						fieldLabel: i18n('sDestination_attribute'),
						emptyText: i18n('sSelect_an_attribute'),
						name:'dst',
						store: sDstAttribute,
					},
				],
			}
		 ];

		this.callParent(arguments);
	}
});
