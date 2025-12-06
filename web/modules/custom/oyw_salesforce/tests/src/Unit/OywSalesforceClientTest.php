<?php

namespace Drupal\Tests\oyw_salesforce\Unit;

// Import the Salesforce client we are testing.
use Drupal\oyw_salesforce\Client\OywSalesforceClient;

// Base Drupal unit test class.
use Drupal\Tests\UnitTestCase;

// Simple logger that does nothing (used to satisfy the constructor)
use Psr\Log\NullLogger;

/**
 * Oyw Salesforce Client Test.
 */
class OywSalesforceClientTest extends UnitTestCase {

  /**
   * This test checks that the correct Salesforce API endpoint is built.
   *
   * And that the Salesforce response is correctly processed into options.
   */
  public function testGetPicklistValuesBuildsCorrectEndpointAndProcessesResponse(): void {
    // Create a dummy logger so we don't log anything during the test.
    $logger = new NullLogger();

    // Create a mock version of the Salesforce client.
    // We only mock the apiCall() method so no real HTTP request is made.
    $client = $this->getMockBuilder(OywSalesforceClient::class)
      ->setConstructorArgs([$logger])
      ->onlyMethods(['apiCall'])
      ->getMock();

    // Fake Salesforce response based on the provided example data.
    $salesforceResponse = [
      'values' => [
        [
          'attributes' => NULL,
          'label' => 'Male',
          'validFor' => [],
          'value' => 'Male',
        ],
        [
          'attributes' => NULL,
          'label' => 'Female',
          'validFor' => [],
          'value' => 'Female',
        ],
        [
          'attributes' => NULL,
          'label' => 'Other',
          'validFor' => [],
          'value' => 'Other',
        ],
      ],
    ];

    // Tell PHPUnit that apiCall() must be called exactly once
    // and that it must be called with this exact endpoint string.
    $client->expects($this->once())
      ->method('apiCall')
      ->with(
        'ui-api/object-info/Contact/picklist-values/Gender__c/field_gender'
      )
      // Return the fake Salesforce response so processing logic can be tested.
      ->willReturn($salesforceResponse);

    // Call the real method we are testing.
    $result = $client->getPicklistValues(
      'field_gender',
      "['Contact','Gender__c']"
    );

    // Assert that the Salesforce response was transformed into
    // the expected options array.
    $this->assertSame([
      'Male' => 'Male',
      'Female' => 'Female',
      'Other' => 'Other',
    ], $result);
  }

}
