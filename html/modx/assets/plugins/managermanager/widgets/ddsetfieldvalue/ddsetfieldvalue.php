<?php
/** 
 * ddSetFieldValue
 * @version 1.0.3 (2012-11-13)
 * 
 * Жёстко выставляет необходимые значения заданному полю
 * 
 * @uses ManagerManager plugin 0.4.
 * 
 * @todo Основан на mm_default
 * 
 * @param field {string} - Имя поля, для которого необходимо установить значение.
 * @param value {string} - Значение, которое необходимо установить.
 * @param roles {comma separated string} - Id ролей. По умолчанию: для всех ролей.
 * @param templates {comma separated string} - Id шаблонов. По умолчанию: для всех шаблонов.
 * 
 * @link http://code.divandesign.biz/modx/mm_ddsetfieldvalue/1.0.3
 * 
 * @copyright 2012, DivanDesign
 * http://www.DivanDesign.biz
 */

function mm_ddSetFieldValue($field, $value = '', $roles = '', $templates = ''){
	global $modx, $content, $mm_fields, $mm_current_page;
	$e = &$modx->event;
	
	if ($e->name !== 'OnDocFormRender' || !useThisRule($roles, $templates)) {
        return;
    }

    $output = " // ----------- mm_ddSetFieldValue :: Begin -------------- \n";

    //Подбираем правильный формат даты в соответствии с конфигурацией
    switch ($modx->config['datetime_format']) {
        case 'dd-mm-YYYY':
            $date_format = 'd-m-Y';
            break;
        case 'mm/dd/YYYY':
            $date_format = 'm/d/Y';
            break;
        case 'YYYY/mm/dd':
            $date_format = 'Y/m/d';
            break;
    }

    //Смотрим, что за поле надо изменить
    switch ($field) {
        //Дата публикации
        case 'pub_date':
            if ($value == '') {
                $value = date($date_format . ' H:i:s');
            }
            $output .= '$j("input[name=pub_date]").val("' . jsSafe($value) . '"); ' . "\n";
            break;

        //Дата отмены публикации
        case 'unpub_date':
            if ($value == '') {
                $value = date($date_format . ' H:i:s');
            }
            $output .= '$j("input[name=unpub_date]").val("' . jsSafe($value) . '"); ' . "\n";
            break;

        //Признак публикации
        case 'published':
            if ($value == '1') {
                $output .= '$j("input[name=publishedcheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '$j("input[name=publishedcheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=published]").val("' . $value . '"); ' . "\n";
            break;

        //Признак отображения в меню
        case 'show_in_menu':
            if ($value == '1') {
                $output .= '$j("input[name=hidemenucheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '$j("input[name=hidemenucheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=hidemenu]").val("' . (($value == '1') ? '0' : '1') . '"); ' . "\n"; // Note these are reversed from what you'd think
            break;

        //Признак скрытия из меню (аналогично show_in_menu, только наоборот)
        case 'hide_menu':
            if ($value == '0') {
                $output .= '$j("input[name=hidemenucheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '1';
                $output .= '$j("input[name=hidemenucheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=hidemenu]").val("' . $value . '"); ' . "\n";
            break;

        //Признак доступности для поиска
        case 'searchable':
            if ($value == '1') {
                $output .= '$j("input[name=searchablecheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '$j("input[name=searchablecheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=searchable]").val("' . $value . '"); ' . "\n";
            break;

        //Признак кэширования
        case 'cacheable':
            if ($value == '1') {
                $output .= '$j("input[name=cacheablecheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '$j("input[name=cacheablecheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=cacheable]").val("' . $value . '"); ' . "\n";
            break;

        //Признак очистки кэша
        case 'clear_cache':
            if ($value == '1') {
                $output .= '$j("input[name=syncsitecheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '$j("input[name=syncsitecheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=syncsite]").val("' . $value . '"); ' . "\n";
            break;

        //Признак папки
        case 'is_folder':
            if ($value == '1') {
                $output .= '$j("input[name=isfoldercheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '$j("input[name=isfoldercheck]").removeAttr("checked"); ' . "\n";
            }
            break;

        //Признак использованшия визуального редактора
        case 'is_richtext':
            $output .= 'var originalRichtextValue = $j("#which_editor:first").val(); ' . "\n";

            if ($value == '1') {
                $output .= '$j("input[name=richtextcheck]").prop("checked", true); ' . "\n";
            } else {
                $value = '0';
                $output .= '
                            $j("input[name=richtextcheck]").removeAttr("checked");
                            // Make the RTE displayed match the default value that has been set here
                            if (originalRichtextValue != "none"){
                                $j("#which_editor").val("none");
                                changeRTE();
                            }
                                    
                            ';
                $output .= '' . "\n";
            }

            $output .= '$j("input[name=richtext]").val("' . $value . '"); ' . "\n";
            break;

        //Признак логирования
        case 'log':
            //Note these are reversed from what you'd think
            $value = ($value) ? '0' : '1';

            if ($value == '1') {
                $output .= '$j("input[name=donthitcheck]").prop("checked", true); ' . "\n";
            } else {
                $output .= '$j("input[name=donthitcheck]").removeAttr("checked"); ' . "\n";
            }

            $output .= '$j("input[name=donthit]").val("' . $value . '"); ' . "\n";
            break;

        //Тип содержимого
        case 'content_type':
            $output .= '$j("select[name=contentType]").val("' . $value . '");' . "\n";
            break;

        //TV
        default:
            // Which template is this page using?

            $tvsMas = tplUseTvs($mm_current_page['template'], $field);

            if ($tvsMas) {
                $output .= '$j("#tv' . $tvsMas[0]['id'] . '").val("' . $value . '");' . "\n";
            }
            break;
    }

    $output .= "\n// ---------------- mm_ddSetFieldValue :: End -------------";

    $e->output($output . "\n");
}
