<?php
/**
 * mm_requireFields
 * @version 1.2 (2013-02-11)
 * 
 * Make fields required. Currently works with text fields only.
 * In the future perhaps this could deal with other elements.
 * Originally version by Jelle Jager AKA TobyL - Make fields required
 * Updated by ncrossland to utilise simpler field handline of MM 0.3.5+; bring jQuery code into line; add indication to required fields
 * 
 * @uses ManagerManager plugin 0.4.
 * 
 * @link http://code.divandesign.biz/modx/mm_requirefields/1.2
 * 
 * @copyright 2013
 */

function mm_requireFields($fields, $roles='', $templates=''){
	global $mm_fields, $mm_current_page, $modx;
	$e = &$modx->event;
	
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name !== 'OnDocFormRender' || !useThisRule($roles, $templates)) {
        return;
    }

// if we've been supplied with a string, convert it into an array
    $fields = makeArray($fields);

    if (count($fields) == 0) return;

    $output = "//  -------------- mm_requireFields :: Begin ------------- \n";

    $output .= '
    jQuery("head").append("<style>.mmRequired { background-image: none !important; background-color: #ff9999 !important; } .requiredIcon { color: #ff0000; font-weight: bold; margin-left: 3px; cursor: help; }</style>");
    var requiredHTML = "<span class=\"requiredIcon\" title=\"Required\">*</span>";
    ';

    $submit_js = '';
    $load_js = '';

    foreach ($fields as $field) {
        //ignore for now
        switch ($field) {
            // fields for which this doesn't make sense - in my opinion anyway :)
            case 'keywords':
            case 'metatags':
            case 'hidemenu':
            case 'which_editor':
            case 'template':
            case 'menuindex':
            case 'show_in_menu':
            case 'parent':
            case 'is_folder':
            case 'is_richtext':
            case 'log':
            case 'searchable':
            case 'cacheable':
            case 'clear_cache':
            case 'content_type':
            case 'content_dispo':
                $output .= '';
                break;

            // Pub/unpub dates don't have a type attribute on their input tag in 1.0.2, so add this. Won't do any harm to other versions
            case 'pub_date':
            case 'unpub_date':
                $load_js .= '
                jQuery("#pub_date, #unpub_date").each(function() { this.type = "text";  }); // Cant use jQuery attr function as datepicker class clashes with jQuery methods
                ';
            // no break, because we want to do the things below too.

            // Ones that follow the regular pattern
            default:
                //if it's tv & it's not used in current template
                if ($mm_fields[$field]['tv'] && tplUseTvs($mm_current_page['template'], $field) === false) {
                    //Go to next field
                    break;
                }

                // What type is this field?
                $fieldname = $mm_fields[$field]['fieldname'];

                // What jQuery selector should we use for this fieldtype?
                switch ($mm_fields[$field]['fieldtype']) {
                    case 'textarea':
                        $selector = "textarea[name=$fieldname]";
                        break;

                    case 'input': // If it's an input, we only want to do something if it's a text field
                        $selector = "input[type=text][name=$fieldname]";
                        break;

                    case 'select':
                        $selector = "select[name=$fieldname]";
                        break;

                    default:  // all other input types, do nothing
                        $selector = '';
                        break;
                }

                // If we've found something we want to use
                if (!empty($selector)) {
                    $submit_js .= '

// The element we are targetting (' . $fieldname . ')
var $sel = jQuery("' . $selector . '");

// Check if its valid
if(jQuery.trim($sel.val()) == ""){  // If it is empty

// Find the label (this will be easier in Evo 1.1 with more semantic code)
var lbl = $sel.parent("td").prev("td").children("span.warning").text().replace(jQuery(requiredHTML).text(), "");

// Add the label to the errors array. Would be nice to say which tab it is on, but no
// easy way of doing this in 1.0.x as no semantic link between tabs and tab body
errors.push(lbl);

// Add an event so the hilight is removed upon focussing
$sel.addClass("mmRequired").focus(function(){
jQuery(this).removeClass("mmRequired");
});
}
                    ';

                    $load_js .= '

// Add an indicator this is required (' . $fieldname . ')
var $sel = jQuery("' . $selector . '");

// Find the label (this will be easier in Evo 1.1 with more semantic code)
var $lbl = $sel.parent("td").prev("td").children("span.warning").append(requiredHTML);

                    ';
                }
                break;
        }
    }

    $output .= $load_js . '
jQuery("#mutate").submit(function(){
var errors = [];
//TODO: The local variable msg is never read
var msg = "";

' . $submit_js . '

if(errors.length > 0){
    var errMsg = errors.length + " required fields are missing:\n\n ";
    
    for (var i = 0; i < errors.length; i++){
        errMsg += " - " + errors[i] + " \n";
    }
    errMsg += " \nPlease correct the indicated fields.";
    
    alert(errMsg);
    return false;
}else{
    return true;
}
});
    ';

    $output .= "//  -------------- mm_requireFields :: End ------------- \n";

    $e->output($output . "\n");
}
