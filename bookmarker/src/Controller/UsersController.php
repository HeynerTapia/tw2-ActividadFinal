<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Bookmarks'],
        ]);

        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function login()
    {
        // Verificar si la solicitud es de tipo POST (es decir, si se envió un formulario de inicio de sesión)
        if ($this->request->is('post')) 
        {
            // Intentar identificar al usuario utilizando el componente Auth
            $user = $this->Auth->identify();
            // Si se identifica al usuario correctamente
            if ($user) {
                // Establecer la sesión del usuario
                $this->Auth->setUser($user);
                // Redirigir al usuario a la página a la que intentaba acceder antes de iniciar sesión (o a la página por defecto)
                return $this->redirect($this->Auth->redirectUrl());
            }
            // Si el usuario no se identifica correctamente, mostrar un mensaje de error
            $this->Flash->error('Tu usuario o contraseña es incorrecta.');
        }
    }
    
    public function initialize():void
    {
        // Llama al método initialize() del controlador padre para mantener las configuraciones del controlador.
        parent::initialize();

        // Permite que las acciones 'logout' y 'add' sean accesibles sin autenticación.
        // Esto significa que los usuarios podrán acceder a estas acciones sin necesidad de iniciar sesión.
        // 'logout': Permite a los usuarios cerrar sesión en la aplicación.
        // 'add': Permite a los usuarios agregar nuevos elementos, probablemente en el contexto de una funcionalidad de CRUD.
        $this->Auth->allow(['logout', 'add']);
    }

    public function logout()
    {
        $this->Flash->success('You are now logged out.');
        return $this->redirect($this->Auth->logout());
    }

    
}
