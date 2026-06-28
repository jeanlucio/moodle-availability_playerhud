@availability @availability_playerhud @javascript
Feature: Level-based availability restriction controls student access
  As a student
  I should be blocked from an activity when my PlayerHUD level is too low
  and gain access once I reach the required level

  Background:
    Given the following config values are set as admin:
      | enableavailability | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And a PlayerHUD block exists in course "C1" with 100 XP per level
    And the following "activities" exist:
      | activity | course | name          | availability                                                                         |
      | page     | C1     | Gated Content | {"op":"&","c":[{"type":"playerhud","subtype":"level","levelval":2}],"showc":[true]} |

  Scenario: A student below the required level sees the restriction notice
    Given a PlayerHUD player "student1" exists in course "C1" with 50 XP
    When I log in as "student1"
    And I am on "C1" course homepage
    Then I should see "Gated Content"
    And I should see "You must reach"

  Scenario: A student at or above the required level accesses the activity without restriction
    Given a PlayerHUD player "student1" exists in course "C1" with 100 XP
    When I log in as "student1"
    And I am on "C1" course homepage
    Then I should see "Gated Content"
    And I should not see "You must reach"
