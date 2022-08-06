#lx:require pluginClasses/;

class Plugin extends lx.Plugin {
    initCss(css) {
        css.addClass('rm-side', {
            borderRadius: '5px',
            boxShadow: '0 0 6px rgba(0,0,0,0.5)'
        });

        css.addClass('rm-selected', {
            backgroundColor: css.preset.checkedDeepColor
        });
    }

    run() {
        this.core = new Core(this);
    }
}
