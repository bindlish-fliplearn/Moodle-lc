@mod @mod_hwork
Feature: Edit hwork page - remove multiple questions
  In order to change the layout of a hwork I built efficiently
  As a teacher
  I need to be able to delete many questions questions.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity   | name   | course | idnumber |
      | hwork       | Quiz 1 | C1     | hwork1    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"

  @javascript
  Scenario: Delete selected question using select multiple items feature.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
    And hwork "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And I navigate to "Edit hwork" in current page administration

    # Confirm the starting point.
    Then I should see "Question A" on hwork page "1"
    And I should see "Question B" on hwork page "1"
    And I should see "Question C" on hwork page "2"
    And I should see "Total of marks: 3.00"
    And I should see "Questions: 3"
    And I should see "This hwork is open"

    # Delete last question in last page. Page contains multiple questions. No reordering.
    When I click on "Select multiple items" "button"
    Then I click on "selectquestion-3" "checkbox"
    And I click on "Delete selected" "button"
    And I click on "Yes" "button" in the "Confirm" "dialogue"

    Then I should see "Question A" on hwork page "1"
    And I should see "Question B" on hwork page "1"
    And I should not see "Question C" on hwork page "2"
    And I should see "Total of marks: 2.00"
    And I should see "Questions: 2"

  @javascript
  Scenario: Delete first selected question using select multiple items feature.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
    And hwork "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 2    |
      | Question C | 2    |
    And I navigate to "Edit hwork" in current page administration

  # Confirm the starting point.
    Then I should see "Question A" on hwork page "1"
    And I should see "Question B" on hwork page "2"
    And I should see "Question C" on hwork page "2"
    And I should see "Total of marks: 3.00"
    And I should see "Questions: 3"
    And I should see "This hwork is open"

  # Delete first question in first page. Page contains multiple questions. No reordering.
    When I click on "Select multiple items" "button"
    Then I click on "selectquestion-1" "checkbox"
    And I click on "Delete selected" "button"
    And I click on "Yes" "button" in the "Confirm" "dialogue"

    Then I should not see "Question A" on hwork page "1"
    And I should see "Question B" on hwork page "1"
    And I should see "Question C" on hwork page "1"
    And I should see "Total of marks: 2.00"
    And I should see "Questions: 2"

  @javascript
  Scenario: Can delete the last question in a hwork.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
    And hwork "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
    When I navigate to "Edit hwork" in current page administration
    And I click on "Select multiple items" "button"
    And I click on "selectquestion-1" "checkbox"
    And I click on "Delete selected" "button"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    Then I should see "Questions: 0"

  @javascript
  Scenario: Delete all questions by checking select all.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
    And hwork "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And I navigate to "Edit hwork" in current page administration

  # Confirm the starting point.
    Then I should see "Question A" on hwork page "1"
    And I should see "Question B" on hwork page "1"
    And I should see "Question C" on hwork page "2"
    And I should see "Total of marks: 3.00"
    And I should see "Questions: 3"
    And I should see "This hwork is open"

  # Delete all questions in page. Page contains multiple questions
    When I click on "Select multiple items" "button"
    Then I click on "Select all" "link"
    And I click on "Delete selected" "button"
    And I click on "Yes" "button" in the "Confirm" "dialogue"

    Then I should not see "Question A" on hwork page "1"
    And I should not see "Question B" on hwork page "1"
    And I should not see "Question C" on hwork page "2"
    And I should see "Total of marks: 0.00"
    And I should see "Questions: 0"

  @javascript
  Scenario: Deselect all questions by checking deselect all.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
    And hwork "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And I navigate to "Edit hwork" in current page administration

  # Confirm the starting point.
    Then I should see "Question A" on hwork page "1"
    And I should see "Question B" on hwork page "1"
    And I should see "Question C" on hwork page "2"

  # Delete last question in last page. Page contains multiple questions
    When I click on "Select multiple items" "button"
    And I click on "Select all" "link"
    Then the field "selectquestion-3" matches value "1"

    When I click on "Deselect all" "link"
    Then the field "selectquestion-3" matches value "0"
