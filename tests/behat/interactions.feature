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

  Scenario: Generate a report with a selection of columns
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Generate report" in site administration
    And I should see "Tables that may have issues:"
    When I press "workshop_assessments"
    # TODO: Question - Point 8. Should the reset button clear the current select of table & columns, clear the current report (potential hits) or something else?
    #And I should see "Reset" "Button"
    # Point 2.
    And I click on "All found columns (workshop_assessments)" "link"
    # Point 3.
    And I trigger cron
    # Point 4.
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

  Scenario: Confirm contents and available actions of the report
    Given I log in as "admin"
    And I generate a report for "workshop_assessments" and "All found columns (workshop_assessments)" columns
    And I generate a report for "workshop_assessments" and "Column (feedbackauthor)" columns
    And I navigate to "Plugins > Admin tools > Base64 Encoder > Display report" in site administration
    # Check the report contents.
    And the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)                       | MIME Type       | Size    | Migrated |
      | workshop_assessments | feedbackauthor,feedbackreviewer |data:image/gif;  | 0.07 kb | No       |
    And I should see "Native ID"
    And "workshop_assessments" row "Duration" column of "reportbuilder-table" table should contain "secs"
    # Check sorting on an arbitrary column.
    And I click on "Sort by Column(s) Ascending" "link"
    And "feedbackauthor" "text" should appear before "feedbackauthor,feedbackreviewer" "text"
    # Check filtering.
    And I click on "Filters" "button"
    And I set the following fields in the "Column(s)" "core_reportbuilder > Filter" to these values:
      | Column(s) value    | feedbackauthor   |
    And I click on "Apply" "button" in the "[data-region='report-filters']" "css_element"
    Then I should see "Filters applied"
    And I should see "Filters (1)" in the "#dropdownFiltersButton" "css_element"
    And the following should exist in the "reportbuilder-table" table:
      | table                | Column(s)      |
      | workshop_assessments | feedbackauthor |
    And the following should not exist in the "reportbuilder-table" table:
      | table                | Column(s)                       |
      | workshop_assessments | feedbackauthor,feedbackreviewer |
    And I click on "Filters" "button"
    # Check the actions available.
    And I choose the "View" item in the "Actions" action menu of the "workshop_assessments" "table_row"
    And I wait to be redirected

  Scenario: Confirm privileged users can access the migration tool
    Given I log in as "admin"
    And I generate a report for "workshop_assessments" and "All found columns (workshop_assessments)" columns
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
    And I press "workshop_assessments"
    And I click on "All found columns (workshop_assessments)" "link"
    And I wait to be redirected
    And I press "workshop_assessments"
    And I click on "Column (feedbackauthor)" "link"
    When I trigger cron
    Then I should see "{\"table\":\"workshop_assessments\",\"columns\":\"feedbackauthor,feedbackreviewer\"}"
    And I should see "{\"table\":\"workshop_assessments\",\"columns\":\"feedbackauthor\"}"
