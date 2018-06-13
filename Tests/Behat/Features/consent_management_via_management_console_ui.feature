@regression
@feature-BB-13768
Feature: Consent management via Management Console UI
  In order to be able to manage consents in OroCommerce
  As an Administrator
  I want to provide the ability to CRUD consents in the system

  Scenario: Create two sessions
    Given sessions active:
      | Admin                   | first_session  |
      | User                    | second_session |

  Scenario: Create Landing Page and Content Node in Web Catalog
    Given I proceed as the Admin
    And I login as administrator
    And go to Marketing/ Landing Pages
    Then click "Create Landing Page"
    And fill "Landing Page Form" with:
      | Titles   | Consent Landing |
      | URL Slug | consent-landing |
    And I fill in "CMS Page Content" with "Consent landing page description"
    And click "Save and Close"
    And go to Marketing/ Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Store and Process |
    And I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message
    And I click "Edit Content Tree"
    And I fill "Content Node Form" with:
      | Titles | Home page |
    And I click "Add System Page"
    And I save form
    Then I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Store and Process Node |
      | Url Slug     | store-and-process-node |
      | Landing Page | Consent Landing        |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Enable consent functionality via feature toggle
    Given go to System/ Configuration
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And I should not see a "Sortable Consent List" element
    And fill form with:
      | Use Default                  | false |
      | Enable User Consents Feature | true  |
    And click "Save settings"
    And I should see a "Sortable Consent List" element

  Scenario: Admin User is able to CRUD consents
    Given I go to System/ Consent Management
    And click "Create Consent"
    When click "Save and Close"
    Then should see "Consent Form" validation errors:
      | Name | This value should not be blank. |
    And fill "Consent Form" with:
      | Name | Presenting Personal Data |
      | Type | Optional                 |
    And I save and close form
    Then should see "Consent has been created" flash message
    And I click "Edit"
    And fill "Consent Form" with:
      | Type | Mandatory |
    When I click "Web Catalog Hamburger Button"
    And I should see following grid:
      | Id | Name              |
      | 1  | Store and Process |
    Then close ui dialog
    And I should see "Please choose a Web Catalog"
    And I fill form with:
      | Web Catalog | Store and Process |
    And I should not see "Please choose a Web Catalog"
    And I click "Store and Process Node"
    And save and close form
    Then should see "Consent has been saved" flash message
    When I click "Delete"
    Then I should see "Are you sure you want to delete this consent?"
    And I click "Cancel"
    And go to System/ Consent Management
    And I should see following grid:
      | Name                     | Type      | Content Node           | Content Source  |
      | Presenting Personal Data | Mandatory | Store and Process Node | Consent Landing |
    Then I set "Store and Process" as default web catalog
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | Email Newsletters |
      | Type | Optional          |
    And I save and create new form
    And fill "Consent Form" with:
      | Name        | Collecting and storing personal data |
      | Type        | Mandatory                            |
      | Web Catalog | Store and Process                    |
    And I click "Store and Process Node"
    And I save and create new form
    And fill "Consent Form" with:
      | Name        | Receive notifications |
      | Type        | Optional              |
    And save and close form
    Then go to System/ Consent Management
    And I should see following grid:
      | Name                                 | Type      | Content Node           | Content Source  |
      | Receive notifications                | Optional  | N/A                    | N/A             |
      | Collecting and storing personal data | Mandatory | Store and Process Node | Consent Landing |
      | Email Newsletters                    | Optional  | N/A                    | N/A             |
      | Presenting Personal Data             | Mandatory | Store and Process Node | Consent Landing |

  Scenario: Admin User is able to enable/disable consents functionality on System/Website level
    Given go to System/ Configuration
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And fill "Consent Settings Form" with:
      | Enabled User Consents Use Default | false |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And I choose Consent "Presenting Personal Data" in 1 row
    Then click "Add Consent"
    And I choose Consent "Email Newsletters" in 2 row
    Then click "Add Consent"
    And I choose Consent "Collecting and storing personal data" in 3 row
    Then click "Add Consent"
    And I choose Consent "Receive notifications" in 4 row
    And I drag 2 row to the top in "Consent" table
    Then click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row

  Scenario: Show consents on registration page
    Given I proceed as the User
    And I am on the homepage
    And click "Register"
    Then I should see 2 elements "Required Consent"
    And I should see 2 elements "Optional Consent"
    And I should not see "Consent Link" in the "Optional Consent" element
    And the "Presenting Personal Data" checkbox should not be checked
    And the "Email Newsletters" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should not be checked
    And the "Receive notifications" checkbox should not be checked
    Then I fill form with:
      | Company Name                         | OroCommerce              |
      | First Name                           | Amanda                   |
      | Last Name                            | Cole                     |
      | Email Address                        | AmandaRCole1@example.org |
      | Password                             | AmandaRCole1@example.org |
      | Confirm Password                     | AmandaRCole1@example.org |
    And press "Create An Account"
    Then I should see that "Required Consent" contains "This agreement is required"
    And I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title        | Presenting Personal Data         |
      | Content      | Consent landing page description |
      | okButton     | Accept                           |
      | cancelButton | Cancel                           |
    And click "Accept"
    Then I should not see a "Consent Popup" element
    Then I click "Collecting and storing personal data"
    And click "Accept"
    And the "Presenting Personal Data" checkbox should be checked
    And the "Email Newsletters" checkbox should not be checked
    And the "Collecting and storing personal data" checkbox should be checked
    When press "Create An Account"
    Then I should see "Please check your email to complete registration" flash message

  Scenario: Confirmation of new user which have accepted consents
    Given I proceed as the Admin
    And go to Customers/Customer Users
    And click view "AmandaRCole1@example.org" in grid
    And click "Confirm"
    And I should see "Confirmation successful" flash message

  Scenario: Manage consents from My profile page
    Given I proceed as the User
    And I signed in as AmandaRCole1@example.org on the store frontend
    When click "Account"
    Then should see a "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Presenting Personal Data" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element
    And I should not see "Consent Item Link" in the "Unaccepted Consent" element
    When I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title        | Presenting Personal Data         |
      | Content      | Consent landing page description |
      | cancelButton | Close                            |
    And click "Close"
    Then I should not see a "Consent Popup" element
    When I click "Edit Profile Button"
    Then the "Email Newsletters" checkbox should not be checked
    And the "Presenting Personal Data" checkbox should be checked
    And the "Collecting and storing personal data" checkbox should be checked
    And I should not see "Consent Link" in the "Optional Consent" element
    And fill form with:
      | Presenting Personal Data             | false |
      | Collecting and storing personal data | false |
      | Email Newsletters                    | true  |
    And I save form
    Then I should see "UiWindow" with elements:
      | Title        | Data Protection                                                 |
      | Content      | Are you sure you want to decline the consents accepted earlier? |
      | okButton     | Yes, Decline                                                    |
      | cancelButton | No, Cancel                                                      |
    And I click "No, Cancel"
    When I click "Presenting Personal Data"
    Then I should see "UiDialog" with elements:
      | Title        | Presenting Personal Data         |
      | Content      | Consent landing page description |
      | okButton     | Accept                           |
      | cancelButton | Cancel                           |
    And click "Cancel"
    Then the "Presenting Personal Data" checkbox should not be checked
    When I click "Presenting Personal Data"
    And click "Accept"
    Then I should not see a "Consent Popup" element
    And the "Presenting Personal Data" checkbox should be checked
    Then the "Email Newsletters" checkbox should be checked
    Then the "Collecting and storing personal data" checkbox should not be checked
    And I save form
    And click "Yes, Decline"
    Then should see "Customer User profile updated" flash message
    When click "Account"
    And I should see "Accepted Consent" element with text "Email Newsletters" inside "Data Protection Section" element
    And I should see "Accepted Consent" element with text "Presenting Personal Data" inside "Data Protection Section" element
    And I should see "Unaccepted Consent" element with text "Collecting and storing personal data" inside "Data Protection Section" element

  Scenario: Send notifications on removing consents
    Given I proceed as the Admin
    When I go to Activities/ Contact Requests
    And I should see following grid:
      | First Name | Last Name | Email                    | Contact Reason                             | Website |
      | Amanda     | Cole      | AmandaRCole1@example.org | General Data Protection Regulation details | Default |
    And click view "General Data Protection Regulation details" in grid
    Then I should see Contact Request with:
      | First Name     | Amanda                                                            |
      | Last Name      | Cole                                                              |
      | Email          | AmandaRCole1@example.org                                          |
      | Contact Reason | General Data Protection Regulation details                        |
      | Comment        | Consent Collecting and storing personal data declined by customer |
      | Customer User  | Amanda Cole                                                       |

  Scenario: Check mandatory consents before creating an RFQ
    Given I proceed as the User
    And click "Requests For Quote"
    Then click "New Quote"
    And I should see 1 elements "Required Consent"
    And I should not see an "Optional Consent" element
    And I should not see "Presenting Personal Data"
    And I should not see "Email Newsletters"
    And the "Collecting and storing personal data" checkbox should not be checked
    When I fill form with:
      | First Name    | Amanda                                                                |
      | Last Name     | Cole                                                                  |
      | Email Address | AmandaRCole@example.org                                               |
      | Company       | Oro Inc                                                               |
      | Notes         | Testing the way required consents are displayed before submitting RFQ |
    And click "Submit Request"
    Then I should see that "Required Consent" contains "This agreement is required"
    When I click "Collecting and storing personal data"
    Then I should see "UiDialog" with elements:
      | Title        | Collecting and storing personal data |
      | Content      | Consent landing page description     |
      | okButton     | Accept                               |
      | cancelButton | Cancel                               |
    And click "Cancel"
    And the "Collecting and storing personal data" checkbox should not be checked
    When I click "Collecting and storing personal data"
    And click "Accept"
    Then I should not see a "Consent Popup" element
    And the "Collecting and storing personal data" checkbox should be checked
    When click "Submit Request"
    Then should see "Request has been saved" flash message

  Scenario: When deleting consent, it should be removed from system config
    Given I proceed as the Admin
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And uncheck "Use System" for "Enabled user consents" field
    And submit form
    Then I should see "Configuration saved" flash message
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row
    And I should see that "Receive notifications" is in 4 row
    Given I go to System/ Consent Management
    And click delete "Receive notifications" in grid
    Then I should see "Are you sure you want to delete this consent?"
    And I click "Yes, Delete"
    Then I should not see "Receive notifications"
    Given go to System/ Configuration
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And I should not see "Receive notifications"
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row
    Given go to System/ Websites
    And click "Configuration" on row "Default" in grid
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And I should not see "Receive notifications"
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Presenting Personal Data" is in 2 row
    And I should see that "Collecting and storing personal data" is in 3 row

  Scenario: Accepted consents can't be deleted or edited
    Given I go to System/ Consent Management
    And I should not see following actions for Collecting and storing personal data in grid:
      | Edit   |
      | Delete |
    And I should not see following actions for Presenting Personal Data in grid:
      | Edit   |
      | Delete |
    And I should not see following actions for Email Newsletters in grid:
      | Edit   |
      | Delete |

  Scenario: Admin User is unable to edit/delete CMS page, which has relation to applied consent
    Given I go to Marketing/ Landing Pages
    And I should see following actions for About in grid:
      | View   |
      | Edit   |
      | Delete |
    And I should see following actions for Consent Landing in grid:
      | View |
    And I should not see following actions for Consent Landing in grid:
      | Edit   |
      | Delete |

  Scenario: Accepted consents can be deleted from system config
    Given go to System/ Configuration
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    And I remove "Presenting Personal Data" from Consent
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see that "Email Newsletters" is in 1 row
    And I should see that "Collecting and storing personal data" is in 2 row

  Scenario: When User submit registration form with removed consent, it should see validation error
    Given I proceed as the Admin
    Then I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name | Consent on remove |
      | Type | Mandatory         |
    And I save and close form
    Then should see "Consent has been created" flash message
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    Then click "Add Consent"
    And I choose Consent "Consent on remove" in 4 row
    Then click "Save settings"
    Given I proceed as the User
    Then I click "Sign Out"
    And I am on the homepage
    And click "Register"
    Then I should see 3 elements "Required Consent"
    Then I fill form with:
      | Company Name                         | OroCommerce              |
      | First Name                           | Amanda                   |
      | Last Name                            | Cole                     |
      | Email Address                        | AmandaRCole2@example.org |
      | Password                             | AmandaRCole2@example.org |
      | Confirm Password                     | AmandaRCole2@example.org |
      | Consent on remove                    | true                     |
    And I click "Presenting Personal Data"
    And click "Accept"
    Then I click "Collecting and storing personal data"
    And click "Accept"
    Given I proceed as the Admin
    Then I go to System/ Consent Management
    And I click view "Consent on remove" in grid
    When I click "Delete"
    Then I should see "Are you sure you want to delete this consent?"
    And I click "Yes, Delete"
    Then should see "Consent deleted" flash message
    Given I proceed as the User
    When press "Create An Account"
    Then I should see "Some consents were changed. Please reload the page."

  Scenario: When User submit registration form with removed landing page, it should see validation error
    Given I proceed as the Admin
    Then go to Marketing/ Landing Pages
    And click "Create Landing Page"
    And fill "Landing Page Form" with:
      | Titles   | Consent Landing on remove |
      | URL Slug | consent-landing-on-remove |
    And I fill in "CMS Page Content" with "Consent landing page description"
    And click "Save and Close"
    Then go to Marketing/ Web Catalogs
    And click view "Store and Process" in grid
    And I click "Edit Content Tree"
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Landing page on remove node      |
      | Url Slug     | consent-landing-on-remove-node   |
      | Landing Page | Consent Landing on remove        |
    And I save form
    Then I should see "Content Node has been saved" flash message
    Then I go to System/ Consent Management
    And click "Create Consent"
    And fill "Consent Form" with:
      | Name        | Consent with landing page on remove |
      | Type        | Mandatory                           |
      | Web Catalog | Store and Process                   |
    And I click "Landing page on remove node"
    And I save and close form
    Then should see "Consent has been created" flash message
    Then go to System/ Websites
    And click "Configuration" on row "Default" in grid
    Then follow "Commerce/Customer/Consents" on configuration sidebar
    Then click "Add Consent"
    And I choose Consent "Consent with landing page on remove" in 4 row
    Then click "Save settings"
    Given I proceed as the User
    And I am on the homepage
    And click "Register"
    Then I should see 3 elements "Required Consent"
    Then I fill form with:
      | Company Name                         | OroCommerce              |
      | First Name                           | Amanda                   |
      | Last Name                            | Cole                     |
      | Email Address                        | AmandaRCole3@example.org |
      | Password                             | AmandaRCole3@example.org |
      | Confirm Password                     | AmandaRCole3@example.org |
    And I click "Presenting Personal Data"
    And click "Accept"
    Then I click "Collecting and storing personal data"
    And click "Accept"
    And I click "Consent with landing page on remove"
    And click "Accept"
    Given I proceed as the Admin
    And go to Marketing/ Landing Pages
    And click view "Consent Landing on remove" in grid
    When I click "Delete"
    Then I should see "Are you sure you want to delete this Landing Page?"
    And I click "Yes, Delete"
    Then should see "Landing Page deleted" flash message
    Given I proceed as the User
    When press "Create An Account"
    Then I should see "Some consents were changed. Please reload the page."
