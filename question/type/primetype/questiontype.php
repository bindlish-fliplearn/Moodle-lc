<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The questiontype class for the multiple choice question type.
 *
 * @package    qtype
 * @subpackage primetype
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
//require_once($CFG->dirroot . '/question/type/primetype/lib.php');

/**
 * The multiple choice question type.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_primetype extends question_type {
    public function get_question_options($question) {
        global $DB, $OUTPUT;
        $question->options = $DB->get_record('qtype_primetype_options',
                array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_question_options($question) {
        global $DB;
        $context = $question->context;
        $result = new stdClass();

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // Following hack to check at least two answers exist.
        $answercount = 0;
        foreach ($question->answer as $key => $answer) {
            if ($answer != '') {
                $answercount++;
            }
        }
        if ($answercount < 2) { // Check there are at lest 2 answers for multiple choice.
            $result->notice = get_string('notenoughanswers', 'qtype_primetype', '2');
            return $result;
        }

        // Insert all the new answers.
        $totalfraction = 0;
        $maxfraction = -1;
        foreach ($question->answer as $key => $answerdata) {
            if (trim($answerdata['text']) == '') {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            // Doing an import.
            $answer->answer = $this->import_or_save_files($answerdata,
                    $context, 'question', 'answer', $answer->id);
            $answer->answerformat = $answerdata['format'];
            $answer->fraction = $question->fraction[$key];
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];

            $DB->update_record('question_answers', $answer);

            if ($question->fraction[$key] > 0) {
                $totalfraction += $question->fraction[$key];
            }
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $options = $DB->get_record('qtype_primetype_options', array('questionid' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('qtype_primetype_options', $options);
        }

        $options->single = $question->single;
        if (isset($question->layout)) {
            $options->layout = $question->layout;
        }
        $options->answernumbering = $question->answernumbering;
        $options->shuffleanswers = $question->shuffleanswers;
        $options = $this->save_combined_feedback_helper($options, $question, $context, true);
        $DB->update_record('qtype_primetype_options', $options);

        $this->save_hints($question, true);

        // Perform sanity checks on fractional grades.
        if ($options->single) {
            if ($maxfraction != 1) {
                $result->noticeyesno = get_string('fractionsnomax', 'qtype_primetype',
                        $maxfraction * 100);
                return $result;
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $result->noticeyesno = get_string('fractionsaddwrong', 'qtype_primetype',
                        $totalfraction * 100);
                return $result;
            }
        }
    }

    protected function make_question_instance($questiondata) {
        question_bank::load_question_definition_classes($this->name());
        if ($questiondata->options->single) {
            $class = 'qtype_primetype_single_question';
        } else {
            $class = 'qtype_primetype_multi_question';
        }
        return new $class();
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->shuffleanswers = $questiondata->options->shuffleanswers;
        $question->answernumbering = $questiondata->options->answernumbering;
        if (!empty($questiondata->options->layout)) {
            $question->layout = $questiondata->options->layout;
        } else {
            $question->layout = qtype_primetype_single_question::LAYOUT_VERTICAL;
        }
        $this->initialise_combined_feedback($question, $questiondata, true);

        $this->initialise_question_answers($question, $questiondata, false);
    }

    public function make_answer($answer) {
        // Overridden just so we can make it public for use by question.php.
        return parent::make_answer($answer);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_primetype_options', array('questionid' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        if (!$questiondata->options->single) {
            // Pretty much impossible to compute for _multi questions. Don't try.
            return null;
        }

        // Single choice questions - average choice fraction.
        $totalfraction = 0;
        foreach ($questiondata->options->answers as $answer) {
            $totalfraction += $answer->fraction;
        }
        return $totalfraction / count($questiondata->options->answers);
    }

    public function get_possible_responses($questiondata) {
        if ($questiondata->options->single) {
            $responses = array();

            foreach ($questiondata->options->answers as $aid => $answer) {
                $responses[$aid] = new question_possible_response(
                        question_utils::to_plain_text($answer->answer, $answer->answerformat),
                        $answer->fraction);
            }

            $responses[null] = question_possible_response::no_response();
            return array($questiondata->id => $responses);
        } else {
            $parts = array();

            foreach ($questiondata->options->answers as $aid => $answer) {
                $parts[$aid] = array($aid => new question_possible_response(
                        question_utils::to_plain_text($answer->answer, $answer->answerformat),
                        $answer->fraction));
            }

            return $parts;
        }
    }

    /**
     * @return array of the numbering styles supported. For each one, there
     *      should be a lang string answernumberingxxx in teh qtype_primetype
     *      language file, and a case in the switch statement in number_in_style,
     *      and it should be listed in the definition of this column in install.xml.
     */
    public static function get_numbering_styles() {
        $styles = array();
        foreach (array('abc', 'ABCD', '123', 'iii', 'IIII', 'none') as $numberingoption) {
            $styles[$numberingoption] =
                    get_string('answernumbering' . $numberingoption, 'qtype_primetype');
        }
        return $styles;
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid, true);
        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid, true);
        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }
    
    /**
     * Provide export functionality for xml format.
     *
     * @param question object the question object
     * @param format object the format object so that helper methods can be used
     * @param extra mixed any additional format specific data that may be passed by the format (see
     *        format code for info)
     *
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_xml_old($question, qformat_xml $format, $extra = null) {
      print_r($question);die;
         //global $CFG, $OUTPUT;
        raise_memory_limit(MEMORY_EXTRA); 
        $invalidquestion = false;//
        
        $fs = get_file_storage();
        $contextid = $question->contextid;
        // Get files used by the questiontext.
        /*$question->questiontextfiles = $fs->get_area_files(
                $contextid, 'question', 'questiontext', $question->id);
        // Get files used by the generalfeedback.
        $question->generalfeedbackfiles = $fs->get_area_files(
                $contextid, 'question', 'generalfeedback', $question->id);
        if (!empty($question->options->answers)) {
            foreach ($question->options->answers as $answer) {
                $answer->answerfiles = $fs->get_area_files(
                        $contextid, 'question', 'answer', $answer->id);
                $answer->feedbackfiles = $fs->get_area_files(
                        $contextid, 'question', 'answerfeedback', $answer->id);
            }
        }*/

        $expout = '';

        // Add a comment linking this to the original question id.
        $expout .= "<!-- question: {$question->id}  -->\n";

        // Check question type.
        $questiontype = $question->qtype;

        // Categories are a special case.
        /*if ($question->qtype == 'category') {
            $categorypath = $format->writetext($question->category);
            $expout .= "  <question type=\"category\">\n";
            $expout .= "    <category>\n";
            $expout .= "        {$categorypath}\n";
            $expout .= "    </category>\n";
            $expout .= "  </question>\n";
            return $expout;
        }*/

        // Now we know we are are handing a real question.
        // Output the generic information.
        $expout .= "  <question type=\"{$questiontype}\">\n";
        $expout .= "    <name>\n";
        $expout .= $format->writetext($question->name, 3);
        $expout .= "    </name>\n";
        $expout .= "    <questiontext {$format->format($question->questiontextformat)}>\n";
        $expout .= $format->writetext($question->questiontext, 3);
        $expout .= $format->write_files($question->questiontextfiles);
        $expout .= "    </questiontext>\n";
        $expout .= "    <generalfeedback {$format->format($question->generalfeedbackformat)}>\n";
        $expout .= $format->writetext($question->generalfeedback, 3);
        $expout .= $format->write_files($question->generalfeedbackfiles);
        $expout .= "    </generalfeedback>\n";
        if ($question->qtype != 'multianswer') {
            $expout .= "    <defaultgrade>{$question->defaultmark}</defaultgrade>\n";
        }
        $expout .= "    <penalty>{$question->penalty}</penalty>\n";
        $expout .= "    <hidden>{$question->hidden}</hidden>\n";

        // The rest of the output depends on question type.
//        switch($question->qtype) {
//            
//              case 'primetype':
                $expout .= "    <single>" . $format->get_single($question->options->single) .
                        "</single>\n";
                $expout .= "    <shuffleanswers>" .
                        $format->get_single($question->options->shuffleanswers) .
                        "</shuffleanswers>\n";
                $expout .= "    <answernumbering>" . $question->options->answernumbering .
                        "</answernumbering>\n";
                $expout .= $format->write_combined_feedback($question->options, $question->id, $question->contextid);
                $expout .= $format->write_answers($question->options->answers);
//              break;
//          default:
//              // Try support by optional plugin.
//              /*if (!$data = $format->try_exporting_using_qtypes($question->qtype, $question)) {
//                  $invalidquestion = true;
//              } else {
//                  $expout .= $data;
//              }*/
//      }

        // Output any hints.
        //$expout .= $format->write_hints($question);

        // Write the question tags.
      /*  if (core_tag_tag::is_enabled('core_question', 'question')) {
            $tagobjects = core_tag_tag::get_item_tags('core_question', 'question', $question->id);

            if (!empty($tagobjects)) {
                $context = context::instance_by_id($contextid);
                $sortedtagobjects = question_sort_tags($tagobjects, $context, [$format->course]);

                if (!empty($sortedtagobjects->coursetags)) {
                    // Set them on the form to be rendered as existing tags.
                    $expout .= "    <coursetags>\n";
                    foreach ($sortedtagobjects->coursetags as $coursetag) {
                        $expout .= "      <tag>" . $format->writetext($coursetag, 0, true) . "</tag>\n";
                    }
                    $expout .= "    </coursetags>\n";
                }

                if (!empty($sortedtagobjects->tags)) {
                    $expout .= "    <tags>\n";
                    foreach ($sortedtagobjects->tags as $tag) {
                        $expout .= "      <tag>" . $format->writetext($tag, 0, true) . "</tag>\n";
                    }
                    $expout .= "    </tags>\n";
                }
            }
        }*/

        // Close the question tag.
        $expout .= "  </question>\n";
//        if ($invalidquestion) {
//            return '';
//        } else {
            return $expout;
//        }
    }
    
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
         //global $CFG, $OUTPUT;
        raise_memory_limit(MEMORY_EXTRA); 
        $invalidquestion = false;//
        
        $fs = get_file_storage();
        $contextid = $question->contextid;

        $expout = '';

        // Add a comment linking this to the original question id.
        $expout .= "<!-- question: {$question->id}  -->\n";

        // Check question type.
        $questiontype = $question->qtype;


        // Now we know we are are handing a real question.
        // Output the generic information.
        //$expout .= "  <question type=\"{$questiontype}\">\n";
//        $expout .= "    <name>\n";
//        $expout .= $format->writetext($question->name, 3);
//        $expout .= "    </name>\n";
//        $expout .= "    <questiontext {$format->format($question->questiontextformat)}>\n";
//        $expout .= $format->writetext($question->questiontext, 3);
//        $expout .= $format->write_files($question->questiontextfiles);
//        $expout .= "    </questiontext>\n";
//        $expout .= "    <generalfeedback {$format->format($question->generalfeedbackformat)}>\n";
//        $expout .= $format->writetext($question->generalfeedback, 3);
//        $expout .= $format->write_files($question->generalfeedbackfiles);
//        $expout .= "    </generalfeedback>\n";
//        if ($question->qtype != 'multianswer') {
//            $expout .= "    <defaultgrade>{$question->defaultmark}</defaultgrade>\n";
//        }
//        $expout .= "    <penalty>{$question->penalty}</penalty>\n";
//        $expout .= "    <hidden>{$question->hidden}</hidden>\n";

        // The rest of the output depends on question type.
//        switch($question->qtype) {
//            
//              case 'primetype':
        $expout .= "    <single>" . $format->get_single($question->options->single) .
                "</single>\n";
        $expout .= "    <shuffleanswers>" .
                $format->get_single($question->options->shuffleanswers) .
                "</shuffleanswers>\n";
        $expout .= "    <answernumbering>" . $question->options->answernumbering .
                "</answernumbering>\n";
        $expout .= $format->write_combined_feedback($question->options, $question->id, $question->contextid);
        $expout .= $format->write_answers($question->options->answers);

        //$expout .= "  </question>\n";
        return $expout;
    }
    
    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
      // Get common parts.
        $qo = $format->import_headers($data);
        //echo $qo->questiontextformat; die;
        // Header parts particular to primetype.
        $qo->qtype = 'primetype';
        $single = $format->getpath($data, array('#', 'single', 0, '#'), 'true');
        $qo->single = $format->trans_single($single);
        $shuffleanswers = $format->getpath($data,
                array('#', 'shuffleanswers', 0, '#'), 'false');
        $qo->answernumbering = $format->getpath($data,
                array('#', 'answernumbering', 0, '#'), 'abc');
        $qo->shuffleanswers = $format->trans_single($shuffleanswers);

        // There was a time on the 1.8 branch when it could output an empty
        // answernumbering tag, so fix up any found.
        if (empty($qo->answernumbering)) {
            $qo->answernumbering = 'abc';
        }

        //print_r($data['#']['question'][0]['#']['answer']);die("hhhhh");
        // Run through the answers.
        $answers = $data['#']['answer'];
        //print_r($answers); die;
        //$answers = $data['#']['question'][0]['#']['answer'];
        
        $acount = 0;
        //print_r($answers);die("jjjjjjjjjj");
        foreach ($answers as $answer) {
            //$ans = $format->import_answer($answer, true, $format->get_format($qo->datatextformat));
            $ans = $format->import_answer($answer, true, "html");
            $qo->answer[$acount] = $ans->answer;
            $qo->fraction[$acount] = $ans->fraction;
            $qo->feedback[$acount] = $ans->feedback;
            ++$acount;
        }

        $format->import_combined_feedback($qo, $data, true);
        $format->import_hints($qo, $data, true, false, "html");
        //$format->import_hints($qo, $data, true, false, $this->get_format($qo->datatextformat));

        return $qo;
    }
    
    /*public function import_from_xml($question, $qo, qformat_xml $format, $extra = null) {
      // Get common parts.
        $qo = $format->import_headers($question);
        //echo $qo->questiontextformat; die;
        // Header parts particular to primetype.
        $qo->qtype = 'primetype';
        $single = $format->getpath($question, array('#', 'single', 0, '#'), 'true');
        $qo->single = $format->trans_single($single);
        $shuffleanswers = $format->getpath($question,
                array('#', 'shuffleanswers', 0, '#'), 'false');
        $qo->answernumbering = $format->getpath($question,
                array('#', 'answernumbering', 0, '#'), 'abc');
        $qo->shuffleanswers = $format->trans_single($shuffleanswers);

        // There was a time on the 1.8 branch when it could output an empty
        // answernumbering tag, so fix up any found.
        if (empty($qo->answernumbering)) {
            $qo->answernumbering = 'abc';
        }

        print_r($question['#']);die("hhhhh");
        // Run through the answers.
        $answers = $question['#']['answer'];
        //print_r($answers);die("lllllll");
        
        $acount = 0;
        print_r($answers);die("jjjjjjjjjj");
        foreach ($answers as $answer) {
            //$ans = $format->import_answer($answer, true, $format->get_format($answer->questiontextformat));
            $ans = $format->import_answer($answer, true, "html");
            $qo->answer[$acount] = $ans->answer;
            $qo->fraction[$acount] = $ans->fraction;
            $qo->feedback[$acount] = $ans->feedback;
            ++$acount;
        }

        $format->import_combined_feedback($qo, $question, true);
        $format->import_hints($qo, $question, true, false, "html");
        //$format->import_hints($qo, $question, true, false, $this->get_format($answer->questiontextformat));

        return $qo;
    }*/
}
