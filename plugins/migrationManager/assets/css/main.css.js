#lx:use lx.CssColorSchema;
#lx:use lx.MainCssContext;

const cssContext = lx.MainCssContext.instance;

function icon(code) {
	return [code, {fontSize: 10, paddingTop: '4px'}];
}

cssContext.inheritClasses({
	'lx-model-renew' : { backgroundColor: lx.CssColorSchema.neutralMainColor, '@icon': icon('\\21BB') },
	'lx-model-up'    : { backgroundColor: lx.CssColorSchema.checkedMainColor, '@icon': icon('\\21D1') },
	'lx-model-down'  : { backgroundColor: lx.CssColorSchema.hotMainColor,     '@icon': icon('\\21D3') },
	'lx-model-gen'   : { backgroundColor: lx.CssColorSchema.checkedMainColor, '@icon': icon('\\270E') },
	'lx-model-info'  : { backgroundColor: lx.CssColorSchema.neutralMainColor, '@icon': icon('\\0069') }
}, 'ActiveButton');

cssContext.addClass('lx-model-action-report-service', {
	backgroundColor: lx.CssColorSchema.checkedLightColor
});
cssContext.addClass('lx-model-action-report-title', {
	paddingLeft: '10px'
});
cssContext.addClass('lx-model-action-report-row', {
	paddingLeft: '30px'
});

cssContext.addClass('lx-model-mapplied', {
	backgroundColor: lx.CssColorSchema.checkedLightColor
});
cssContext.addClass('lx-model-munapplied', {
	backgroundColor: lx.CssColorSchema.hotLightColor
});

cssContext.addClass('lx-model-iup', {
	backgroundColor: lx.CssColorSchema.checkedSoftColor
});
cssContext.addClass('lx-model-idown', {
	backgroundColor: lx.CssColorSchema.hotSoftColor
});

cssContext.addClass('lx-model-migtext', {
	backgroundColor: 'gray',
	color: 'white',
	overflow: 'auto'
});

return cssContext.toString();
