#lx:use lx.CssContext;

var cssList = new lx.CssContext();

cssList.addClass('rm-side', {
	borderRadius: '5px',
	boxShadow: '0 0 6px rgba(0,0,0,0.5)'
});

return cssList.toString();
