<?php

namespace Drupal\Tests\oyw_salesforce\Unit;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\oyw_salesforce\Client\OywSalesforceClient;
use Drupal\oyw_salesforce\SalesforceFieldValues;
use Drupal\Tests\UnitTestCase;

class SalesforceFieldValuesTest extends UnitTestCase
{
    public function testValuesAreBuiltAndCached(): void
    {
        $client = $this->createMock(OywSalesforceClient::class);
        $cache = new MemoryBackend('oyw_salesforce_test');

        $definition = $this->createMock(FieldStorageDefinitionInterface::class);
        $definition->method('getName')->willReturn('field_gender');

        $client->expects($this->once())
            ->method('getPicklistValues')
            ->willReturn([
                'values' => [
                    ['value' => 'Male', 'label' => 'Male'],
                    ['value' => 'Female', 'label' => 'Female'],
                ],
            ]);

        $service = new SalesforceFieldValues($client, $cache);

        $values1 = $service->getAllowedValues($definition);
        $values2 = $service->getAllowedValues($definition);

        $this->assertSame($values1, $values2);
    }
}
