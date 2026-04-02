<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * BlogPosts Model
 *
 * @method \App\Model\Entity\BlogPost newEmptyEntity()
 * @method \App\Model\Entity\BlogPost newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\BlogPost get(mixed $primaryKey, array|string $finder = 'all', ...$args)
 * @method \App\Model\Entity\BlogPost findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\BlogPost patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\BlogPost saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class BlogPostsTable extends Table
{
    /**
     * Initialize method.
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('blog_posts');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 255)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->scalar('excerpt')
            ->allowEmptyString('excerpt');

        $validator
            ->scalar('meta_description')
            ->maxLength('meta_description', 320)
            ->allowEmptyString('meta_description');

        $validator
            ->scalar('meta_keywords')
            ->maxLength('meta_keywords', 255)
            ->allowEmptyString('meta_keywords');

        $validator
            ->scalar('og_image')
            ->maxLength('og_image', 500)
            ->allowEmptyString('og_image');

        $validator
            ->scalar('author_name')
            ->maxLength('author_name', 100)
            ->allowEmptyString('author_name');

        $validator
            ->scalar('tags')
            ->maxLength('tags', 500)
            ->allowEmptyString('tags');

        $validator
            ->scalar('status')
            ->inList('status', ['draft', 'published'])
            ->notEmptyString('status');

        $validator
            ->scalar('language')
            ->maxLength('language', 5)
            ->inList('language', ['en', 'pt', 'es'])
            ->allowEmptyString('language');

        $validator
            ->dateTime('published_at')
            ->allowEmptyDateTime('published_at');

        return $validator;
    }

    /**
     * Finder for published posts.
     * Returns posts where status = 'published' AND published_at <= NOW().
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findPublished(SelectQuery $query): SelectQuery
    {
        return $query
            ->where([
                'BlogPosts.status' => 'published',
                'BlogPosts.published_at <=' => new \Cake\I18n\DateTime(),
            ])
            ->orderBy(['BlogPosts.published_at' => 'DESC']);
    }

    /**
     * Finder to filter by language.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param string $language Language code (en, pt, es).
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByLanguage(SelectQuery $query, string $language): SelectQuery
    {
        return $query->where(['BlogPosts.language' => $language]);
    }
}

