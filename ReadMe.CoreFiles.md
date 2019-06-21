# List of changes done in core files

## Quiz

1.For remove the 3rd “Submit all and Finish” button from a quiz

File Name : mod/quiz/renderer.php
Specifically, in mod/quiz/renderer.php, in the summary_page_controls method, remove the bit about

$button->add_action(new confirm_action(
Perhaps the easiest way to do that is to change the if statement just before it from

if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
to

if (false && $attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {

2. Skip Attempt quiz now screen 
File Name mod/quiz/view.php
line no : 211 and 197



## Course

Add teacher dashboard link 
file name: /lib/navigationlib.php 
function name : add_course_essentials





## Admin

