@availability @availability_playerhud @javascript
Feature: Gamification-based availability restriction controls student access
  As a student
  I should be blocked from an activity when gamification is disabled for my account
  and gain access once gamification is enabled

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
      | activity | course | name          | availability                                                               |
      | page     | C1     | Gated Content | {"op":"&","c":[{"type":"playerhud","subtype":"gamification"}],"showc":[true]} |

  Scenario: A student with gamification disabled sees the restriction notice
    Given a PlayerHUD player "student1" exists in course "C1" with gamification disabled
    When I log in as "student1"
    And I am on "C1" course homepage
    Then I should see "Gated Content"
    And I should see "gamification enabled"

  Scenario: A student with gamification enabled accesses the activity without restriction
    Given a PlayerHUD player "student1" exists in course "C1" with 0 XP
    When I log in as "student1"
    And I am on "C1" course homepage
    Then I should see "Gated Content"
    And I should not see "gamification enabled"
