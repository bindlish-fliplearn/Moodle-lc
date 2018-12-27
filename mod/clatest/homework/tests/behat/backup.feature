@mod @mod_homework
Feature: Backup and restore of homeworkzes
  In order to reuse my homeworkzes
  As a teacher
  I need to be able to back them up and restore them.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And I log in as "admin"

  @javascript
  Scenario: Duplicate a homework with two questions
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | homework       | Quiz 1 | For testing backup | C1     | homework1    |
    And the following "questions" exist:
      | questioncategory | qtype       | name | questiontext    |
      | Test questions   | truefalse   | TF1  | First question  |
      | Test questions   | truefalse   | TF2  | Second question |
    And homework "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
      | TF2      | 2    |
    When I am on "Course 1" course homepage with editing mode on
    And I duplicate "Quiz 1" activity editing the new copy with:
      | Name | Quiz 2 |
    And I follow "Quiz 2"
    And I navigate to "Edit homework" in current page administration
    Then I should see "TF1"
    And I should see "TF2"

  @javascript @_file_upload
  Scenario: Restore a Moodle 2.8 homework backup
    When I am on "Course 1" course homepage
    And I navigate to "Restore" node in "Course administration"
    And I press "Manage backup files"
    And I upload "mod/homework/tests/fixtures/moodle_28_homework.mbz" file to "Files" filemanager
    And I press "Save changes"
    And I restore "moodle_28_homework.mbz" backup into "Course 1" course using this options:
    And I follow "Restored Moodle 2.8 homework"
    And I navigate to "Edit homework" in current page administration
    Then I should see "TF1"
    And I should see "TF2"
