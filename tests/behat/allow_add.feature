@availability @availability_playerhud @javascript
Feature: PlayerHUD restriction type is only offered when the block exists in the course
  As a teacher
  I should only be able to add the PlayerHUD availability restriction
  when a PlayerHUD block is present in the course

  Background:
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name      |
      | page     | C1     | Test Page |

  Scenario: The PlayerHUD restriction option appears when the block is present
    Given a PlayerHUD block exists in course "C1" with 100 XP per level
    And I log in as "teacher1"
    And I am on the "Test Page" "page activity editing" page
    And I expand "Restrict access" fieldset
    When I click on "Add restriction..." "button"
    Then I should see "PlayerHUD"

  Scenario: The PlayerHUD restriction option is absent when the block is not in the course
    Given I log in as "teacher1"
    And I am on the "Test Page" "page activity editing" page
    And I expand "Restrict access" fieldset
    When I click on "Add restriction..." "button"
    Then I should not see "PlayerHUD"
