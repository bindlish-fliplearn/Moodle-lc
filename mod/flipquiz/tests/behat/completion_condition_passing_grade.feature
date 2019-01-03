@mod @mod_flipquiz
Feature: Set a flipquiz to be marked complete when the student passes
  In order to ensure a student has learned the material before being marked complete
  As a teacher
  I need to set a flipquiz to complete when the student recieves a passing grade

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | grade_item_advanced | hiddenuntil |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext              |
      | Test questions   | truefalse | First question | Answer the first question |
    And the following "activities" exist:
      | activity   | name           | course | idnumber | attempts | gradepass | completion | completionpass |
      | flipquiz       | Test flipquiz name | C1     | flipquiz1    | 4        | 5.00      | 2          | 1              |
    And flipquiz "Test flipquiz name" contains the following questions:
      | question       | page |
      | First question | 1    |

  Scenario: student1 passes on the first try
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test flipquiz name" "flipquiz" activity with "auto" completion should be marked as not complete
    And I follow "Test flipquiz name"
    And I press "Attempt flipquiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I am on "Course 1" course homepage
    Then "Completed: Test flipquiz name" "icon" should exist in the "li.modtype_flipquiz" "css_element"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    And "Completed" "icon" should exist in the "Student 1" "table_row"