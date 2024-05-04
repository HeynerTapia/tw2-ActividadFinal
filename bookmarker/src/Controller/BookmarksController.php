<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Bookmarks Controller
 *
 * @property \App\Model\Table\BookmarksTable $Bookmarks
 * @method \App\Model\Entity\Bookmark[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BookmarksController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'conditions' => [
                'Bookmarks.user_id' => $this->Auth->user('id'),
            ]
        ];
        $this->set('bookmarks', $this->paginate($this->Bookmarks));
        $this->set('_serialize', ['bookmarks']);
    }
    public function tags()
    {
        // The 'pass' key is provided by CakePHP and contains all
        // the passed URL path segments in the request.
        $tags = $this->request->getParam('pass');

        // Use the BookmarksTable to find tagged bookmarks.
        $bookmarks = $this->Bookmarks->find('tagged', [
            'tags' => $tags
        ]);

        // Pass variables into the view template context.
        $this->set([
            'bookmarks' => $bookmarks,
            'tags' => $tags
        ]);
    }

    /**
     * View method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $bookmark = $this->Bookmarks->get($id, [
            'contain' => ['Users', 'Tags'],
        ]);

        $this->set(compact('bookmark'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $bookmark = $this->Bookmarks->newEntity($this->request->getData()); // Pasa los datos del formulario
        if ($this->request->is('post')) {
            $bookmark->user_id = $this->Auth->user('id');
            if ($this->Bookmarks->save($bookmark)) {
                $this->Flash->success('El favorito se ha guardado.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('El favorito podría no haberse guardado. Por favor, inténtalo de nuevo.');
        }
        $tags = $this->Bookmarks->Tags->find('list');
        $this->set(compact('bookmark', 'tags'));
        $this->set('_serialize', ['bookmark']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        // Obtiene el bookmark con el ID proporcionado, incluyendo sus tags relacionados.
        $bookmark = $this->Bookmarks->get($id, [
            'contain' => ['Tags']
        ]);

        // Verifica si la solicitud es de tipo PATCH, POST o PUT (es decir, si se envió un formulario para editar un favorito).
        if ($this->request->is(['patch', 'post', 'put'])) {
            // Se parchea (patch) la entidad Bookmark con los datos de la solicitud.
            $bookmark = $this->Bookmarks->patchEntity($bookmark, $this->request->getData());
            
            // Se establece el user_id del bookmark como el ID del usuario autenticado.
            $bookmark->user_id = $this->Auth->user('id');
            
            // Se intenta guardar el bookmark en la base de datos.
            if ($this->Bookmarks->save($bookmark)) {
                // Si se guarda correctamente, se muestra un mensaje de éxito y se redirige al usuario a la página index.
                $this->Flash->success('El favorito se ha guardado.');
                return $this->redirect(['action' => 'index']);
            }
            // Si no se guarda correctamente, se muestra un mensaje de error.
            $this->Flash->error('El favorito podría no haberse guardado. Por favor, inténtalo de nuevo.');
        }

        // Se obtienen los tags disponibles para los favoritos.
        $tags = $this->Bookmarks->Tags->find('list');

        // Se envían los datos de la entidad bookmark y los tags a la vista.
        $this->set(compact('bookmark', 'tags'));

        // Se establece la serialización de la entidad bookmark para su uso en la API.
        $this->set('_serialize', ['bookmark']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Bookmark id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $bookmark = $this->Bookmarks->get($id);
        if ($this->Bookmarks->delete($bookmark)) {
            $this->Flash->success(__('The bookmark has been deleted.'));
        } else {
            $this->Flash->error(__('The bookmark could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
    //=========================================Restringir BOOKMARKS==================================
    public function isAuthorized($user)
    {
        // Obtiene el nombre de la acción actual.
        $action = $this->request->getParam('action');

        // Las acciones 'index', 'add' y 'tags' están siempre permitidas para todos los usuarios.
        if (in_array($action, ['index', 'add', 'tags'])) {
            return true;
        }

        // Las demás acciones requieren que se proporcione un ID.
        if (!$this->request->getParam('pass.0')) {
            return false;
        }

        // Si se proporciona un ID, se verifica si el usuario actual tiene permiso para acceder a ese registro.
        $id = $this->request->getParam('pass.0');
        $bookmark = $this->Bookmarks->get($id);

        // Verifica si el usuario actual es el propietario del registro (bookmark).
        if ($bookmark->user_id == $user['id']) {
            // Si el usuario actual es el propietario del registro, se permite el acceso.
            return true;
        }

        // Si no se cumple ninguna de las condiciones anteriores, se delega a la lógica de autorización del controlador padre.
        return parent::isAuthorized($user);
    }
}
