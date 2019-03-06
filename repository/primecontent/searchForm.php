<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/../repository/lib.php');
$itemid = optional_param('itemid', '', PARAM_RAW);
$client_id = optional_param('client_id', '', PARAM_RAW);
$sesskey = optional_param('sesskey', '', PARAM_RAW);
$ctx_id = optional_param('ctx_id', '', PARAM_RAW);
?>
<link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/repository/primecontent/pix/style.css">

<div id="search_class_subject">
    <div class="fp-login-form" id="yui_3_17_2_1_1549621888968_2766">
        <div class="fp-content-center">
            <div class="fp-formset">
                <div class="fp-login-select control-group clearfix" id="yui_3_17_2_1_1549621888968_2775">
                    <label class="control-label" for="primecontent_class">Search for:</label>
                    <div class="controls">
                        <select id="primecontent_class" name="primecontent_class">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>
                <div class="fp-login-select control-group clearfix" id="yui_3_17_2_1_1549621888968_2795">
                    <label class="control-label" for="primecontent_subject">Search for:</label>
                    <div class="controls">
                        <select id="primecontent_subject" name="primecontent_subject">
                            <option value="">Select subject</option>
                        </select>
                    </div>
                </div>
            </div>
            <p><button class="fp-login-submit btn-primary btn" id="classSubjectButton">Submit</button></p>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo $CFG->wwwroot; ?>/lib/jquery/jquery-3.2.1.min.js"></script>
<script type="text/javascript">
  itemid = <?php echo $itemid; ?>;
  client_id = "<?php echo $client_id; ?>";
  sesskey = "<?php echo $sesskey; ?>";
  ctx_id = "<?php echo $ctx_id; ?>";
  baseUrl = "<?php echo $CFG->wwwroot; ?>";
  primeUrl = "<?php echo PRIME_URL; ?>";
</script>
<script src="<?php echo $CFG->wwwroot; ?>/repository/primecontent/pix/primecontent.js"></script>