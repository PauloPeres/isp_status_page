<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Blog Controller
 *
 * Handles public blog listing and individual post views.
 * Supports multi-language via ?lang= or /pt/blog/ routes.
 */
class BlogController extends AppController
{
    /**
     * Allow unauthenticated access to all blog actions.
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions(['index', 'view']);
    }

    /**
     * Blog listing page.
     * Shows published posts filtered by language, paginated, newest first.
     *
     * @param string|null $lang Language code (null = detect from route/query).
     * @return \Cake\Http\Response|null
     */
    public function index(?string $lang = null): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        $language = $lang ?? $this->request->getQuery('lang', 'en');
        if (!in_array($language, ['en', 'pt', 'es'])) {
            $language = 'en';
        }

        $query = $this->fetchTable('BlogPosts')
            ->find('published')
            ->find('byLanguage', language: $language);

        $posts = $this->paginate($query, [
            'limit' => 12,
            'order' => ['BlogPosts.published_at' => 'DESC'],
        ]);

        $this->set(compact('posts', 'language'));

        return null;
    }

    /**
     * Single blog post view.
     *
     * @param string $slug The post slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException If post not found or not published.
     */
    public function view(string $slug): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        $post = $this->fetchTable('BlogPosts')
            ->find('published')
            ->where(['BlogPosts.slug' => $slug])
            ->first();

        if (!$post) {
            throw new NotFoundException(__('Blog post not found.'));
        }

        $language = $post->language ?? 'en';
        $this->set(compact('post', 'language'));

        return null;
    }
}
