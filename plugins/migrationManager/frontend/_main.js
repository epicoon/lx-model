/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */



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

const context = new lx.models.Context(Plugin);
context.renew();
