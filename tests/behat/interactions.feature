@tool @tool_base64encoder @javascript
Feature: Check that the appropriate users can access the migration tool

  Background:
    Given I have a workshop module
    And As a student I add some good data
    And As a student I enter a set of bad data with an inline images and videos
    # Note: Point 1 is more of a functional requirement than user story, at this point I will look at fields that are text columns and other potential identifiers.
    # Shorthand for the process covered in the Generate a report scenario.
    And I trigger the migration tool population task

  # Verbosely confirm we can generate reports based on the admin selection of table & columns
  Scenario: Generate a report with a selection of columns
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > base64encoder > generate report" in site administration
    And I should see a list of a list of potential tables to scan found via in a similar manner as xmldb tool
    When I select a table to scan
    And I select a set of columns to scan
    # TODO: Question - Should the reset button clear the current select of table & columns, clear the current report (potential hits) or something else?
    # Point 8.
    And I should see "Reset" "Button"
    # Point 2.
    Then I press "Generate report"
    # Point 3.
    And I should have spawned an adhoc task per column
    # Point 4.
    And I wait for the adhoc tasks to complete
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    # Story can be enhanced depending on the answer to the question in "Confirm contents and available actions of the report"
    And I should see "Some records that have been found"
    # Further coverage can be added for edge case where a column contains many encoded files.
    # TODO: Question - If multiple encoded values add up to more than the admin setting, should the warning be triggered even if individual sizes are under threshold?

  # Noted in email that this is the functional milestone for the project.
  Scenario: Confirm contents and available actions of the report
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    # TODO: Question - Points 4.1 -> 4.4 be stored as a run object within the DB, details in the adhoc output satisfactory or which ever I decide?
    # Could easily change based on answers to questions as a run may have meta information stored about the report such as size, run time, etc.
    # If such meta information is stored, we can have a report selection when hitting the report page rather than having to group records, filtering, pagination to even get started.
    # Further from this, we can opt to show all generated records rather than an instance of a sweep.

    # Covers points 5.1 -> 5.7
    # Fields here are for example purposes only and not reflective of the schema. / Base64 size to be used instead of decoded size.
    When the following fields in the "Record" "Report" match these values:
      | id | cmid | userid | table               | column       | format | mime | size | migrated | link                          | action         |
      | 1  | 1    | 2      | workshop_submission | editorformat | atto   | mp4  | 11Kb | false    | http://<>/workshop/submission | migrate,delete |
      | 2  | 1    | 2      | workshop_submission | description  | atto   | png  | 5Kb  | false    | http://<>/workshop/submission | migrate,delete |
    # Covers point 5.
    # TODO: Question - Depending on the reading of #5, Is it discussing an instance with an encode in two columns or is it about the edge case where a column contains many encodes?
    And I can sort by "size" "desc" ordered by cmid
    And I can filter by "migrated or other values"
    And I click on the link for record "1"
    Then I should be redirected to "http://<>/workshop/submission?cmid=1"

  Scenario: Confirm non-privileged users cannot access the migration tool
    Given I log in as "student"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    And I should see "You do not have permission to view this report"
    When I log out
    And I log in as "teacher"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    Then I should see "You do not have permission to view this report"

  Scenario: Confirm privileged users can access the migration tool
    # Confirm that the primary admin has access.
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    And I should see "Some records that have been found"
    # Checking that not only the primary admin has access.
    And I elevate the teacher user to an admin
    And I log out
    When I log in as "teacher"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    Then I should see "Some records that have been found"

  # Likely that this may not be needed for MVP similar to the migration of sets of records. Points 6 & 7.
  # Note that some clarification would be appreciated in that from depending on the reading,
  # one task is spawned per column in a table but in another reading it could be one task per column pair / selected columns.
  Scenario: Generate a set of reports ensuring multiple tasks are spawned
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > base64encoder > generate report" in site administration
    And I should see a list of a list of potential tables to scan
    And I select a table to scan
    And I select a column to scan
    And I press "Generate report"
    And I select another table to scan
    And I select another column to scan
    And I press "Generate report"
    When I go to the adhoc task page
    Then I shold see tasks, one for each report
