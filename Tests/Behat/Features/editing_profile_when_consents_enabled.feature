@ticket-BB-16452
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
Feature: Editing profile when consents enabled
  In order to be able to edit profile when consents enabled
  As an Logged user
  I should have possibility to work with no consents selected in system configuration

  Scenario: Create two sessions
    Given sessions active:
      | Admin                   | first_session  |
      | User                    | second_session |

  Scenario: Enable consent functionality via feature toggle
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And I should not see a "Sortable Consent List" element
    And fill form with:
      | Use Default                  | false |
      | Enable User Consents Feature | true  |
    And click "Save settings"
    And I should see a "Sortable Consent List" element

  Scenario: Check frontend user can edit profile with enabled consents feature but not selected consents
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account link"
    And I click "Edit Profile Button"
    And I fill form with:
      | First Name | Updated name |
    When I save form
    Then I should see "Customer User profile updated" flash message
    And I should see "Updated name"
