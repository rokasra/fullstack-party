<?php

namespace App\Service;

use Github\Api\Search;
use Github\Client;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * App\Service\GithubApiManager
 */
class GithubApiManager
{
    const STATE_OPEN = 'open';
    const STATE_CLOSED = 'closed';

    const ISSUES_SORT = 'newest';

    const API_SEARCH = 'search';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var string
     */
    protected $issuesPerPage;

    /**
     * @param Client                $client
     * @param TokenStorageInterface $tokenStorage
     * @param string                $issuesPerPage
     */
    public function __construct(
        Client $client,
        TokenStorageInterface $tokenStorage,
        string $issuesPerPage
    ) {
        $this->client = $client;
        $this->tokenStorage = $tokenStorage;
        $this->issuesPerPage = $issuesPerPage;
    }

    /**
     * @param string $state
     * @param int    $page
     *
     * @return array
     */
    public function getIssuesList(string $state, int $page): array
    {
        $filter = $this->getStateFilter($state);
        if ($username = $this->getUsername()) {
            $filter = sprintf('%s %s', $filter, $this->getInvolvesFilter($username));
        }

        /** @var Search $search */
        $search = $this->getClient()->api(self::API_SEARCH);
        $search->setPerPage($this->issuesPerPage)->setPage($page);

        return $search->issues($filter, self::ISSUES_SORT);
    }

    /**
     * @return int
     */
    public function getIssuesOpenCount(): int
    {
        return $this->getIssuesCount(self::STATE_OPEN);
    }

    /**
     * @return int
     */
    public function getIssuesClosedCount(): int
    {
        return $this->getIssuesCount(self::STATE_CLOSED);
    }

    /**
     * @param string $username
     * @param string $repository
     * @param int    $number
     *
     * @return array
     */
    public function getIssue(string $username, string $repository, int $number): array
    {
        return $this->getClient()->issue()->show($username, $repository, $number);
    }

    /**
     * @param string $username
     * @param string $repository
     * @param int    $number
     * @param int    $page
     *
     * @return array
     */
    public function getComments(string $username, string $repository, int $number, int $page): array
    {
        return $this->getClient()->issue()->comments()->all($username, $repository, $number, $page);
    }

    /**
     * @param string $state
     *
     * @return int
     */
    protected function getIssuesCount(string $state): int
    {
        $filter = $this->getStateFilter($state);
        if ($username = $this->getUsername()) {
            $filter = sprintf('%s %s', $filter, $this->getInvolvesFilter($username));
        }

        /** @var Search $search */
        $search = $this->getClient()->api(self::API_SEARCH);

        return $search->issues($filter)['total_count'];
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OAuthToken) {
            $this->client->authenticate($token->getAccessToken(), null, Client::AUTH_HTTP_TOKEN);
        }

        return $this->client;
    }

    /**
     * @return null|string
     */
    protected function getUsername(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OAuthToken) {
            return null;
        }

        return $token->getUsername();
    }

    /**
     * @param string $state
     *
     * @return string
     */
    protected function getStateFilter(string $state): string
    {
        return sprintf('is:%s', $state);
    }

    /**
     * @param string $username
     *
     * @return string
     */
    protected function getInvolvesFilter(string $username): string
    {
        return sprintf('involves:%s', $username);
    }
}
