<?php

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;

/**
 * Thin wrapper for driving the real app over HTTP in functional/security
 * tests. Every request goes through the actual CI3 front controller
 * (index.php/Controller/method) — there is no way to unit-test controllers
 * in isolation given how tightly they're coupled to CI's superobject, so
 * this suite tests observable HTTP behavior instead.
 */
class ApiClient
{
    private Client $http;
    private CookieJar $jar;

    public function __construct()
    {
        $this->jar = new CookieJar();
        $this->http = new Client([
            'base_uri' => rtrim(ADMINTOOL_BASE_URL, '/') . '/index.php/',
            'cookies' => $this->jar,
            'http_errors' => false,
            'allow_redirects' => false,
            'timeout' => 15,
        ]);
    }

    public function get(string $path, array $query = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->http->get($path, [RequestOptions::QUERY => $query]);
    }

    public function post(string $path, array $formParams = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->http->post($path, [RequestOptions::FORM_PARAMS => $formParams]);
    }

    public function postMultipart(string $path, array $multipart): \Psr\Http\Message\ResponseInterface
    {
        return $this->http->post($path, [RequestOptions::MULTIPART => $multipart]);
    }

    /**
     * Logs in as one of the three seeded test accounts and keeps the
     * resulting session cookie for subsequent requests on this client.
     * Returns the raw login response so callers can also assert on it.
     */
    public function loginAs(string $role): \Psr\Http\Message\ResponseInterface
    {
        $usernames = [
            'superadmin' => 'test_superadmin',
            'admin' => 'test_admin',
            'finance' => 'test_finance',
        ];
        if (!isset($usernames[$role])) {
            throw new \InvalidArgumentException("Unknown seeded role: $role");
        }

        return $this->post('Login/aksi_login', [
            'username' => $usernames[$role],
            'password' => 'TestPass123!',
        ]);
    }

    public function cookieValue(string $name): ?string
    {
        $cookie = $this->jar->getCookieByName($name);
        return $cookie ? $cookie->getValue() : null;
    }

    public function rawCookie(string $name): ?\GuzzleHttp\Cookie\SetCookie
    {
        return $this->jar->getCookieByName($name);
    }

    /** A fresh, unauthenticated client sharing no cookies with this one. */
    public static function anonymous(): self
    {
        return new self();
    }
}
