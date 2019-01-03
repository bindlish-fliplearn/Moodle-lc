@mod @mod_pratest
Feature: The various checks that may happen when an attept is started
  As a student
  In order to start a pratest with confidence
  I need to be waned if there is a time limit, or various similar things

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Student   | One      | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student  | C1     | student |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext               |
      | Test questions   | truefalse   | TF1   | Text of the first question |

  @javascript
  Scenario: Start a pratest with no time limit
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | pratest       | Quiz 1 | Quiz 1 description | C1     | pratest1    |
    And pratest "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt pratest now"
    Then I should see "Text of the first question"

  @javascript
  Scenario: Start a pratest with time limit and password
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | timelimit | pratestpassword |
      | pratest       | Quiz 1 | Quiz 1 description | C1     | pratest1    | 3600      | Frog         |
    And pratest "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt pratest now"
    Then I should see "To attempt this pratest you need to know the pratest password" in the "Start attempt" "dialogue"
    And I should see "The pratest has a time limit of 1 hour. Time will " in the "Start attempt" "dialogue"
    And I set the field "Quiz password" to "Frog"
    And I click on "Start attempt" "button" in the "Start attempt" "dialogue"
    And I should see "Text of the first question"

  @javascript
  Scenario: Cancel starting a pratest with time limit and password
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | timelimit | pratestpassword |
      | pratest       | Quiz 1 | Quiz 1 description | C1     | pratest1    | 3600      | Frog         |
    And pratest "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt pratest now"
    And I click on "Cancel" "button" in the "Start attempt" "dialogue"
    Then I should see "Quiz 1 description"
    And "Attempt pratest now" "button" should be visible

  @javascript
  Scenario: Start a pratest with time limit and password, get the password wrong first time
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | timelimit | pratestpassword |
      | pratest       | Quiz 1 | Quiz 1 description | C1     | pratest1    | 3600      | Frog         |
    And pratest "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt pratest now"
    And I set the field "Quiz password" to "Toad"
    And I click on "Start attempt" "button" in the "Start attempt" "dialogue"
    Then I should see "Quiz 1 description"
    And I should see "To attempt this pratest you need to know the pratest password"
    And I should see "The pratest has a time limit of 1 hour. Time will "
    And I should see "The password entered was incorrect"
    And I set the field "Quiz password" to "Frog"
    # On Mac/FF tab key is needed as text field in dialogue and page have same id.
    And I press tab key in "Quiz password" "field"
    And I press "Start attempt"
    And I should see "Text of the first question"

  @javascript
  Scenario: Start a pratest with time limit and password, get the password wrong first time then cancel
    Given the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | timelimit | pratestpassword |
      | pratest       | Quiz 1 | Quiz 1 description | C1     | pratest1    | 3600      | Frog         |
    And pratest "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |
    When I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Attempt pratest now"
    And I set the field "Quiz password" to "Toad"
    And I click on "Start attempt" "button" in the "Start attempt" "dialogue"
    And I should see "Quiz 1 description"
    And I should see "To attempt this pratest you need to know the pratest password"
    And I should see "The pratest has a time limit of 1 hour. Time will "
    And I should see "The password entered was incorrect"
    And I set the field "Quiz password" to "Frog"
    # On Mac/FF tab key is needed as text field in dialogue and page have same id.
    And I press tab key in "Quiz password" "field"
    And I press "Cancel"
    Then I should see "Quiz 1 description"
    And "Attempt pratest now" "button" should be visible