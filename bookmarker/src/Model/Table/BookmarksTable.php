<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Bookmarks Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TagsTable&\Cake\ORM\Association\BelongsToMany $Tags
 *
 * @method \App\Model\Entity\Bookmark newEmptyEntity()
 * @method \App\Model\Entity\Bookmark newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark get($primaryKey, $options = [])
 * @method \App\Model\Entity\Bookmark findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Bookmark patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Bookmark|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bookmark saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bookmark[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Bookmark[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Bookmark[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Bookmark[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BookmarksTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('bookmarks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsToMany('Tags', [
            'foreignKey' => 'bookmark_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'bookmarks_tags',
        ]);
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 50)
            ->allowEmptyString('title');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('url')
            ->allowEmptyString('url');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    // El argumento $query es una instancia de query.
    // El array $options contendrá las opciones de 'tags' que pasemos
    // para encontrar'tagged') en nuestra acción del controlador.
    public function findTagged(Query $query, array $options)
    {
        $bookmarks = $this->find()
            ->select(['id', 'url', 'title', 'description']);

        if (empty($options['tags'])) {
            $bookmarks
                ->leftJoinWith('Tags')
                ->where(['Tags.title IS' => null]);
        } else {
            $bookmarks
                ->innerJoinWith('Tags')
                ->where(['Tags.title IN ' => $options['tags']]);
        }

        return $bookmarks->group(['Bookmarks.id']);
    }

    public function beforeSave($event, $entity, $options)
    {
        if ($entity->tag_string) {
            $entity->tags = $this->_buildTags($entity->tag_string);
        }
    }

    protected function _buildTags($tagString)
    {
        // Hace trim a las etiquetas
        $newTags = array_map('trim', explode(',', $tagString));
        // Elimina las etiquetas vacías
        $newTags = array_filter($newTags);
        // Elimina las etiquetas duplicadas
        $newTags = array_unique($newTags);

        $out = [];
        $query = $this->Tags->find()
            ->where(['Tags.title IN' => $newTags]);

        // Elimina las etiquetas existentes de la lista de nuevas etiquetas.
        foreach ($query->extract('title') as $existing) {
            $index = array_search($existing, $newTags);
            if ($index !== false) {
                unset($newTags[$index]);
            }
        }
        // Añade las etiquetas existentes.
        foreach ($query as $tag) {
            $out[] = $tag;
        }
        // Añade las etiquetas nuevas.
        foreach ($newTags as $tag) {
            $out[] = $this->Tags->newEntity(['title' => $tag]);
        }
        return $out;
    }
}
