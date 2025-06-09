Ext.define('Rd.view.homeServerPools.cmbHomeServerProtocols', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbHomeServerProtocols',
    fieldLabel		: i18n('sProtocol'),
    labelSeparator	: '',
    valueField		: 'value',
    displayField	: 'name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbHomeServerProtocols',
    labelClsExtra: 'lblRd',
    store:[
        {"value":"udp",  "name":"UDP"},
        {"value":"tcp",  "name":"TCP"},
    ]
});

