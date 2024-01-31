@tool @tool_encoded @javascript
Feature: Find potential records that need to be migrated and action as appropriate

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                 |
      | teacher1  | Teacher   | One      | teacher1@example.com  |
      | student1  | Student   | One      | student1@example.com  |
    And I fill the table "workshop_assessments" with:
      | submissionid | reviewerid | grade | feedbackauthor                                                                                                    |
      | 1            | 2          | 50.00 | <p>Bad data &lt;img alt="" src="data:image/gif;base64,R0lGODdhAQABAPAAAP8AAAAAACwAAAAAAQABAAACAkQBADs=" /&gt;</p> |
      | 2            | 2          | 75.00 | <p>No encoded data so algood</p>                                                                                  |

#  # Runnable tests start.
#  Scenario: With a known bad record, create a new adhoc task to migrate the record and verify that it is migrated
#    Given I choose the "Migrate" item in the "Actions" action menu of the "workshop_assessments" "table_row"
#    When I trigger cron
#    Then I should see "{\"table\":\"workshop_assessments\",\"columns\":\"feedbackauthor,feedbackreviewer\"}"
#    And I am on site homepage
#    When I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
#    Then the following should exist in the "reportbuilder-table" table:
#      | table                | Column(s)                       | MIME Type       | Size    | Migrated |
#      | workshop_assessments | feedbackauthor,feedbackreviewer | data:image/gif; | 0.07 kb | Yes      |
#
#  Scenario: With a known bad record, if it is larger than the admin setting, warn the admin
#    Given I log in as "admin"
#    # Lower the warning threshold to 5Kb for testing purposes.
#    And the following config values are set as admin:
#      | size | 5 | tool_encode |
#    And I fill the table "workshop_assessments" with:
#      | submissionid | reviewerid | grade | feedbackauthor                                                                                                        |
#      | 3            | 2          | 10.00 | <p>Bad big data &lt;img alt="" src="data:image/gif;base64,R0lGODdhAQABAPAAAP8AAAAAACwAAAAAAQABAAACAkQBADs=" /&gt;</p> |
#    And I navigate to "Plugins > Admin tools > Base64 Encoder > Generate report" in site administration
#    And I press "workshop_assessments"
#    And I click on "All found columns (workshop_assessments)" "link"
#    And I trigger cron
#    And I should see "tool_encoded\task\migrate"
#    And I am on site homepage
#    And I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
#    # Further coverage can be added for edge case where a column contains many encoded files.
#    And the following should exist in the "reportbuilder-table" table:
#      | table                | Column(s)                       | MIME Type       | Size   | Migrated | Warning            |
#      | workshop_assessments | feedbackauthor,feedbackreviewer | data:image/gif; | 6.2 kb | No       | Found large record |
#    And the "View" item should exist in the "Actions" action menu of the "workshop_assessments" "table_row"
#    And the "Migrate" item should exist in the "Actions" action menu of the "workshop_assessments" "table_row"
#    And the "Delete" item should exist in the "Actions" action menu of the "workshop_assessments" "table_row"
#
#    # Attempt a migration and verify that the record is migrated.
#    And I choose the "Migrate" item in the "Actions" action menu of the "workshop_assessments" "table_row"
#    And I wait to be redirected
#    When I trigger cron
#    Then I should see "{\"table\":\"workshop_assessments\",\"columns\":\"feedbackauthor,feedbackreviewer\"}"
#    And I am on site homepage
#    And I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
#    # Further coverage can be added for edge case where a column contains many encoded files.
#    And the following should exist in the "reportbuilder-table" table:
#      | table                | Column(s)                       | MIME Type       | Size   | Migrated | Warning            |
#      | workshop_assessments | feedbackauthor,feedbackreviewer | data:image/gif; | 6.2 kb | Yes      | Found large record |
#  # Runnable tests end.
#
#  # Questionable user stories.
#  # TODO: Question - Should records that are potentially good still be attempted to be migrated?
#  Scenario: Try to migrate a known good record and verify that it is not migrated
#    Given I trigger the known good migration tool record to "Create adhoc task"
#    When I navigate to the adhoc report
#    # TODO: Question - If we do queue up a task, should we make it successful or failed?
#    # I think successful as the process would run and not errors were detected.
#    Then I should see the migration task was successful
#    And I should see "No records migrated"
#    And I navigate to "Plugins > Admin tools > encoded > report" in site administration
#    And the record should be flagged as not migrated
#
#  # TODO: Question - Should we limit the task to a single module instance? Reading emails it seems we should only look at selected columns in a table.
#  Scenario: Only search within a single module instance at a time when generating the report
#    Given I have two workshop modules
#    And I trigger the migration tool population task once with a cmid
#    When I navigate to "Plugins > Admin tools > encoded > report" in site administration
#    Then I should only see records from the first workshop module with the matching cmid
#    And I should not see records from the second workshop module
#
#  # This can be extended to teachers with label modules etc.
#  Scenario: Learner files that are base64 encoded can be accessed after migration
#    Given I migrate the record
#    When I log in as a learner
#    And I access the workshop module
#    Then I should see "Good data and no encoded data"
#    And I should not see "A notification about the migration
