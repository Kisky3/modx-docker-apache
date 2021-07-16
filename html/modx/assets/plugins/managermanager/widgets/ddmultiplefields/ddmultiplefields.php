<?php
/** 
 * mm_ddMultipleFields
 * @version 4.3.4 (2012-12-20)
 * 
 * Позволяет добавлять произвольное количество полей (TV) к одному документу (записывается в одно через разделители).
 *
 * @param tvs {comma separated string} - Имена TV, для которых необходимо применить виджет.
 * @param roles {comma separated string} - Роли, для которых необходимо применить виждет, пустое значение — все роли. По умолчанию: ''.
 * @param templates {comma separated string} - Id шаблонов, для которых необходимо применить виджет, пустое значение — все шаблоны. По умолчанию: ''.
 * @param coloumns {comma separated string} - Типы колонок (field — колонка типа поля, text — текстовая колонка, id — скрытое поле с уникальным идентификатором, select — список с выбором значений (см. coloumnsData)). По умолчанию: 'field'.
 * @param coloumnsTitle {comma separated string} - Названия колонок. По умолчанию: ''.
 * @param colWidth {comma separated string} - Ширины колонок (может быть задана одна ширина). По умолчанию: 180;
 * @param splY {string} - Разделитель между строками. По умолчанию: '||'.
 * @param splX {string} - Разделитель между колонками. По умолчанию: '::'.
 * @param imgW {integer} - Максимальная ширина превьюшки. По умолчанию: 300.
 * @param imgH {integer} - Максимальная высота превьюшки. По умолчанию: 100.
 * @param minRow {integer} - Минимальное количество строк. По умолчанию: 0.
 * @param maxRow {integer} - Максимальное количество строк. По умолчанию: 0 (без лимита).
 * @param coloumnsData {separated string} - Список возможных значений для полей в формате json, через ||. По умолчанию: ''.
 * 
 * @link http://code.divandesign.biz/modx/mm_ddmultiplefields/4.3.4
 * 
 * @copyright 2012, DivanDesign
 * http://www.DivanDesign.ru
 */

function mm_ddMultipleFields($tvs = '', $roles = '', $templates = '', $columns = 'field', $columnsTitle = '', $colWidth = '180', $splY = '||', $splX = '::', $imgW = 300, $imgH = 100, $minRow = 0, $maxRow = 0, $columnsData = ''){

	global $modx, $mm_current_page, $_lang;
	$e = &$modx->Event;
	
	if ($e->name !== 'OnDocFormRender' || !useThisRule($roles, $templates)) {
        return;
    }

    $output = '';
    $widgetDir = MODX_BASE_URL . 'assets/plugins/managermanager/widgets/ddmultiplefields/';

    if ($columnsData) {
        $columnsDataTemp = explode('||', $columnsData);
        $columnsData = array();
        foreach ($columnsDataTemp as $value) {
            //Евалим знение и записываем результат или исходное значени
            $eval = @eval($value);
            $columnsData[] = $eval ? addslashes(json_encode($eval)) : $value;
        }
        //Сливаем в строку, что бы передать на клиент
        $columnsData = implode('||', $columnsData);
    }

    //Стиль превью изображения
    $stylePrewiew = "max-width:{$imgW}px; max-height:{$imgH}px; margin: 4px 0; cursor: pointer;";

    // Which template is this page using?
    if (isset($mm_current_page['template'])) {
        $page_template = $mm_current_page['template'];
    } else {
        // If no content is set, it's likely we're adding a new page at top level.
        // So use the site default template. This may need some work as it might interfere with a default template set by MM?
        $page_template = $modx->config['default_template'];
    }

    $tvsMas = array();
    // Does this page's template use any image or file or text TVs?
    $types = explode(',', 'image,file,text,textarea');
    foreach ($types as $type) {
        $tvsTemp = tplUseTvs($page_template, $tvs, $type);
        if ($tvsTemp) {
            foreach ($tvsTemp as $v) {
                $v['type'] = $type;
                $tvsMas[] = $v;
            }
        }
    }

    if (count($tvsMas) == 0) {
        return;
    }

    $output .= "// ---------------- mm_ddMultipleFields :: Begin ------------- \n";
    //General functions
    $output .= '
//Если ui-sortable ещё не подключён, подключим
if (!jQuery.ui || !jQuery.ui.sortable){' . includeJs($widgetDir . 'jquery-ui.custom.min.js') . '}

//Проверяем на всякий случай (если вдруг вызывается пару раз)
if (!ddMultiple){
' . includeCss($widgetDir . 'ddmultiplefields.css') . '
var ddMultiple = {
datePickerOffset: ' . $modx->config['datepicker_offset'] . ',
datePickerFormat: "' . $modx->config['datetime_format'] . '" + " hh:mm:00",
ids: new Array(),
//Обновляет мульти-поле, берёт значение из оригинального поля
updateField: function(id){
    //Если есть текущее поле
    if (ddMultiple[id].currentField){
        //Задаём значение текущему полю (берём у оригинального поля), запускаем событие изменения
        ddMultiple[id].currentField.val(jQuery.trim(jQuery("#" + id).val())).trigger("change.ddEvents");
        //Забываем текущее поле (ибо уже обработали)
        ddMultiple[id].currentField = false;
    }
},
//Обновляет оригинальное поле TV, собирая данные по мульти-полям
updateTv: function(id){
    var masRows = new Array();
    
    //Перебираем все строки
    jQuery("#" + id + "ddMultipleField .ddFieldBlock").each(function(){
        var $this = jQuery(this),
            masCol = new Array(),
            id_field = {index: false, val: false, $field: false};
        
        //Перебираем все колонки, закидываем значения в массив
        $this.find(".ddField").each(function(index){
            //Если поле с типом id
            if (ddMultiple[id].coloumns[index] == "id"){
                id_field.index = index;
                id_field.$field = jQuery(this);
                
                //Сохраняем значение поля
                id_field.val = id_field.$field.val();
                //Если значение пустое, то генерим
                if (id_field.val == "") id_field.val = (new Date).getTime();
                
                //Обнуляем значение
                id_field.$field.val("");
            }
            //Собираем значения строки в массив
            masCol.push(jQuery.trim(jQuery(this).val()));
        });
        
        var col = masCol.join(ddMultiple[id].splX);
        if (col.length != ((masCol.length - 1) * ddMultiple[id].splX.length)){
            //Проверяем было ли поле с id
            if (id_field.index !== false){
                //Записываем значение в поле
                id_field.$field.val(id_field.val);
                //Обновляем значение в массиве
                masCol[id_field.index] = id_field.val;
                //Пересобираем строку
                col = masCol.join(ddMultiple[id].splX);
            }
            masRows.push(col);
        }
    });

    //Записываем значение в оригинальное поле
    jQuery("#" + id).val(ddMultiple.maskQuoutes(masRows.join(ddMultiple[id].splY)));
},
//Инициализация
//Принимает id оригинального поля, его значения и родителя поля
init: function(id, val, target){
    //Делаем таблицу мульти-полей, вешаем на таблицу функцию обновления оригинального поля
    var $ddMultipleField = jQuery("<table class=\"ddMultipleField\" id=\"" + id + "ddMultipleField\"></table>").appendTo(target)/*.
                            on("change.ddEvents", function(){ddMultiple.updateTv(id);})*/;
    
    //Если заголовков хватает
    if (ddMultiple[id].coloumnsTitle.length == ddMultiple[id].coloumns.length){
        var text = "";
        //Создадим шапку
        jQuery.each(ddMultiple[id].coloumnsTitle, function(key, val){
            text += "<th>"+val+"</th>";
        });
        
        jQuery("<tr><th></th>" + text + "<th></th></tr>").appendTo($ddMultipleField);
    }
    
    //Делаем новые мульти-поля
    var arr = val.split(ddMultiple[id].splY);
    
    //Проверяем на максимальное и минимальное количество строк
    if (ddMultiple[id].maxRow && arr.length > ddMultiple[id].maxRow) arr.length = ddMultiple[id].maxRow;
    else if (ddMultiple[id].minRow && arr.length < ddMultiple[id].minRow) arr.length = ddMultiple[id].minRow;
    
    for (var i = 0, len = arr.length; i < len; i++){
        //В случае, если размер массива был увеличен по minRow, значением будет undefined, посему зафигачим пустую строку
        ddMultiple.makeFieldRow(id, arr[i] || "");
    }
    
    //Создаём кнопку +
    ddMultiple.makeAddButton(id);
    
    //Добавляем возможность перетаскивания
    $ddMultipleField.sortable({
        items: "tr:has(td)",
        handle: ".ddSortHandle",
        cursor: "move",
        axis: "y",
/*			tolerance: "pointer",*/
/*			containment: "parent",*/
        placeholder: "ui-state-highlight",
        start: function(event, ui){
            ui.placeholder.html("<td colspan=\""+(ddMultiple[id].coloumns.length+2)+"\"><div></div></td>").find("div").css("height", ui.item.height());
        },
        stop: function(event, ui){
            //Находим родителя таблицы, вызываем функцию обновления поля
            ui.item.parents(".ddMultipleField:first").trigger("change.ddEvents");
            ddMultiple.moveAddButton(id);
        }
    });
    //Запускаем обновление, если были ограничения
    if (ddMultiple[id].maxRow || ddMultiple[id].minRow) $ddMultipleField.trigger("change.ddEvents");
    
},
//Функция создания строки
//Принимает id и данные строки
makeFieldRow: function(id, val){
    //Проверяем привышает ли количество строк максимальное
    if (ddMultiple[id].maxRow && jQuery("#"+id+"ddMultipleField .ddFieldBlock").length >= ddMultiple[id].maxRow) return;
    var $fieldBlock = jQuery("<tr class=\"ddFieldBlock " + id + "ddFieldBlock\" ><td class=\"ddSortHandle\"><div></div></td></tr>").appendTo(jQuery("#" + id + "ddMultipleField"));
    
    //Разбиваем переданное значение на колонки
    val = ddMultiple.maskQuoutes(val).split(ddMultiple[id].splX);
    
    var $field;

    //Перебираем колонки
    jQuery.each(ddMultiple[id].coloumns, function(key){
        if (!val[key]) val[key] = "";
        if (!ddMultiple[id].coloumnsTitle[key]) ddMultiple[id].coloumnsTitle[key] = "";
        if (!ddMultiple[id].colWidth[key] || ddMultiple[id].colWidth[key] == "") ddMultiple[id].colWidth[key] = ddMultiple[id].colWidth[key - 1];
    
        var $col = ddMultiple.makeFieldCol($fieldBlock);

        //По умолчанию создаём поле как текстовое
        $field = ddMultiple.makeText(val[key], ddMultiple[id].coloumnsTitle[key], ddMultiple[id].colWidth[key], $col);

        //Если текущая колонка является полем
        if(ddMultiple[id].coloumns[key] == "field"){
            ddMultiple[id].makeFieldFunction(id, $col);

            //If is file or image
            if (ddMultiple[id].browseFuntion){
                //Create Attach browse button
                jQuery("<input class=\"ddAttachButton\" type=\"button\" value=\"' . $_lang['insert'] . '\" />").insertAfter($field).on("click", function(){
                    ddMultiple[id].currentField = jQuery(this).siblings(".ddField");
                    ddMultiple[id].browseFuntion(id);
                });
            }
        }else if (ddMultiple[id].coloumns[key] == "id"){
            if (!($field.val())){
                $field.val((new Date).getTime());
            }
            
            $col.hide();
        }else if(ddMultiple[id].coloumns[key] == "date"){
            $field.remove();
            ddMultiple.makeDate(val[key], ddMultiple[id].coloumnsTitle[key], $col);
        }else if(ddMultiple[id].coloumns[key] == "textarea"){
            $field.remove();
            ddMultiple.makeTextarea(val[key], ddMultiple[id].coloumnsTitle[key], ddMultiple[id].colWidth[key], $col);
        }else if(ddMultiple[id].coloumns[key] == "select"){
            $field.remove();
            ddMultiple.makeSelect(val[key], ddMultiple[id].coloumnsTitle[key], ddMultiple[id].coloumnsData[key], ddMultiple[id].colWidth[key], $col);
        }
    
    });

    //Create DeleteButton
    ddMultiple.makeDeleteButton(id, ddMultiple.makeFieldCol($fieldBlock));

    //При изменении и загрузке
    jQuery(".ddField", $fieldBlock).on("load.ddEvents change.ddEvents",function(){
        jQuery(this).parents(".ddMultipleField:first").trigger("change.ddEvents");
    });
    
    //Специально для полей, содержащих изображения необходимо инициализировать
    jQuery(".ddFieldCol:has(.ddField_image) .ddField", $fieldBlock).trigger("change.ddEvents");
    
    return $fieldBlock;
},
//Создание колонки поля
makeFieldCol: function(fieldRow){
    return jQuery("<td class=\"ddFieldCol\"></td>").appendTo(fieldRow);
},
//Make delete button
makeDeleteButton: function(id, fieldCol){
    jQuery("<input class=\"ddDeleteButton\" type=\"button\" value=\"×\" />").appendTo(fieldCol).on("click", function(){
        //Проверяем на минимальное количество строк
        if (ddMultiple[id].minRow && jQuery("#" + id + "ddMultipleField .ddFieldBlock").length <= ddMultiple[id].minRow){
            return;
        }
        
        var $this = jQuery(this),
            $par = $this.parents(".ddFieldBlock:first"),
            $table = $this.parents(".ddMultipleField:first");
        //Отчистим значения полей
        $par.find(".ddField").val("");

        //Если больше одной строки, то можно удалить текущую строчку
//			jQuery(".ddField_image").hide();alert(\'test\');
        if(jQuery("#" + id + "ddMultipleField .ddFieldBlock").length==1)
            jQuery(".ddField_image").hide();
        if ($par.siblings(".ddFieldBlock").length > 0){
            $par.fadeOut(300, function(){
                //Если контейнер имеет кнопку добалвения, перенесём её
                if ($par.find(".ddAddButton").length > 0){
                    ddMultiple.moveAddButton(id, $par.prev(".ddFieldBlock"));
                }
                $par.remove();
                //Инициализируем событие изменения
                $table.trigger("change.ddEvents");
                return;
            });
        }
        //Инициализируем событие изменения
        $table.trigger("change.ddEvents");
    });
},
//Функция создания кнопки +, вызывается при инициализации
makeAddButton: function(id){
    jQuery("<input class=\"ddAddButton\" type=\"button\" value=\"+\" />").appendTo(jQuery("#"+id+"ddMultipleField .ddFieldBlock:last .ddFieldCol:last")).on("click", function(){
        //Вешаем на кнопку создание новой строки
        jQuery(this).appendTo(ddMultiple.makeFieldRow(id, "").find(".ddFieldCol:last"));
        jQuery(this).parents(".ddMultipleField:first").trigger("change.ddEvents");
    });
},
//Перемещение кнопки +
moveAddButton: function(id, $target){
    //Если не передали, куда вставлять, вставляем в самый конец
    if (!$target){
        $target = jQuery("#" + id + "ddMultipleField .ddFieldBlock:last");
    }

    //Находим кнопку добавления и переносим куда надо
    jQuery("#"+id+"ddMultipleField .ddAddButton:first").appendTo($target.find(".ddFieldCol:last"));
},
//Make text field
makeText: function(value, title, width, $fieldCol){
    return jQuery("<input type=\"text\" value=\"" + value + "\" title=\"" + title + "\" style=\"width:" + width + "px;\" class=\"ddField\" />").appendTo($fieldCol);
},
//Make image field
makeImage: function(id, fieldCol){
    // Create a new preview and Attach a browse event to the picture, so it can trigger too
    jQuery("<div class=\"ddField_image\"><img src=\"\" style=\"" + ddMultiple[id].imageStyle + "\" /></div>").appendTo(fieldCol).hide().find("img").on("click", function(){
        fieldCol.find(".ddAttachButton").trigger("click");
    }).on("load.ddEvents", function(){
        //Удаление дерьма, блеать (превьюшка, оставленная от виджета showimagetvs)
        jQuery("#" + id + "PreviewContainer").remove();
    });

    //Находим поле, привязываем события
    jQuery(".ddField", fieldCol).on("change.ddEvents load.ddEvents", function(){
        var $this = jQuery(this), url = $this.val();

        url = (url != "" && url.search(/https?:\/\//i) == -1 && url.search(/^\//i) == -1) ? ("' . MODX_BASE_URL . '" + url) : url;

        //If field not empty
        if (url != ""){
            //Show preview
            $this.siblings(".ddField_image").show().find("img").prop("src", url);
        }else{
            //Hide preview
            $this.siblings(".ddField_image").hide();
        }
    })/*.trigger("change.ddEvents")*/;
},
//Make date field
makeDate: function(value, title, $fieldCol){
    //name нужен для DatePicker`а
    var $field = jQuery("<input type=\"text\" title=\"" + title + "\" class=\"ddField DatePicker\" name=\"ddMultipleDate\" />").val(value).appendTo($fieldCol);

    return $field;
},
//Make textarea field
makeTextarea: function(value, title, width, fieldCol){
    return jQuery("<textarea title=\"" + title + "\" style=\"width:" + width + "px;\" class=\"ddField\">" + value + "</textarea>").appendTo(fieldCol);
},
//Функция создания списка
makeSelect: function(value, title, data, width, fieldCol){
    var $select = jQuery("<select class=\"ddField\">");
    if (data){
        var dataMas = jQuery.parseJSON(data);
        var options = "";
        jQuery.each(dataMas, function(index){
            options += "<option value=\""+ dataMas[index][0] +"\">" + (dataMas[index][1] ? dataMas[index][1] : dataMas[index][0]) +"</option>";
        });
        $select.append(options);
    }
    if (value) $select.val(value);
    return $select.appendTo(fieldCol);
},
//Функция ничего не делает
makeNull: function(id, fieldCol){return false;},
//Маскирует кавычки
maskQuoutes: function(text){
    text = text.replace(/"/g, "&#34;");
    text = text.replace(/\'/g, "&#39;");
    return text;
}
};
//If we have imageTVs on this page, modify the SetUrl function so it triggers a "change" event on the URL field
jQuery(function(){
if (typeof(SetUrl) != "undefined") {
    var OldSetUrl = SetUrl; // Copy the existing Image browser SetUrl function
    SetUrl = function(url, width, height, alt){	// Redefine it to also tell the preview to update
        if(lastFileCtrl) {
            var c = jQuery(document.mutate[lastFileCtrl]);
        } else if(lastImageCtrl) {
            var c = jQuery(document.mutate[lastImageCtrl]);
        }
        OldSetUrl(url, width, height, alt);
        c.trigger("change");
    };
}
//Самбмит главной формы
jQuery("#mutate").on("submit", function(){
    for (var i = 0, len = ddMultiple.ids.length; i < len; i++){
        ddMultiple.updateTv(ddMultiple.ids[i]);
    }
});
});
}
';

    foreach ($tvsMas as $tv) {
        if ($tv['type'] === 'image') {
            $browseFuntion = 'BrowseServer';
            $makeFieldFunction = 'makeImage';
        } else if ($tv['type'] === 'file') {
            $browseFuntion = 'BrowseFileServer';
            $makeFieldFunction = 'makeNull';
        } else {
            $browseFuntion = 'false';
            $makeFieldFunction = 'makeNull';
        }
        $output .= '
//Attach new load event
jQuery("#tv' . $tv['id'] . '").on("load.ddEvents", function(event){
var $this = jQuery(this), //Оригинальное поле
    id = $this.prop("id");//id оригинального поля

//Проверим на существование (возникали какие-то непонятные варианты, при которых два раза вызов был)
if (!ddMultiple[id]){
    //Инициализация текущего объекта с правилами
    ddMultiple[id] = {
        splY: "' . $splY . '",
        splX: "' . $splX . '",
        coloumns: "' . $columns . '".split(","),
        coloumnsTitle: "' . $columnsTitle . '".split(","),
        coloumnsData: "' . $columnsData . '".split("||"),
        colWidth: "' . $colWidth . '".split(","),
        imageStyle: "' . $stylePrewiew . '",
        minRow: parseInt("' . $minRow . '", 10),
        maxRow: parseInt("' . $maxRow . '", 10),
        makeFieldFunction: ddMultiple.' . $makeFieldFunction . ',
        browseFuntion: ' . $browseFuntion . '
    };
    
    ddMultiple.ids.push(id);

    //Скрываем оригинальное поле
    $this.removeClass("imageField").addClass("originalField").hide();

    //Назначаем обработчик события при изменении (необходимо для того, чтобы после загрузки фотки адрес вставлялся в нужное место)
    $this.on("change.ddEvents", function(){
        //Обновляем текущее мульти-поле
        ddMultiple.updateField($this.prop("id"));
    });
    
    //Если это файл или изображение, cкрываем оригинальную кнопку
    if (ddMultiple[id].browseFuntion){$this.next("input[type=button]").hide();}

    //Создаём мульти-поле
    ddMultiple.init(id, $this.val(), $this.parent());
}
}).trigger("load");
        ';
    }

    $output .= "\n// ---------------- mm_ddMultipleFields :: End -------------";

    $e->output($output . "\n");
} // end of widget
