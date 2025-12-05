<?php

namespace Drupal\Tests\oyw_salesforce\Unit;

use Drupal\oyw_salesforce\Client\OywSalesforceClient;
use Drupal\Tests\UnitTestCase;
use Psr\Log\NullLogger;

class OywSalesforceClientTest extends UnitTestCase
{
    public function testGetPicklistValuesBuildsCorrectEndpoint(): void
    {
        $logger = new NullLogger();

        $client = $this->getMockBuilder(OywSalesforceClient::class)
            ->setConstructorArgs([$logger])
            ->onlyMethods(['apiCall'])
            ->getMock();

        $client->expects($this->once())
            ->method('apiCall')
            ->with(
                'ui-api/object-info/Contact/picklist-values/Gender__c/field_gender'
            )
            ->willReturn(['values' => []]);

        $result = $client->getPicklistValues(
            'field_gender',
            "['Contact','Gender__c']"
        );

        $this->assertIsArray($result);
    }
}
