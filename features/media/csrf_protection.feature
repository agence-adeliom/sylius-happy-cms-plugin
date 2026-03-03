@media @csrf @security
Feature: CSRF Protection on Media Manager
    In order to protect against Cross-Site Request Forgery attacks
    As an administrator
    I want all media operations to require a valid CSRF token

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @ui
    Scenario: CSRF token is present on media manager page
        When I go to the media manager page
#        Then I should see a CSRF token in the page metadata
#        And the CSRF token should be a 64-character hexadecimal string
#
#    @ui @javascript
#    Scenario: Upload file without CSRF token is rejected
#        Given I am on the media manager page
#        When I attempt to upload a file without providing a CSRF token
#        Then I should see an error message containing "csrf" or "security token"
#        And the file should not be uploaded
#
#    @ui @javascript
#    Scenario: Upload file with invalid CSRF token is rejected
#        Given I am on the media manager page
#        When I attempt to upload a file with an invalid CSRF token
#        Then I should see an error message containing "csrf" or "invalid"
#        And the file should not be uploaded
#
#    @ui @javascript
#    Scenario: Upload file with valid CSRF token succeeds
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When I upload a file named "test-image.jpg" with the valid CSRF token
#        Then I should see a success message
#        And the file "test-image.jpg" should be uploaded
#
#    @ui @javascript
#    Scenario: Delete file without CSRF token is rejected
#        Given there is a media file "important-document.pdf" in the media library
#        And I am on the media manager page
#        When I attempt to delete "important-document.pdf" without providing a CSRF token
#        Then I should see an error message containing "csrf"
#        And the file "important-document.pdf" should still exist
#
#    @ui @javascript
#    Scenario: Delete file with valid CSRF token succeeds
#        Given there is a media file "test-file.pdf" in the media library
#        And I am on the media manager page
#        And there is a valid CSRF token
#        When I delete "test-file.pdf" with the valid CSRF token
#        Then I should see a success message
#        And the file "test-file.pdf" should not exist
#
#    @ui @javascript
#    Scenario: Rename file without CSRF token is rejected
#        Given there is a media file "old-name.jpg" in the media library
#        And I am on the media manager page
#        When I attempt to rename "old-name.jpg" to "new-name.jpg" without a CSRF token
#        Then I should see an error message containing "csrf"
#        And the file should still be named "old-name.jpg"
#
#    @ui @javascript
#    Scenario: Rename file with valid CSRF token succeeds
#        Given there is a media file "original.jpg" in the media library
#        And I am on the media manager page
#        And there is a valid CSRF token
#        When I rename "original.jpg" to "renamed.jpg" with the valid CSRF token
#        Then I should see a success message
#        And the file should be named "renamed.jpg"
#
#    @ui @javascript
#    Scenario: Create folder without CSRF token is rejected
#        Given I am on the media manager page
#        When I attempt to create a folder named "New Folder" without a CSRF token
#        Then I should see an error message containing "csrf"
#        And the folder "New Folder" should not exist
#
#    @ui @javascript
#    Scenario: Create folder with valid CSRF token succeeds
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When I create a folder named "Test Folder" with the valid CSRF token
#        Then I should see a success message
#        And the folder "Test Folder" should exist
#
#    @ui @javascript
#    Scenario: Move file without CSRF token is rejected
#        Given there is a media file "document.pdf" in the media library
#        And there is a folder "Documents" in the media library
#        And I am on the media manager page
#        When I attempt to move "document.pdf" to "Documents" without a CSRF token
#        Then I should see an error message containing "csrf"
#        And the file "document.pdf" should not be in the "Documents" folder
#
#    @ui @javascript
#    Scenario: Move file with valid CSRF token succeeds
#        Given there is a media file "file.pdf" in the media library
#        And there is a folder "Archive" in the media library
#        And I am on the media manager page
#        And there is a valid CSRF token
#        When I move "file.pdf" to "Archive" with the valid CSRF token
#        Then I should see a success message
#        And the file "file.pdf" should be in the "Archive" folder
#
#    @ui @javascript
#    Scenario: Edit media metadata without CSRF token is rejected
#        Given there is a media file "image.jpg" in the media library
#        And I am on the media manager page
#        When I attempt to edit metadata of "image.jpg" without a CSRF token
#        Then I should see an error message containing "csrf"
#        And the metadata should not be changed
#
#    @ui @javascript
#    Scenario: Edit media metadata with valid CSRF token succeeds
#        Given there is a media file "photo.jpg" in the media library
#        And I am on the media manager page
#        And there is a valid CSRF token
#        When I edit metadata of "photo.jpg" with title "My Photo" and the valid CSRF token
#        Then I should see a success message
#        And the file "photo.jpg" should have metadata title "My Photo"
#
#    @api
#    Scenario: CSRF token expires after 2 hours
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When 2 hours and 1 minute have passed
#        And I attempt to upload a file with the expired CSRF token
#        Then I should see an error message containing "expired"
#        And the file should not be uploaded
#
#    @api
#    Scenario: CSRF tokens are different for different sessions
#        Given I am logged in as "admin@example.com" in session A
#        And I am logged in as "admin@example.com" in session B
#        When I get the CSRF token from session A
#        And I get the CSRF token from session B
#        Then the tokens should be different
#
#    @ui @javascript
#    Scenario: CSRF protection works across different operations
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When I create a folder named "Uploads" with the valid CSRF token
#        And I upload a file named "document.pdf" with the valid CSRF token
#        And I move "document.pdf" to "Uploads" with the valid CSRF token
#        And I rename "document.pdf" to "final-doc.pdf" with the valid CSRF token
#        And I delete "final-doc.pdf" with the valid CSRF token
#        Then all operations should succeed
#        And no CSRF errors should occur
#
#    @api
#    Scenario: CSRF token can be sent in HTTP header
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When I make a POST request to delete a file with CSRF token in "X-CSRF-Token" header
#        Then the request should succeed
#        And no CSRF error should occur
#
#    @api
#    Scenario: CSRF token can be sent in request body
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When I make a POST request to delete a file with CSRF token in JSON body as "_csrf_token"
#        Then the request should succeed
#        And no CSRF error should occur
#
#    @api
#    Scenario: CSRF token can be sent as POST parameter
#        Given I am on the media manager page
#        And there is a valid CSRF token
#        When I make a POST request to delete a file with CSRF token as POST parameter "_csrf_token"
#        Then the request should succeed
#        And no CSRF error should occur
