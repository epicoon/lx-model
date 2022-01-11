#lx:use lx.CssColorSchema;

var cssList = new lx.CssContext();

cssList.addClass('rm-side', {
	borderRadius: '5px',
	boxShadow: '0 0 6px rgba(0,0,0,0.5)'
});

cssList.addClass('rm-selected', {
	backgroundColor: checkedDeepColor
});

return cssList.toString();
