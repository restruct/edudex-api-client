<?php

/**
 * SiteConfig extension for EduDex API configuration
 *
 * Adds EduDex settings to the SiteConfig admin interface
 *
 * @property SiteConfig $owner
 */
class EduDexSiteConfigExtension extends DataExtension
{
    /**
     * Database fields
     *
     * @var array
     */
    private static $db = array(
        'EduDexBearerToken' => 'Varchar(500)',
        'EduDexApiBaseUrl' => 'Varchar(255)',
    );

    /**
     * Default values
     *
     * @var array
     */
    private static $defaults = array(
        'EduDexApiBaseUrl' => 'https://api.edudex.nl/data/v1/',
    );

    /**
     * Update CMS fields
     *
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields)
    {
        if (!Permission::check('ADMIN')) {
            return;
        }

        $fields->addFieldsToTab('Root.EduDex', array(
            HeaderField::create('EduDexHeader', 'EduDex API Configuration', 2),

            LiteralField::create(
                'EduDexInfo',
                '<p class="message info">Configure EduDex Data API connection settings. '
                . 'The bearer token is required for authentication.</p>'
            ),

            TextField::create('EduDexApiBaseUrl', 'API Base URL')
                ->setDescription('Leave as default unless using a different environment'),

            TextField::create('EduDexBearerToken', 'Bearer Token')
                ->setDescription('JWT token for API authentication. Can also be set via EDUDEX_API_TOKEN constant in _ss_environment.php.')
                ->setAttribute('placeholder', 'Enter your bearer token'),

            $this->getConnectionStatusField(),
        ));
    }

    /**
     * Get connection status field
     *
     * @return LiteralField
     */
    protected function getConnectionStatusField()
    {
        $token = $this->getEduDexToken();
        $baseUrl = $this->getEduDexBaseUrl();

        if (empty($token)) {
            return LiteralField::create(
                'EduDexStatus',
                '<div class="message warning">No bearer token configured. Set the token above or via EDUDEX_API_TOKEN constant in _ss_environment.php.</div>'
            );
        }

        try {
            $client = new EduDexClient($token, $baseUrl);
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
     * Get the bearer token (from DB or constant)
     *
     * @return string|null
     */
    public function getEduDexToken()
    {
        if ($this->owner->EduDexBearerToken) {
            return $this->owner->EduDexBearerToken;
        }

        if (defined('EDUDEX_API_TOKEN')) {
            return EDUDEX_API_TOKEN;
        }

        return null;
    }

    /**
     * Get the API base URL
     *
     * @return string
     */
    public function getEduDexBaseUrl()
    {
        return $this->owner->EduDexApiBaseUrl ? $this->owner->EduDexApiBaseUrl : 'https://api.edudex.nl/data/v1/';
    }

    /**
     * Create a configured EduDex client instance
     *
     * @return EduDexClient|null
     */
    public function getEduDexClient()
    {
        $token = $this->getEduDexToken();

        if (empty($token)) {
            return null;
        }

        try {
            return new EduDexClient($token, $this->getEduDexBaseUrl());
        } catch (EduDexException $e) {
            return null;
        }
    }
}
