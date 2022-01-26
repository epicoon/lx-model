/*
- Контекст: весь проект, есть ненакаченные миграции
* Посмотреть список ненакаченных миграций в проекте
* Накатывание всех миграций

- Контекст: весь проект, все миграции накачены, но есть несоответствия в цепочке схема-код-репозиторий
* Проверить соответствия схема-код
* Актуализировать код по схемам
* Проверить соответствия код-репозиторий
* Сгенерировать миграции (доступно только если решены несоответствия схема-код)

- Контекст: конкретный сервис, есть ненакаченные миграции
* Посмотреть список всех миграций в конкретном сервисе (с возможностью фильтровать отдельно накаченные/ненакаченные)
* Накатывание определенного числа (по умолчанию всех) миграций

- Контекст: конкретный сервис, все миграции накачены, но есть несоответствия в цепочке схема-код-репозиторий
* Проверить соответствия схема-код
* Актуализировать код по схемам
* Проверить соответствия код-репозиторий
* Сгенерировать миграции (доступно только если решены несоответствия схема-код)

- Контекст: конкретный сервис, все миграции накачены, нет несоответствий в цепочке схема-код-репозиторий
* Откатывание определенного числа (по умолчанию одной) миграций
*/

#lx:require -R src/;

class Plugin extends lx.Plugin {
    initCssAsset(css) {
        function icon(code) {
            return [code, {fontSize: 10, paddingTop: '4px'}];
        }

        css.inheritClasses({
            'lx-model-renew' : { backgroundColor: css.preset.neutralMainColor, '@icon': icon('\\21BB') },
            'lx-model-up'    : { backgroundColor: css.preset.checkedMainColor, '@icon': icon('\\21D1') },
            'lx-model-down'  : { backgroundColor: css.preset.hotMainColor,     '@icon': icon('\\21D3') },
            'lx-model-gen'   : { backgroundColor: css.preset.checkedMainColor, '@icon': icon('\\270E') },
            'lx-model-info'  : { backgroundColor: css.preset.neutralMainColor, '@icon': icon('\\0069') }
        }, 'ActiveButton');

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
            backgroundColor: css.preset.checkedLightColor
        });
        css.addClass('lx-model-munapplied', {
            backgroundColor: css.preset.hotLightColor
        });

        css.addClass('lx-model-iup', {
            backgroundColor: css.preset.checkedSoftColor
        });
        css.addClass('lx-model-idown', {
            backgroundColor: css.preset.hotSoftColor
        });

        css.addClass('lx-model-migtext', {
            backgroundColor: 'gray',
            color: 'white',
            overflow: 'auto'
        });
    }

    run() {
        const context = new lx.models.Context(this);
        context.renew();
    }
}
