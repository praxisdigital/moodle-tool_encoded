@tool @tool_encoded @javascript
Feature: Check that the appropriate users can access the migration tool

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                 |
      | teacher1  | Teacher   | One      | teacher1@example.com  |
      | student1  | Student   | One      | student1@example.com  |
    And I fill the table "workshop_assessments" with:
      | submissionid | reviewerid | grade | feedbackauthor                                                                                                    |
      | 1            | 2          | 50.00 | <p>Bad data &lt;img alt="" src="data:image/gif;base64,R0lGODdhAQABAPAAAP8AAAAAACwAAAAAAQABAAACAkQBADs=" /&gt;</p> |
      | 2            | 2          | 75.00 | <p>No encoded data so algood</p>                                                                                  |
    And I fill the table "book" with:
      | course | name      | intro                                                                                                             | introformat | numbering | navstyle | customtitles | revision | timecreated | timemodified |
      | 1      | Some book | <p>Bad data &lt;img alt="" src="data:image/gif;base64,R0lGODdhAQABAPAAAP8AAAAAACwAAAAAAQABAAACAkQBADs=" /&gt;</p> | 0           | 1         | 0        | 0            | 1        | 0           | 0            |
      | 2      | Fake book | <p>No encoded data so algood</p>                                                                                  | 0           | 1         | 0        | 0            | 1        | 0           | 0            |

  Scenario: Generate a report with a selection of columns
    # Verbosely generate a report for a single table.
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Generate report" in site administration
    And I should see "Generate report"
    When I press "workshop_assessments_generate"
    And I trigger cron
    And I should see "tool_encoded\task\generate_report"
    # Move back onto the site.
    And I am on site homepage
    Then I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
    # Further coverage can be added for edge case where a column contains many encoded files.
    And the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)                       | MIME Type       | Size    | Migrated |
      | workshop_assessments | feedbackauthor,feedbackreviewer | data:image/gif; | 0.07 kb | No       |
    And the "View" item should exist in the "Actions" action menu of the "workshop_assessments" "table_row"
    And the "Delete" item should exist in the "Actions" action menu of the "workshop_assessments" "table_row"
    # TODO: Question - If multiple encoded values add up to more than the admin setting, should the warning be triggered even if individual sizes are under threshold?

  @failing
  Scenario: Confirm contents and available actions of the report
    # TODO: Once CMID is included in the report, this can be used to check the contents of the report.
    Given I log in as "admin"
    And I generate a report for "workshop_assessments"
    And I generate a report for "book"
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
    # Check the report contents.
    And the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)                       | MIME Type       | Size    | Migrated |
      | workshop_assessments | feedbackauthor,feedbackreviewer |data:image/gif;  | 0.07 kb | No       |
    And I should see "Native ID"
    And "workshop_assessments" row "Duration" column of "reportbuilder-table" table should contain "secs"
    # Check sorting on an arbitrary column.
    And I click on "Sort by Column(s) Ascending" "link"
    And "workshop_assessments" "table_row" should appear before "book" "table_row"
    # Check filtering.
    And I click on "Filters" "button"
    And I set the following fields in the "Column(s)" "core_reportbuilder > Filter" to these values:
      | Column(s) value    | feedbackauthor   |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    Then I should see "Filters applied"
    And I should see "Filters (1)" in the "#dropdownFiltersButton" "css_element"
    And the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)      |
      | workshop_assessments | feedbackauthor,feedbackreviewer |
    And the following should not exist in the "reportbuilder-table" table:
      | table | Column(s) |
      | book  | intro     |
    And I click on "Filters" "button"
    # Check the actions available.
    And I choose the "View" item in the "Actions" action menu of the "workshop_assessments" "table_row"
    And I wait to be redirected

  Scenario: Confirm privileged users can access the migration tool
    Given I log in as "admin"
    And I generate a report for "workshop_assessments"
    # Confirm that the primary admin has access.
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
    And the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)                       | MIME Type       | Size    | Migrated |
      | workshop_assessments | feedbackauthor,feedbackreviewer | data:image/gif; | 0.07 kb | No       |
    # Checking that other admins have access after they were elevated.
    And I navigate to "Users > Permissions > Site administrators" in site administration
    And I click on "//div[@class='userselector']/descendant::option[contains(., 'Teacher One')]" "xpath_element"
    And I press "Add"
    And I press "Continue"
    And I log out
    When I log in as "teacher1"
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
    Then the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)                       | MIME Type       | Size    | Migrated |
      | workshop_assessments | feedbackauthor,feedbackreviewer | data:image/gif; | 0.07 kb | No       |
    And I log in as "student1"
    And "Plugins > Admin tools > Base64 Encoder > Display report" "link" should not exist in current page administration

  Scenario: Generate a set of reports ensuring multiple tasks are spawned
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Generate report" in site administration
    And I press "assign_generate"
    And I wait to be redirected
    When I press "workshop_assessments_generate"
    When I trigger cron
    Then I should see "{\"table\":\"assign\",\"columns\":\"intro,activity\"}"
    And I should see "{\"table\":\"workshop_assessments\",\"columns\":\"feedbackauthor,feedbackreviewer\"}"
