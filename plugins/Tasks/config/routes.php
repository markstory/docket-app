<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

$routes->plugin('Tasks', ['path' => '/'], function (RouteBuilder $builder): void {
    try {
        $builder->applyMiddleware('csrf');
    } catch (Exception) {
    }

    $builder->scope('/tasks', ['controller' => 'Tasks'], function (RouteBuilder $builder): void {
        $builder->get('/', ['action' => 'index'], 'tasks:index');
        $builder->get('/today', ['action' => 'daily', 'today'], 'tasks:today');
        $builder->get('/upcoming', ['action' => 'index', 'upcoming'], 'tasks:upcoming');
        $builder->get('/deleted', ['action' => 'deleted'], 'tasks:deleted');

        $builder->get('/day/{date}', ['action' => 'daily'], 'tasks:daily')
            ->setPass(['date']);

        $builder->connect('/add', ['action' => 'add'], ['_name' => 'tasks:add']);

        // HTMX uses delete to change completion status as the success
        // means the element needs to be removed from the client state.
        $builder->post('/{id}/complete', ['action' => 'complete'], 'tasks:complete')
            ->setPass(['id']);
        $builder->delete('/{id}/complete', ['action' => 'complete'])->setPass(['id']);
        $builder->post('/{id}/incomplete', ['action' => 'incomplete'], 'tasks:incomplete')
            ->setPass(['id']);
        $builder->delete('/{id}/incomplete', ['action' => 'incomplete'])->setPass(['id']);

        $builder->post('/{id}/delete', ['action' => 'delete'], 'tasks:delete')
            ->setPass(['id']);
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'tasks:deleteconfirm')
            ->setPass(['id']);
        $builder->post('/{id}/undelete', ['action' => 'undelete'], 'tasks:undelete')
            ->setPass(['id']);

        $builder->post('/{id}/edit', ['action' => 'edit'], 'tasks:edit')
            ->setPass(['id']);
        $builder->put('/{id}/edit', ['action' => 'edit'])->setPass(['id']);

        $builder->get('/{id}/view', ['action' => 'view'], 'tasks:view')
            ->setPass(['id']);
        $builder->get('/{id}/view/{mode}', ['action' => 'view'], 'tasks:viewmode')
            ->setPass(['id', 'mode']);
        $builder->post('/{id}/move', ['action' => 'move'], 'tasks:move')
            ->setPass(['id']);
    });

    $builder->scope('/projects', ['controller' => 'Projects'], function (RouteBuilder $builder): void {
        $builder->get('/', ['action' => 'index'], 'projects:index');
        $builder->connect('/add', 'Projects::add', ['_name' => 'projects:add']);
        $builder->get('/archived', ['action' => 'archived'], 'projects:archived');
        $builder->post('/reorder', ['action' => 'reorder'], 'projects:reorder');

        $builder->get('/{slug}', ['action' => 'view'], 'projects:view')
            ->setPass(['slug']);
        $builder->post('/{slug}/delete', ['action' => 'delete'], 'projects:delete')
            ->setPass(['slug']);
        $builder->get('/{slug}/delete/confirm', ['action' => 'deleteConfirm'], 'projects:deleteConfirm')
            ->setPass(['slug']);
        $builder->post('/{slug}/archive', ['action' => 'archive'], 'projects:archive')
            ->setPass(['slug']);
        $builder->post('/{slug}/unarchive', ['action' => 'unarchive'], 'projects:unarchive')
            ->setPass(['slug']);
        $builder->post('/{slug}/move', ['action' => 'move'], 'projects:move')
            ->setPass(['slug']);
        $builder->connect('/{slug}/edit', ['action' => 'edit'], ['_name' => 'projects:edit'])
            ->setPass(['slug']);
    });

    $builder->scope(
        '/projects/{projectSlug}/sections',
        ['controller' => 'ProjectSections'],
        function (RouteBuilder $builder): void {
            $builder->connect('/', ['action' => 'add'], ['_name' => 'projectsections:add'])
                ->setPass(['projectSlug']);
            $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'projectsections:edit'])
                ->setPass(['projectSlug', 'id']);
            $builder->get('/{id}/view', ['action' => 'view'], 'projectsections:view')
                ->setPass(['projectSlug', 'id']);
            $builder->post('/{id}/move', ['action' => 'move'], 'projectsections:move')
                ->setPass(['projectSlug', 'id']);
            $builder->post('/{id}/delete', ['action' => 'delete'], 'projectsections:delete')
                ->setPass(['projectSlug', 'id']);
            $builder->connect(
                '/{id}/delete/confirm',
                ['action' => 'deleteConfirm'],
                ['_name' => 'projectsections:deleteconfirm']
            )
            ->setPass(['projectSlug', 'id']);
        }
    );

    $builder->scope(
        '/projectsections',
        ['controller' => 'ProjectSections'],
        function (RouteBuilder $builder): void {
            $builder->get('/options', ['action' => 'options'], 'projectsections:options');
        }
    );
});

// API routes - no CSRF
$routes->plugin('Tasks', ['path' => '/'], function (RouteBuilder $builder): void {
    $builder->setExtensions(['json']);

    $builder->prefix('Api', ['_namePrefix' => 'api:'], function (RouteBuilder $builder): void {
        $builder->scope('/tasks', ['controller' => 'Tasks'], function (RouteBuilder $builder) {
            $builder->get('/', ['action' => 'index'], 'tasks:index');
            $builder->get('/today', ['action' => 'daily', 'today'], 'tasks:today');
            $builder->get('/upcoming', ['action' => 'index', 'upcoming'], 'tasks:upcoming');
            $builder->get('/deleted', ['action' => 'deleted'], 'tasks:deleted');

            $builder->get('/day/{date}', ['action' => 'daily'], 'tasks:daily')
                ->setPass(['date']);

            $builder->post('/add', ['action' => 'add'], 'tasks:add');

            $builder->post('/{id}/complete', ['action' => 'complete'], 'tasks:complete')
                ->setPass(['id']);
            $builder->delete('/{id}/complete', ['action' => 'complete'])->setPass(['id']);
            $builder->post('/{id}/incomplete', ['action' => 'incomplete'], 'tasks:incomplete')
                ->setPass(['id']);
            $builder->delete('/{id}/incomplete', ['action' => 'incomplete'])->setPass(['id']);

            $builder->post('/{id}/delete', ['action' => 'delete'], 'tasks:delete')
                ->setPass(['id']);
            $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'tasks:deleteconfirm')
                ->setPass(['id']);
            $builder->post('/{id}/undelete', ['action' => 'undelete'], 'tasks:undelete')
                ->setPass(['id']);

            $builder->post('/{id}/edit', ['action' => 'edit'], 'tasks:edit')
                ->setPass(['id']);
            $builder->put('/{id}/edit', ['action' => 'edit'])->setPass(['id']);

            $builder->get('/{id}/view', ['action' => 'view'], 'tasks:view')
                ->setPass(['id']);
            $builder->post('/{id}/move', ['action' => 'move'], 'tasks:move')
                ->setPass(['id']);
        });

        $builder->scope('/projects', ['controller' => 'Projects'], function (RouteBuilder $builder) {
            $builder->get('/', ['action' => 'index'], 'projects:index');
            $builder->post('/add', ['action' => 'add'], 'projects:add');
            $builder->get('/archived', ['action' => 'archived'], 'projects:archived');
            $builder->post('/reorder', ['action' => 'reorder'], 'projects:reorder');

            $builder->get('/{slug}', ['action' => 'view'], 'projects:view')
                ->setPass(['slug']);
            $builder->post('/{slug}/delete', ['action' => 'delete'], 'projects:delete')
                ->setPass(['slug']);
            $builder->post('/{slug}/archive', ['action' => 'archive'], 'projects:archive')
                ->setPass(['slug']);
            $builder->post('/{slug}/unarchive', ['action' => 'unarchive'], 'projects:unarchive')
                ->setPass(['slug']);
            $builder->post('/{slug}/move', ['action' => 'move'], 'projects:move')
                ->setPass(['slug']);
            $builder->post('/{slug}/edit', ['action' => 'edit'], 'projects:edit')
                ->setPass(['slug']);
        });

        $builder->scope(
            '/projects/{projectSlug}/sections',
            ['controller' => 'ProjectSections'],
            function (RouteBuilder $builder) {
                $builder->connect('/', ['action' => 'add'], ['_name' => 'projectsections:add'])
                    ->setPass(['projectSlug']);
                $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'projectsections:edit'])
                    ->setPass(['projectSlug', 'id']);
                $builder->get('/{id}/view', ['action' => 'view'], 'projectsections:view')
                    ->setPass(['projectSlug', 'id']);
                $builder->post('/{id}/move', ['action' => 'move'], 'projectsections:move')
                    ->setPass(['projectSlug', 'id']);
                $builder->post('/{id}/delete', ['action' => 'delete'], 'projectsections:delete')
                    ->setPass(['projectSlug', 'id']);
            }
        );
        $builder->scope(
            '/projectsections',
            ['controller' => 'ProjectSections'],
            function (RouteBuilder $builder) {
                $builder->get('/options', ['action' => 'options'], 'projectsections:options');
            }
        );

        $builder->scope('/tasks/{taskId}/subtasks', ['controller' => 'Subtasks'], function ($builder) {
            $builder->post('/', ['action' => 'add'], 'subtasks:add')
                ->setPass(['taskId']);
            $builder->post('/{id}/edit', ['action' => 'edit'], 'subtasks:edit')
                ->setPass(['taskId', 'id']);
            $builder->post('/{id}/delete', ['action' => 'delete'], 'subtasks:delete')
                ->setPass(['taskId', 'id']);
            $builder->post('/{id}/toggle', ['action' => 'toggle'], 'subtasks:toggle')
                ->setPass(['taskId', 'id']);
            $builder->post('/{id}/move', ['action' => 'move'], 'subtasks:move')
                ->setPass(['taskId', 'id']);
        });
    });
});
