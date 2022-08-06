#lx:require -R src/;

class Plugin extends lx.Plugin {
    initCss(css) {
        function icon(code) {
            return [code, {fontSize: 10}];
        }

        css.inheritAbstractClass('mm-icon', 'ActiveButton', {
            color: css.preset.widgetColoredIconColor
        });
        css.inheritClasses({
            'lx-model-renew' : { backgroundColor: css.preset.neutralMainColor, '@icon': icon('\\21BB') },
            'lx-model-up'    : { backgroundColor: css.preset.checkedMainColor, '@icon': icon('\\21D1') },
            'lx-model-down'  : { backgroundColor: css.preset.hotMainColor,     '@icon': icon('\\21D3') },
            'lx-model-gen'   : { backgroundColor: css.preset.checkedMainColor, '@icon': icon('\\270E') },
            'lx-model-info'  : { backgroundColor: css.preset.neutralMainColor, '@icon': icon('\\0069') }
        }, 'mm-icon');

        css.addClass('lx-model-count', {
            backgroundColor: css.preset.altMainBackgroundColor
        });

        css.addClass('lx-model-action-report-service', {
            backgroundColor: css.preset.checkedLightColor
        });
        css.addClass('lx-model-action-report-title', {
            paddingLeft: '10px'
        });
        css.addClass('lx-model-action-report-row', {
            paddingLeft: '30px'
        });

        css.addClass('lx-model-mapplied', {
            backgroundColor: css.preset.checkedDeepColor
        });
        css.addClass('lx-model-munapplied', {
            backgroundColor: css.preset.hotDeepColor
        });

        css.addClass('lx-model-iup', {
            backgroundColor: css.preset.checkedSoftColor
        });
        css.addClass('lx-model-idown', {
            backgroundColor: css.preset.hotSoftColor
        });
        css.addClass('lx-model-iselected', {
            borderRadius: css.preset.borderRadius,
            backgroundColor: css.preset.neutralMainColor
        });

        css.addClass('lx-model-migtext', {
            backgroundColor: css.preset.bodyBackgroundColor,
            overflow: 'auto'
        });
    }

    run() {
        const context = new lx.models.Context(this);
        context.renew();
    }
}
