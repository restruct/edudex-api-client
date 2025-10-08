<?php

namespace Restruct\EduDex\Integration\SilverStripe\Extensions;

use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\EduDexException;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;

/**
 * SiteConfig extension for EduDex API configuration
 *
 * Adds EduDex settings to the SiteConfig admin interface
 *
 * @package Restruct\EduDex\Integration\SilverStripe\Extensions
 * @property \SilverStripe\SiteConfig\SiteConfig|\Restruct\EduDex\Integration\SilverStripe\Extensions\SiteConfigExtension $owner
 */
class SiteConfigExtension extends DataExtension
{
    /**
     * Database fields
     *
     * @var array
     */
    private static array $db = [
        'EduDexBearerToken' => 'Varchar(500)',
        'EduDexApiBaseUrl' => 'Varchar(255)',
    ];

    /**
     * Default values
     *
     * @var array
     */
    private static array $defaults = [
        'EduDexApiBaseUrl' => 'https://api.edudex.nl/data/v1/',
    ];

    /**
     * Update CMS fields
     *
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields): void
    {
        if (!Permission::check('ADMIN')) {
            return;
        }

        $fields->addFieldsToTab('Root.EduDex', [
            HeaderField::create('EduDexHeader', 'EduDex API Configuration', 2),

            LiteralField::create(
                'EduDexInfo',
                '<p class="message info">Configure EduDex Data API connection settings. '
                . 'The bearer token is required for authentication.</p>'
            ),

            TextField::create('EduDexApiBaseUrl', 'API Base URL')
                ->setDescription('Leave as default unless using a different environment'),

            TextField::create('EduDexBearerToken', 'Bearer Token')
                ->setDescription('JWT token for API authentication. Can also be set via EDUDEX_API_TOKEN environment variable.')
                ->setAttribute('placeholder', 'Enter your bearer token'),

            $this->getConnectionStatusField(),
        ]);
    }

    /**
     * Get connection status field
     *
     * @return LiteralField
     */
    protected function getConnectionStatusField(): LiteralField
    {
        $token = $this->owner->EduDexBearerToken ?: getenv('EDUDEX_API_TOKEN');
        $baseUrl = $this->owner->EduDexApiBaseUrl ?: 'https://api.edudex.nl/data/v1/';

        if (empty($token)) {
            return LiteralField::create(
                'EduDexStatus',
                '<div class="message warning">No bearer token configured. Set the token above or via EDUDEX_API_TOKEN environment variable.</div>'
            );
        }

        try {
            $client = new Client($token, $baseUrl);
            $orgs = $client->organizations()->list();

            $message = sprintf(
                '<div class="message good">✓ Connected successfully. Found %d organizations.</div>',
                count($orgs)
            );
        } catch (EduDexException $e) {
            $message = sprintf(
                '<div class="message bad">✗ Connection failed: %s</div>',
                htmlspecialchars($e->getMessage())
            );
        }

        return LiteralField::create('EduDexStatus', $message);
    }

    /**
     * Get the bearer token (from DB or environment)
     *
     * @return string|null
     */
    public function getEduDexToken(): ?string
    {
        return $this->owner->EduDexBearerToken ?: getenv('EDUDEX_API_TOKEN') ?: null;
    }

    /**
     * Get the API base URL
     *
     * @return string
     */
    public function getEduDexBaseUrl(): string
    {
        return $this->owner->EduDexApiBaseUrl ?: 'https://api.edudex.nl/data/v1/';
    }

    /**
     * Create a configured EduDex client instance
     *
     * @return Client|null
     */
    public function getEduDexClient(): ?Client
    {
        $token = $this->getEduDexToken();

        if (empty($token)) {
            return null;
        }

        try {
            return new Client($token, $this->getEduDexBaseUrl());
        } catch (EduDexException $e) {
            return null;
        }
    }
}
