@tool @tool_base64encoder @javascript
Feature: Find potential records that need to be migrated and action as appropriate

  Background:
    Given I have a workshop module
    And As a student I add some good data
    And As a student enter a set of bad data with an inline images and videos
    # Shorthand for selecting and generating the report.
    And I trigger the migration tool population task
    And I log in as "admin"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration

  Scenario: With a known bad record, create a new adhoc task to migrate the record and verify that it is migrated
    Given I should see "Migrate" and "Delete" as the only options for the record
    When I trigger the migration tool record to Migrate
    And I navigate to the adhoc report
    Then I should see the migration task was successful
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    And the record should be flagged as migrated

  # This can be extended to ensure large records are migrated.
  Scenario: With a known bad record, if it is larger than the admin setting, warn the admin
    Given The base64encoder max size admin setting is 10Kb
    When A big bad record is added by the learner
    And The migration tool population task is triggered
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    Then I should see a warning message associated with the record
    And I should see "Migrate" and "Delete" as the only options

  # TODO: Question - Should records that are potentially good still be attempted to be migrated?
  Scenario: Try to migrate a known good record and verify that it is not migrated
    Given I trigger the known good migration tool record to "Create adhoc task"
    When I navigate to the adhoc report
    # TODO: Question - If we do queue up a task, should we make it successful or failed?
    # I think successful as the process would run and not errors were detected.
    Then I should see the migration task was successful
    And I should see "No records migrated"
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    And the record should be flagged as not migrated

  # TODO: Question - Should we limit the task to a single module instance? Reading emails it seems we should only look at selected columns in a table.
  Scenario: Only search within a single module instance at a time when generating the report
    Given I have two workshop modules
    And I trigger the migration tool population task once with a cmid
    When I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    Then I should only see records from the first workshop module with the matching cmid
    And I should not see records from the second workshop module

  # This can be extended to teachers with label modules etc.
  Scenario: Learner files that are base64 encoded can be accessed after migration
    Given I migrate the record
    When I log in as a learner
    And I access the workshop module
    Then I should see "Good data and no encoded data"
    And I should not see "A notification about the migration

  # Informed that this story is not required currently. Covers points 6 and 7.
  Scenario: With a known set of bad records, create new adhoc tasks to migrate the records and verify the migration
    Given I select a set of records or columns
    And I trigger the migration tool to "Create adhoc task" / "Migrate all"
    When I navigate to the adhoc report
    Then I should see the migration tasks were successful
    And I navigate to "Plugins > Admin tools > base64encoder > report" in site administration
    And the records should be flagged as migrated
