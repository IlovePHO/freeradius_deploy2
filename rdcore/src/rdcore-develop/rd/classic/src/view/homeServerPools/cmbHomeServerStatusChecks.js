Ext.define('Rd.view.homeServerPools.cmbHomeServerStatusChecks', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbHomeServerStatusChecks',
    fieldLabel		: i18n('sStatus_check'),
    labelSeparator	: '',
    valueField		: 'value',
    displayField	: 'name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbHomeServerStatusChecks',
    labelClsExtra: 'lblRd',
    value:"status-server",
    store:[
        {"value":"status-server",  "name":"status-server"},
        {"value":"none",  "name":"none"},
    ]
});

