/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 *
 * @const {lx.Box} leftBox
 * @const {lx.Box} rightBox
 * @const {lx.Box} resizer
 */

var resizerW = 10,
	w = Snippet.widget.width('px'),
	boxesW = w - resizerW,
	w0 = Math.ceil(boxesW * 0.5),
	w1 = Math.floor(boxesW * 0.5);
leftBox.width(w0 + 'px');
resizer.left(w0 + 'px');
rightBox.width(w1 + 'px');
rightBox.left(w0 + resizerW + 'px');

//todo - Баг: текст с пробелами с первого раза нормально не выравнивается - видимо он изначально переносится и неверно считается его бокс
leftBox.trigger('resize');
rightBox.trigger('resize');
leftBox.trigger('resize');
rightBox.trigger('resize');

Snippet.widget.on('resize', function() {
	var newW = this.width('px'),
		k = newW / w,
		newW0 = Math.round(w0 * k),
		newW1 = newW - resizerW - newW0;
	leftBox.width(newW0 + 'px');
	resizer.left(newW0 + 'px');
	rightBox.width(newW1 + 'px');
	rightBox.left(newW0 + resizerW + 'px');
	w = newW;
	w0 = newW0;
});

resizer.move();
resizer.on('move', function() {
	var l = this.left('px');
	w0 = l;
	leftBox.width(w0 + 'px');
	rightBox.width(Snippet.widget.width('px') - resizerW - w0 + 'px');
	rightBox.left(w0 + resizerW + 'px');

	leftBox.trigger('resize');
	rightBox.trigger('resize');
});
