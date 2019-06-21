# List of changes done in core files

## Quiz
For remove the 3rd “Submit all and Finish” button from a quiz
Specifically, in mod/quiz/renderer.php, in the summary_page_controls method, remove the bit about

$button->add_action(new confirm_action(
Perhaps the easiest way to do that is to change the if statement just before it from

if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
to

if (false && $attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {

## Course

## Admin

