<?php

namespace Restruct\EduDex\Integration\SilverStripe\Extensions;

use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\EduDexException;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfigLeftAndMain;

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
     * Request variable that asks the settings tab to run the connection check
     */
    public const CHECK_VAR = 'edudexcheck';

    /**
     * Get connection status field
     *
     * Only calls the API when the request asks for it (?edudexcheck=1, the "Test connection"
     * link). It used to call the API every time Settings was opened, on every tab, which
     * slowed the screen by up to the request timeout whenever the API was slow (#4).
     *
     * @return LiteralField
     */
    protected function getConnectionStatusField(): LiteralField
    {
        $token = $this->getEduDexToken();

        if (empty($token)) {
            return LiteralField::create(
                'EduDexStatus',
                '<div class="message warning">No bearer token configured. Set the token above or via EDUDEX_API_TOKEN environment variable.</div>'
            );
        }

        if (!$this->isConnectionCheckRequested()) {
            $link = Controller::join_links(SiteConfigLeftAndMain::singleton()->Link(), '?' . self::CHECK_VAR . '=1');

            return LiteralField::create(
                'EduDexStatus',
                sprintf(
                    '<div class="message notice">Connection not tested. <a href="%s">Test connection</a> '
                    . '(reloads Settings and calls the EduDex API once).</div>',
                    htmlspecialchars($link)
                )
            );
        }

        try {
            # getEduDexClient() returns null only when there is no token, handled above
            $orgs = $this->getEduDexClient()->organizations()->list();

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
     * Whether the current request asks for the connection check
     *
     * @return bool
     */
    protected function isConnectionCheckRequested(): bool
    {
        if (!Controller::has_curr()) {
            return false;
        }

        return (bool) Controller::curr()->getRequest()->getVar(self::CHECK_VAR);
    }

    /**
     * Get the bearer token (from DB or environment)
     *
     * @return string|null
     */
    public function getEduDexToken(): ?string
    {
        return $this->owner->EduDexBearerToken ?: Environment::getEnv('EDUDEX_API_TOKEN') ?: null;
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
            // return new Client($token, $this->getEduDexBaseUrl());
            # Through the Injector, so the Client -> SilverStripeClient mapping and its config
            # (timeout, debug) apply; the stored token and base URL still win (#4)
            return Injector::inst()->create(Client::class, $token, $this->getEduDexBaseUrl());
        } catch (EduDexException $e) {
            return null;
        }
    }
}
