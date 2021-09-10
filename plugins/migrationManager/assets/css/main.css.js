#lx:use #lx:php(\lx::$app->assets->getCssColorSchema());
#lx:use lx.MainCssContext;

function icon(code) {
	return [code, {fontSize: 10, paddingTop: '4px'}];
}

cssContext.inheritClasses({
	'lx-model-renew' : { backgroundColor: neutralMainColor, '@icon': icon('\\21BB') },
	'lx-model-up'    : { backgroundColor: checkedMainColor, '@icon': icon('\\21D1') },
	'lx-model-down'  : { backgroundColor: hotMainColor,     '@icon': icon('\\21D3') },
	'lx-model-gen'   : { backgroundColor: checkedMainColor, '@icon': icon('\\270E') },
	'lx-model-info'  : { backgroundColor: neutralMainColor, '@icon': icon('\\0069') }
}, 'ActiveButton');

cssContext.addClass('lx-model-action-report-service', {
	backgroundColor: checkedLightColor
});
cssContext.addClass('lx-model-action-report-title', {
	paddingLeft: '10px'
});
cssContext.addClass('lx-model-action-report-row', {
	paddingLeft: '30px'
});

cssContext.addClass('lx-model-mapplied', {
	backgroundColor: checkedLightColor
});
cssContext.addClass('lx-model-munapplied', {
	backgroundColor: hotLightColor
});

cssContext.addClass('lx-model-iup', {
	backgroundColor: checkedSoftColor
});
cssContext.addClass('lx-model-idown', {
	backgroundColor: hotSoftColor
});

cssContext.addClass('lx-model-migtext', {
	backgroundColor: 'gray',
	color: 'white',
	overflow: 'auto'
});

return cssContext.toString();
