BX.namespace("BX.GridCustom");

// Функция для отправки данных через AJAX
async function do_ajax(options) {
    let type_request = options.type_request || 'POST';  // по умолчанию
    let method = options.method;
    let data = options.data || null;
    return new Promise((resolve, reject) => {
        return BX.ajax({
            url: `https://bitrix71.avh.kz/local/applications/lots/${method}.php`,
            method: type_request,
            data: { 'data': data },
            dataType: 'json',
            processData: false,
            start: true,
            onsuccess: resolve,
            onfailure: function (response) {
                console.error('Ошибка AJAX запроса:', response);
                reject(response); // Отклоняем промис в случае ошибки AJAX запроса
            }
        });
    });
}

// Функция для фильтрации ввода числовых и денежных значений
function filterInput(event) {
    // Разрешаем: цифры, клавиши управления (Backspace, Delete, Arrow keys), точку и минус
    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'];
    const allowedCharacters = '0123456789.,';
    const key = event.key;

    if (allowedKeys.includes(key) || allowedCharacters.includes(key)) {
        return true;
    } else {
        event.preventDefault();
    }
}


// редактирование элементов
BX.GridCustom.editCustom = async function () {
    const deal_id = BX.Crm.Deal.DealComponent.getDealDetailManager()._entityId;

    let response = await do_ajax({
        type_request: 'GET',
        method: 'get_properties_ajax'
    });

    response = JSON.parse(response);

    console.log('editCustom. get_properties_ajax. reponse');
    console.log(response);

    let id_and_type_property = response.types;
    let id_and_values_list = response.values;
    let ib_elems = response.elems;
    let table = document.getElementById('LOTS_GRID_table');

    // Заменя ячеек на inputы
    let mainGridContainer = document.querySelector(".main-grid-container");

    if (!mainGridContainer) {
        console.log("div с классом 'main-grid-container' не найден.");
        return;
    }

    // Внутри mainGridContainer находим tbody
    var tbody = table.querySelector("tbody");
    if (!tbody) {
        console.log("tbody внутри 'main-grid-container' не найден.");
        return;
    }

    // Внутри tbody находим все tr с классом 'main-grid-row main-grid-row-body'
    var rows = tbody.querySelectorAll(".main-grid-row.main-grid-row-body.main-grid-row-checked");

    rows.forEach(function (row, rowIndex) {
        row.dataset.dealid = deal_id;
        create_inputs(row, id_and_type_property, id_and_values_list, ib_elems);
    });

    // работа с родными битриксовскими кнопками
    const save_button = document.querySelector("button.ui-btn-success");
    const cancel_button = document.querySelector("a.ui-btn.ui-btn-link");

    save_button.closest('.ui-entity-wrap').classList.add('crm-section-control-active');

    if (BX.Crm.Deal.DealComponent.getDealDetailManager()._entityId != '0') {

        save_button.removeEventListener('click', save_intermediate_func);
        save_button.addEventListener('click', save_intermediate_func);

        cancel_button.removeEventListener('click', cancel_intermediate_func);
        cancel_button.addEventListener('click', cancel_intermediate_func);
    }

    // отключаем чекбокс "выбрать все" в шапке
    document.getElementById('LOTS_GRID_check_all').disabled = true;

    // замораживаем галочки на выбранных элементах
    rows = table.querySelectorAll('tbody > tr.main-grid-row.main-grid-row-body.main-grid-row-checked');
    rows.forEach((row) => {
        var checkbox = row.querySelector('input[type="checkbox"]');

        // Добавляем обработчик события клика ко всем элементам внутри row, кроме самого checkbox
        [...row.children].forEach(function (child) {
            child.addEventListener('click', function (event) {
                event.preventDefault(); // Останавливаем действие по умолчанию
                event.stopPropagation(); // Останавливаем распространение события
                checkbox.checked = true; // Принудительно устанавливаем галочку в checkbox
            });
        });
    })

    // делаем неактивными невыбранные элементы
    var unactive_rows = document.querySelectorAll('tr.main-grid-row.main-grid-row-body:not(.main-grid-row-checked)');
    unactive_rows.forEach((row) => {

        // Функция для предотвращения событий
        function preventEvent(event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Находим все элементы внутри row
        var elements = row.querySelectorAll('*');

        // Добавляем обработчик события на все элементы внутри row
        elements.forEach(function (element) {
            element.disabled = true;
            element.addEventListener('click', preventEvent);
            element.addEventListener('keydown', preventEvent);
            element.addEventListener('keyup', preventEvent);
            element.addEventListener('keypress', preventEvent);
        });
    });
};

// промежуточная функция сохранения
function save_intermediate_func(event) {
    BX.GridCustom.saveCustom(BX.Crm.Deal.DealComponent.getDealDetailManager()._entityId, true);
    event.target.closest('.ui-entity-wrap').classList.remove('crm-section-control-active');
    console.log('Save and Произошло событие', event.type);
}

// промежуточная функция отмены
function cancel_intermediate_func(event) {
    BX.GridCustom.cancelCustom();
    event.target.closest('.ui-entity-wrap').classList.remove('crm-section-control-active');
    console.log('Cancel and Произошло событие', event.type);
}

// получаем значения инпутов и селектов
function getInputValues(row, headers) {
    const inputs = row.querySelectorAll('input, select');
    let count_index = 0;

    // далее идёт проверка на пустые элементы
    inputs.forEach((elem) => {
        if (elem.value == '' || elem.value == '0') {
            count_index += 1;
        }
    });

    // -1, потому что есть input checkbox с заполненным value
    if (count_index == inputs.length - 1) { // Если все колонки не заполнены
        return;
    }

    var vals = {};
    for (let i = 0; i < inputs.length; i++) {
        if (!headers[i] || !headers[i].hasAttribute("data-name")) {
            console.log('No attribute DATA-NAME');
            return;
        }
        const dataName = headers[i].getAttribute('data-name');
        if (dataName != 0) {
            vals[dataName] = inputs[i].value;
        }
    };

    return vals;
}

// Основная функция для формирования массива данных
function getRowsData() {
    const table = document.getElementById('LOTS_GRID_table');
    const headers = table.querySelectorAll('thead th[data-name]');
    const rows = table.querySelectorAll('tr.main-grid-row.main-grid-row-body.main-grid-row-checked');
    var rowsData = {};
    let i = 0;
    console.log('rows');
    console.log(rows);

    rows.forEach(row => {
        const rowId = row.getAttribute('data-id');
        const rowValues = getInputValues(row, headers);
        if (!rowValues) {
            return;
        }
        if (rowId > 0) {
            rowsData[rowId] = rowValues;
        } else {
            rowsData[i++] = rowValues;
        }
    });

    return rowsData;
}


// берём элементы для сохранения
BX.GridCustom.saveCustom = async function (deal_id, flag_make_reboot) {
    let data = getRowsData();
    data['deal_id'] = deal_id;
    console.log('data');
    console.log(data);
    let response = await do_ajax({
        method: 'save_lots',
        data: data
    });

    console.log('saveCustom. save_lots. reponse');
    console.log(response);

    if (flag_make_reboot) {
        reboot();
    }
}

// создаём инпуты
function create_inputs(row, types, values, elems) {


    var tds = row.querySelectorAll('td[class="main-grid-cell main-grid-cell-left"],[class=""]');
    tds.forEach(function (td, tdIndex) {
        var cellIndex = Array.prototype.indexOf.call(row.children, td);
        var div = td.querySelector(".main-grid-cell-inner");
        var span = td.querySelector(".main-grid-cell-content");

        if (!span) {
            console.log("span с классом 'main-grid-cell-content' не найден.");
            return;
        }

        var th = row
            .parentElement.parentElement // находим table
            .querySelector(`thead th:nth-child(${cellIndex + 1})`);

        if (!th || !th.hasAttribute("data-name")) {
            return;
        }
        var dataName = th.getAttribute("data-name");
        var inputType = types[dataName] || "text"; // По умолчанию используем 'text'

        // Создаем input или select в зависимости от типа
        var input;
        const max_length_input_int = 15;

        if (inputType === "string") {
            // Создаем input для типа 'string'
            input = document.createElement("input");
            input.type = "text";
            input.value = span.textContent; // Устанавливаем значение из span в input
        } else if (inputType === "integer" || inputType === "money") {
            input = document.createElement("input");
            input.type = "text";
            input.value = span.textContent; // Устанавливаем значение из span в value

            // валидация ввода
            input.addEventListener('keydown', filterInput);
            if (inputType === "money") {
                input.placeholder = "KZT";

                // обработчик события input для удаления недопустимых символов
                input.addEventListener('input', e => {
                    const re = /[^0-9,\.]/g;
                    const reMultipleDots = /([,\.].*)[,\.]/;
                    e.target.value = e.target.value.replace(re, '')
                        .replace(reMultipleDots, (match, g) => g);

                    // проверка на максимальное кол-во символов
                    if (e.target.value.length > max_length_input_int) {
                        e.target.value = e.target.value.slice(0, max_length_input_int);
                    }
                });
            // input type == integer
            } else {

                // обработчик события input для удаления недопустимых символов
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/[^0-9]/g, '');

                    // проверка на максимальное кол-во символов
                    if (input.value.length > max_length_input_int) {
                        input.value = input.value.slice(0, max_length_input_int);
                    }
                });
            }

        } else if (inputType === "list" && typeof values[dataName] === 'object') {
            // Создаем select для типа 'list', если значения являются массивом
            input = document.createElement("select");

            // добавляем в список значение (не установлено)
            var option = document.createElement("option");
            option.text = '(не установлено)';
            option.value = 0;
            input.appendChild(option);

            for (const [key, value] of Object.entries(values[dataName])) {
                option = document.createElement("option");
                option.text = value;
                option.value = key;
                if (value == span.textContent) {
                    option.selected = true; // Устанавливаем selected значение из jsArray
                }
                input.appendChild(option);
            }
            // делаем инпут поля Статус - disabled
            if (window.disabled_field && window.disabled_field.includes(dataName)) {
                input.disabled = true;
            }
        }

        // Добавляем input в td, если он определен
        if (input) {
            td.textContent = "";
            td.appendChild(input);
        }

    });
};

// добавляем пустую строку
async function add_empty_row() {

    let response = await do_ajax({
        type_request: 'GET',
        method: 'get_properties_ajax'
    });

    console.log('add_empty_row. get_properties_ajax. reponse');
    console.log(response);

    response = JSON.parse(response);

    let id_and_type_property = response.types;
    let id_and_values_list = response.values;
    let ib_elems = response.elems;
    let zero_var = 0;

    BX.ready(() => {
        const grid = BX.Main.gridManager.getById('LOTS_GRID');
        const gridObject = grid?.instance;
        const gridRealtime = gridObject.getRealtime();
        gridRealtime.addRow({
            id: zero_var,
            insertBefore: 1,
            columns: {},
        });
    })

    var row = document.querySelector(`tr[data-id="${zero_var}"]`);
    row.classList.add('main-grid-row-checked');
    row.querySelector('td span input[type="checkbox"]').checked = true;

    // имитируем нажатие на кнопку
    document.querySelector('.ui-btn.icon.edit').click();

    create_inputs(row, id_and_type_property, id_and_values_list, ib_elems);

    const button = document.getElementById('add_button');
    // отключаем кнопку, чтобы успело прогрузиться
    button.style.pointerEvents = 'none';
    button.style.opacity = '0.5';

    // Через 1 секунду включаем ссылку обратно
    setTimeout(() => {
        button.style.pointerEvents = '';
        button.style.opacity = '';
    }, 1000);

    // прибавляем счетчик, который выводит количество строк в гриде
    let number_of_rows_table = Number(document.getElementById('line-counter').innerText);
    document.getElementById('line-counter').innerHTML = number_of_rows_table + 1;
}

// перезагружаем страницу
BX.GridCustom.cancelCustom = function () {
    reboot();
}

// Кнопка удалить в панели сниппетов
async function main_remove() {
    // let deal_id = window.top.location.href.split('/')[6];
    let rows_id = [];
    let arr_to_send = {};
    var table = document.getElementById('LOTS_GRID_table');
    if (!table) {
        return;
    }
    // Внутри mainGridContainer находим tbody
    var tbody = table.querySelector("tbody");
    if (!tbody) {
        return;
    }
    // Внутри tbody находим все tr с классом 'main-grid-row main-grid-row-body'
    var trs = tbody.querySelectorAll(".main-grid-row.main-grid-row-body.main-grid-row-checked");

    trs.forEach(function (tr, trIndex) {
        const trId = tr.getAttribute('data-id');
        rows_id.push(trId);
    })

    arr_to_send.data = rows_id;
    arr_to_send.deal_id = BX.Crm.Deal.DealComponent.getDealDetailManager()._entityId;

    console.log('arr_to_send');
    console.log(arr_to_send);

    let response = await do_ajax({
        method: 'remove_lots',
        data: arr_to_send
    });

    console.log('main_remove. remove_lots. reponse');
    console.log(response);
}

// функция перезагрузки
function reboot() {
    location.reload();
}

// диалоговое окно с подверждением удаления
BX.GridCustom.removeCustom = function () {
    BX.ready(function () {
        var popup = BX.PopupWindowManager.create("popup-message", null, {
            content: '<h1>Удалить отмеченные элементы?</h1>',
            width: 400, // ширина окна
            height: 200, // высота окна
            zIndex: 100, // z-index
            closeIcon: {
                // объект со стилями для иконки закрытия, при null - иконки не будет
                opacity: 1
            },
            closeByEsc: true, // закрытие окна по esc
            darkMode: false, // окно будет светлым или темным
            autoHide: false, // закрытие при клике вне окна
            draggable: true, // можно двигать или нет
            resizable: true, // можно ресайзить
            min_height: 100, // минимальная высота окна
            min_width: 100, // минимальная ширина окна
            lightShadow: true, // использовать светлую тень у окна
            angle: false, // появится уголок
            overlay: {
                // объект со стилями фона
                backgroundColor: 'black',
                opacity: 500
            },
            buttons: [
                new BX.PopupWindowButton({
                    text: 'Удалить', // текст кнопки
                    id: 'remove-btn', // идентификатор
                    className: 'ui-btn ui-btn-remove', // доп. классы
                    events: {
                        click: function () {
                            // Событие при клике на кнопку
                            main_remove();
                            popup.close();
                            console.log('лот удалён');
                            reboot();
                        }
                    }
                }),
                new BX.PopupWindowButton({
                    text: 'Отменить',
                    id: 'cancel-btn',
                    className: 'ui-btn ui-btn-primary',
                    events: {
                        click: function () {
                            popup.close();
                        }
                    }
                })
            ],
          /*
            events: {
            onPopupShow: function () {
                // Событие при показе окна
            },
            onPopupClose: function () {
                BX.PopupWindow.prototype.close;
            }
        } */
        });
        popup.show();
    });
}

// скрываем столбец
function hide_column(column_id) {
    var header = document.querySelector(`th[data-name="${column_id}"]`);

    if (!header) {
        return;
    }

    var columnIndex = Array.prototype.indexOf.call(header.parentElement.children, header);

    // Скрыть заголовок столбца
    header.style.display = 'none';

    var rows = document.querySelectorAll('table tr');
    rows.forEach(function (row) {
        var cells = row.children;
        if (!cells[columnIndex]) {
            return;
        }
        // Скрыть ячейку с нужным индексом
        cells[columnIndex].style.display = 'none';
    });

}

// скрыть панель редактирования с кнопками Редактировать, Удалить
function hide_control_panel() {
    document.getElementsByClassName('main-grid-control-panel-table')[0].style.display = 'none';
    hide_column(0);
}

// выводим общее количество лотов
function display_total_quantity() {
    var table = document.getElementById('LOTS_GRID_table');

    if (!table) {
        console.log('Таблица не найдена');
        return;
    }

    var tbody = table.querySelector("tbody");
    if (tbody) {
        var trs = tbody.querySelectorAll(".main-grid-row.main-grid-row-body:not(.main-grid-not-count)");
    }
    var filteredTrElements = Array.from(trs).filter(function (tr) {
        return tr.hasAttribute('data-id') && tr.getAttribute('data-id').trim() !== '';
    });
    var count_trs = filteredTrElements.length;
    var table = document.getElementsByClassName('main-grid-panel-table')[0];
    var td_in = table.querySelector('td.main-grid-panel-cell.main-grid-panel-cell-pagination.main-grid-cell-left');
    td_in.innerHTML = `<div><span class="main-grid-panel-content-title">Всего:</span> <span id="line-counter" class="main-grid-panel-content-text">${count_trs}</span></div>`;
}