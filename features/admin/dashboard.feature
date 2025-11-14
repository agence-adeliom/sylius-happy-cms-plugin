@admin_dashboard
Feature: Accessing the administration dashboard
    In order to manage my store
    As an Administrator
    I want to access the administration dashboard without errors

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @no-api @ui
    Scenario: Accessing the administration dashboard
        When I open administration dashboard
