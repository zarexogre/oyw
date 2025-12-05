<?php

namespace Drupal\oyw_salesforce;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\oyw_salesforce\Client\OywSalesforceClient;

/**
 * Service to provide allowed values for Salesforce fields.
 *
 * Create a new class called "SalesforceFieldValues" that is run by the
 * "options_allowed_values" hook, which populates the values for the
 * "field_gender" field.  The class should call the "getPicklistValues"
 * client method like so:
 * $client->getPicklistValues('field_gender', "['Contact',
 * 'Gender__c']")
 */
class SalesforceFieldValues {

  /**
   * Constructs a SalesforceFieldValues service.
   *
   * Using new php feature here property promotion keeps consturctor
   * nice and clean.
   */
  public function __construct(
    private readonly OywSalesforceClient $client,
    private readonly CacheBackendInterface $cache,
  ) {
  }

  /* Resolver with caching and logging support through the client.
  Pass in the Drupal field definition and entity, and weather we want. */

  /**
   * The list cached.  Then return an array of options.
   */
  public function getAllowedValues(
    FieldStorageDefinitionInterface $definition,
    ?FieldableEntityInterface $entity = NULL,
    &$cacheable = NULL,
  ): array {
    // For now only supporting this field, so fail early and return empty
    // to avoid unexpected behavior.  Could log or throw exception here.
    if ($definition->getName() !== 'field_gender') {
      return [];
    }

    $cid = 'oyw_salesforce:field_gender';

    // Cache the results to avoid multiple API calls. (extra)
    $cached = $this->cache->get($cid);
    if ($cached && is_array($cached->data)) {
      return $cached->data;
    }

    // Call the client to get the picklist values.
    $response = $this->client->getPicklistValues(
      'field_gender',
      "['Contact','Gender__c']"
    );

    $values = [];
    // Parse the response to get the values, the example shows values are
    // in 'values' key with 'value' and 'label' subkeys so extract from this
    // to make the data suitable for options list.
    foreach ($response['values'] ?? [] as $item) {
      $values[$item['value']] = $item['label'];
    }

    // Cache the results.
    $this->cache->set($cid, $values);

    return $values;
  }

}
