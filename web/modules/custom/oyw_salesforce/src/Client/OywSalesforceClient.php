<?php

namespace Drupal\oyw_salesforce\Client;

use Drupal\salesforce\Rest\RestClient;
use Psr\Log\LoggerInterface;

class OywSalesforceClient extends RestClient
{
    protected LoggerInterface $logger;

    // Dependency inject logger, this is defined in the services.yml file.
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getPicklistValues(string $name, string $definition): array
    {
        $this->logger->info('Salesforce picklist request started for field: {field}', ['field' => $name]);

        // Extract the data from the json input.
        $parsed = json_decode(str_replace("'", '"', $definition), true);
        $object = $parsed[0];
        $field = $parsed[1];

        // Maybe this could be stored in dotenv in future, fine for this I think.
        $endpoint = "ui-api/object-info/$object/picklist-values/$field/$name";

        $this->logger->info('Calling Salesforce endpoint: {endpoint}', ['endpoint' => $endpoint]);

        // Use the salesforce api to do the api call and get the data back.
        $response = $this->apiCall($endpoint);

        $this->logger->info('Salesforce response received with {count} values', [
            'count' => count($response['values'] ?? []),
        ]);

        return $response;
    }
}
