<?php

namespace Drupal\Tests\oyw_salesforce\Unit;

// Import the Salesforce client we are testing
use Drupal\oyw_salesforce\Client\OywSalesforceClient;

// Base Drupal unit test class
use Drupal\Tests\UnitTestCase;

// Simple logger that does nothing (used to satisfy the constructor)
use Psr\Log\NullLogger;

class OywSalesforceClientTest extends UnitTestCase
{
    // This test checks that the correct Salesforce API endpoint is built
    public function testGetPicklistValuesBuildsCorrectEndpoint(): void
    {
        // Create a dummy logger so we don't log anything during the test
        $logger = new NullLogger();

        // Create a mock version of the Salesforce client
        // We only mock the apiCall() method so no real HTTP request is made
        $client = $this->getMockBuilder(OywSalesforceClient::class)
            ->setConstructorArgs([$logger])
            ->onlyMethods(['apiCall'])
            ->getMock();

        // Tell PHPUnit that apiCall() must be called exactly once
        // and that it must be called with this exact endpoint string
        $client->expects($this->once())
            ->method('apiCall')
            ->with(
                'ui-api/object-info/Contact/picklist-values/Gender__c/field_gender'
            )
            // Fake the Salesforce response so the test can continue
            ->willReturn(['values' => []]);

        // Call the real method we are testing
        $result = $client->getPicklistValues(
            'field_gender',
            "['Contact','Gender__c']"
        );

        // Final safety check: make sure the method returned an array
        $this->assertIsArray($result);
    }
}
