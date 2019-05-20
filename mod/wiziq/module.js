/**
* This old YUI was is deprecated. 
 * But this is how you could run JS on the mod_wiziq settings page 
 * without breaking the no end php flag Moodle guideline.
*
* jquery is already defined on the page the jQuery calls do run.
*/



/**
 * @namespace
 */
M.mod_wiziq = M.mod_wiziq || {};

/**
 * This function is initialized from PHP
 *
 * @param {Object} Y YUI instance
 */
M.mod_wiziq.init = function(Y) {
   
   $("#id_s__wiziq_access_key").prop('required',true);
   $("#id_s__wiziq_secretacesskey").prop('required',true);


                $('input[type="radio"]').click(function() {
                    if ($(this).attr("value") == "0") { // Permanent class
                        $("#id_wiziqdatetimesetting").hide();
                        $("#id_wiziqrecurringclasssettings").hide();
                        $("#id_class_occurrence").prop('required', false);
                    }
                    if ($(this).attr("value") == "1") { // Schedule class
                        $("#id_wiziqdatetimesetting").show();
                        $("#fitem_id_schedule_for_now").show();
                        $("#id_wiziqrecurringclasssettings").hide();
                        $("#id_class_occurrence").prop('required', false);
                    }
                    if ($(this).attr("value") == "2") { // Recurring Class
                        $("#id_wiziqdatetimesetting").show();
                        $("#id_wiziqrecurringclasssettings").show();
                        $("#fitem_id_schedule_for_now").hide();
                        $("#id_class_occurrence").prop('required', true);
                    }
                    if ($(this).attr("value") == "3") { // Class occurance
                        $("#fitem_id_class_occurrence").show();
                        $("#fitem_id_assesstimefinish").hide();
                        if ($('#id_class_type_2').attr('checked', true)) {
                            $("#id_class_occurrence").prop('required', true);
                        }
                    }
                    if ($(this).attr("value") == "4") { // class end date
                        $("#fitem_id_class_occurrence").hide();
                        $("#fitem_id_assesstimefinish").show();
                        $("#id_class_occurrence").prop('required', false);
                    }
                });

                if ($('#id_class_type_2').attr('checked', false)) {
                    $("#id_wiziqrecurringclasssettings").hide();
                }
                if ($('#id_class_schedule_3').attr('checked', true)) {
                    $("#fitem_id_assesstimefinish").hide();
                    $("#id_wiziqrecurringclasssettings").removeClass('collapsed');
                }
                $("#id_wiziq_recur_class_repeat_type").change(function() {
                    if ($(this).val() == '4') { // Weekly
                        $("#fitem_id_specific_week").show();
                        $("#fitem_id_days_of_week").show();
                        $("#fitem_id_select_monthly_repeat_type").hide();
                        $("#fitem_id_monthly_date").hide();
                        $("#id_specific_week").attr('required', true);
                    } else if ($(this).val() == '5') { // once every month
                        $("#fitem_id_select_monthly_repeat_type").show();
                        $("#fitem_id_monthly_date").show();
                        $("#fitem_id_specific_week").hide();
                        $("#fitem_id_days_of_week").hide();
                        $("#id_specific_week").attr('required', false);
                    }
                    else {
                        $("#fitem_id_specific_week").hide();
                        $("#fitem_id_days_of_week").hide();
                        $("#fitem_id_select_monthly_repeat_type").hide();
                        $("#fitem_id_monthly_date").hide();
                        $("#id_specific_week").attr('required', false);
                    }
                });
                $("#id_select_monthly_repeat_type").change(function() {
                    if ($(this).val() == 'byday') {
                        $("#fitem_id_days_of_week").show();
                    } else {
                        $("#fitem_id_days_of_week").hide();
                    }
                });
                $("#fitem_id_specific_week").hide();
                $("#fitem_id_days_of_week").hide();
                $("#fitem_id_select_monthly_repeat_type").hide();
                $("#fitem_id_monthly_date").hide();     



                  

    
    function randomNumber() {
        return Math.floor(Math.random()*101)
    }
    

          
       
}


  