@admin_page_builder
Feature: Accessing the page builder interface
    In order to manage page content
    As an Administrator
    I want to access the page builder interface without errors

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @no-api @ui
    Scenario: Accessing the page builder for a page resource
        Given there is a page with template "homepage"
        When I go to the page builder
        Then I should be on the page builder page
