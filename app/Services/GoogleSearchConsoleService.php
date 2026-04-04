<?php

namespace App\Services;

use App\Models\Setting;
use Google\Client as GoogleClient;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService
{
    protected ?GoogleClient $client = null;
    protected ?SearchConsole $service = null;

    public function getClient(): GoogleClient
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google_search_console.client_id'));
        $this->client->setClientSecret(config('services.google_search_console.client_secret'));
        $this->client->setRedirectUri(config('services.google_search_console.redirect_uri'));
        $this->client->addScope(SearchConsole::WEBMASTERS_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Load existing tokens
        $accessToken = Setting::get('gsc_access_token');
        if ($accessToken) {
            $tokenData = json_decode($accessToken, true);
            $this->client->setAccessToken($tokenData);

            // Refresh if expired
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = Setting::get('gsc_refresh_token');
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->client->getAccessToken();
                    Setting::set('gsc_access_token', json_encode($newToken), 'password', 'google');
                    if (isset($newToken['refresh_token'])) {
                        Setting::set('gsc_refresh_token', $newToken['refresh_token'], 'password', 'google');
                    }
                }
            }
        }

        return $this->client;
    }

    public function getService(): SearchConsole
    {
        if (!$this->service) {
            $this->service = new SearchConsole($this->getClient());
        }
        return $this->service;
    }

    public function getAuthUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function handleCallback(string $code): bool
    {
        try {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('GSC OAuth error', $token);
                return false;
            }

            Setting::set('gsc_access_token', json_encode($token), 'password', 'google');
            if (isset($token['refresh_token'])) {
                Setting::set('gsc_refresh_token', $token['refresh_token'], 'password', 'google');
            }
            Setting::set('gsc_connected', '1', 'boolean', 'google');

            return true;
        } catch (\Exception $e) {
            Log::error('GSC callback failed: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect(): void
    {
        Setting::set('gsc_access_token', '', 'password', 'google');
        Setting::set('gsc_refresh_token', '', 'password', 'google');
        Setting::set('gsc_connected', '0', 'boolean', 'google');
        Cache::forget('gsc_dashboard_data');
    }

    public function isConnected(): bool
    {
        return Setting::get('gsc_connected', '0') === '1'
            && Setting::get('gsc_access_token', '') !== '';
    }

    public function getSiteUrl(): string
    {
        return config('app.url');
    }

    /**
     * Get search analytics data for the dashboard.
     * Cached for 6 hours since GSC data has a 2-3 day delay anyway.
     */
    public function getDashboardData(): array
    {
        if (!$this->isConnected()) {
            return $this->emptyData();
        }

        return Cache::remember('gsc_dashboard_data', 6 * 3600, function () {
            try {
                return [
                    'summary' => $this->getSummary(),
                    'topQueries' => $this->getTopQueries(),
                    'topPages' => $this->getTopPages(),
                    'clicksOverTime' => $this->getClicksOverTime(),
                    'connected' => true,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                Log::error('GSC data fetch failed: ' . $e->getMessage());
                return [
                    ...$this->emptyData(),
                    'connected' => true,
                    'error' => 'Failed to fetch data. Try reconnecting.',
                ];
            }
        });
    }

    protected function getSummary(): array
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate(now()->subDays(28)->format('Y-m-d'));
        $request->setEndDate(now()->subDays(3)->format('Y-m-d'));

        $response = $this->getService()->searchanalytics->query($this->getSiteUrl(), $request);
        $rows = $response->getRows();

        if (empty($rows)) {
            return ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];
        }

        $row = $rows[0];
        return [
            'clicks' => (int) $row->getClicks(),
            'impressions' => (int) $row->getImpressions(),
            'ctr' => round($row->getCtr() * 100, 1),
            'position' => round($row->getPosition(), 1),
        ];
    }

    protected function getTopQueries(int $limit = 10): array
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate(now()->subDays(28)->format('Y-m-d'));
        $request->setEndDate(now()->subDays(3)->format('Y-m-d'));
        $request->setDimensions(['query']);
        $request->setRowLimit($limit);

        $response = $this->getService()->searchanalytics->query($this->getSiteUrl(), $request);
        $rows = $response->getRows() ?? [];

        return array_map(fn($row) => [
            'query' => $row->getKeys()[0],
            'clicks' => (int) $row->getClicks(),
            'impressions' => (int) $row->getImpressions(),
            'ctr' => round($row->getCtr() * 100, 1),
            'position' => round($row->getPosition(), 1),
        ], $rows);
    }

    protected function getTopPages(int $limit = 10): array
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate(now()->subDays(28)->format('Y-m-d'));
        $request->setEndDate(now()->subDays(3)->format('Y-m-d'));
        $request->setDimensions(['page']);
        $request->setRowLimit($limit);

        $response = $this->getService()->searchanalytics->query($this->getSiteUrl(), $request);
        $rows = $response->getRows() ?? [];

        $siteUrl = rtrim($this->getSiteUrl(), '/');

        return array_map(fn($row) => [
            'page' => str_replace($siteUrl, '', $row->getKeys()[0]),
            'clicks' => (int) $row->getClicks(),
            'impressions' => (int) $row->getImpressions(),
            'ctr' => round($row->getCtr() * 100, 1),
            'position' => round($row->getPosition(), 1),
        ], $rows);
    }

    protected function getClicksOverTime(): array
    {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate(now()->subDays(28)->format('Y-m-d'));
        $request->setEndDate(now()->subDays(3)->format('Y-m-d'));
        $request->setDimensions(['date']);

        $response = $this->getService()->searchanalytics->query($this->getSiteUrl(), $request);
        $rows = $response->getRows() ?? [];

        $data = [];
        foreach ($rows as $row) {
            $data[$row->getKeys()[0]] = [
                'clicks' => (int) $row->getClicks(),
                'impressions' => (int) $row->getImpressions(),
            ];
        }

        // Fill missing dates
        for ($i = 28; $i >= 3; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            if (!isset($data[$date])) {
                $data[$date] = ['clicks' => 0, 'impressions' => 0];
            }
        }
        ksort($data);

        return $data;
    }

    protected function emptyData(): array
    {
        return [
            'summary' => ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0],
            'topQueries' => [],
            'topPages' => [],
            'clicksOverTime' => [],
            'connected' => false,
            'error' => null,
        ];
    }
}
