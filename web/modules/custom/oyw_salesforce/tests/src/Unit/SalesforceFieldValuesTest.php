<?php

namespace Drupal\Tests\oyw_salesforce\Unit;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\oyw_salesforce\Client\OywSalesforceClient;
use Drupal\oyw_salesforce\SalesforceFieldValues;
use Drupal\Tests\UnitTestCase;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\MemoryBackend;

class SalesforceFieldValuesTest extends UnitTestCase
{
    // This test verifies that:
    // 1) Values are built from the Salesforce API response
    // 2) The result is cached so the API is not called twice
    public function testValuesAreBuiltAndCached(): void
    {
        // Create a mock Salesforce client so no real API call is made
        $client = $this->createMock(OywSalesforceClient::class);

        // Create a real in-memory cache backend for the test
        $time = new Time();
        $cache = new MemoryBackend($time);

        // Mock the field definition and force the field name to be "field_gender"
        $definition = $this->createMock(FieldStorageDefinitionInterface::class);
        $definition->method('getName')->willReturn('field_gender');

        // Tell the mock client that getPicklistValues() should be called once
        // and return a fake Salesforce-style response
        $client->expects($this->once())
            ->method('getPicklistValues')
            ->willReturn([
                'values' => [
                    ['value' => 'Male', 'label' => 'Male'],
                    ['value' => 'Female', 'label' => 'Female'],
                ],
            ]);

        // Create the real service using the mocked client and real cache
        $service = new SalesforceFieldValues($client, $cache);

        // First call should hit the mocked Salesforce client and cache the result
        $values1 = $service->getAllowedValues($definition);

        // Second call should come from cache, not from the client again
        $values2 = $service->getAllowedValues($definition);

        // Assert that both calls return exactly the same cached result
        $this->assertSame($values1, $values2);
    }
}
