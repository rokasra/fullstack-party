<?php

namespace App\Controller;

use App\Service\GithubApiManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * App\Controller\DefaultController
 */
class DefaultController extends Controller
{
    /**
     * @param GithubApiManager $manager
     * @param string           $state
     * @param int              $page
     *
     * @Route(
     *     "/{state}/{page}",
     *     defaults={"state" = "open", "page" = "1"},
     *     requirements={"state" = "open|closed", "page" = "\d+"}
     * )
     *
     * @return Response
     */
    public function indexAction(GithubApiManager $manager, string $state, int $page): Response
    {
        return $this->render(
            'default/index.html.twig',
            [
                'state'       => $state,
                'page'        => $page,
                'issuesList'  => $manager->getIssuesList($state, $page),
                'openCount'   => $manager->getIssuesOpenCount(),
                'closedCount' => $manager->getIssuesClosedCount(),
                'totalPages'  => ceil(
                    $manager->getIssuesList($state, $page)['total_count'] / $this->getParameter(
                        'app.service.github_api.issues.per_page'
                    )
                ),
            ]
        );
    }

    /**
     * @param GithubApiManager $manager
     * @param string           $user
     * @param string           $repository
     * @param int              $number
     * @param int              $page
     *
     * @Route(
     *     "/issue/{user}/{repository}/{number}/{page}",
     *     defaults={"page" = "1"},
     *     requirements={"user" = "[\w\-]+", "repository" = "[\w\-]+", "number" = "\d+", "page" = "\d+"}
     * )
     *
     * @return Response
     */
    public function issueAction(
        GithubApiManager $manager,
        string $user,
        string $repository,
        int $number,
        int $page
    ): Response {
        $issue = $manager->getIssue($user, $repository, $number);

        return $this->render(
            'default/issue.html.twig',
            [
                'user'       => $user,
                'repository' => $repository,
                'number'     => $number,
                'page'       => $page,
                'issue'      => $issue,
                'comments'   => $issue['comments'] > 0 ? $manager->getComments($user, $repository, $number, $page) : [],
                'totalPages' => ceil(
                    $issue['comments'] / $this->getParameter(
                        'app.service.github_api.comments.per_page'
                    )
                ),
            ]
        );
    }
}
