Ext.define('Rd.view.homeServerPools.cmbHomeServerTypes', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbHomeServerTypes',
    fieldLabel		: i18n('sType'),
    labelSeparator	: '',
    valueField		: 'id',
    displayField	: 'name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbHomeServerTypes',
    labelClsExtra: 'lblRd',
    store:[
        {"id":"auth+acct",  "name":"AUTH+ACCT (Default)"},
        {"id":"auth",   "name":"AUTH"},
        {"id":"acct",   "name":"ACCT"},
    ]
});

