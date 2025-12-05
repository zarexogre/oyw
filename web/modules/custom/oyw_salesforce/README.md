# OYW Salesforce Module

This module dynamically populates Drupal list field options from Salesforce picklist values.

## What it does

- Extends the official Salesforce REST client
- Calls the Salesforce UI API to fetch picklist values
- Injects values into the field_gender field
- Includes PHPUnit unit tests
- Runs tests automatically on git commit

## Caching (extra to original spec)

To avoid unnecessary API calls, the processed picklist response is cached using Drupal's cache backend.

This improves performance and protects against API rate limits.

## Logging (extra to original spec)

Every Salesforce API call is logged to Drupal’s watchdog system under the channel, oyw_salesforce.

The logs include:

- When a picklist request starts
- The exact Salesforce endpoint being called
- How many values were returned

This makes debugging Salesforce data issues easy from:
Admin -> Reports -> Recent log messages

## Running tests

```
ddev phpunit web/modules/custom/oyw_salesforce/tests/src/Unit
```
