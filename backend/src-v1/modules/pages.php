<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/public/pages', function(Request $request, Response $response) {
    $attributes = array(
        'site_id'   => filter_var($request->getAttribute('siteId'), FILTER_SANITIZE_NUMBER_INT),
    );

    // Define controller, fill up main variables
    $pagesController = new PagesController($this->db);

    $pages = $pagesController->getPublicPages($attributes);

    $data = array(
        'pages' => $pages
    );

    return $response->withJson($data, null, JSON_NUMERIC_CHECK);
});

$app->get('/pages', function(Request $request, Response $response) {
    if (!$request->getAttribute('isLogged')) {
        $response = $response->withStatus(401);
        $data = array('message' => 'Authorization required');
    } else {
        $attributes = array(
            'site_id'   => filter_var($request->getAttribute('siteId'), FILTER_SANITIZE_NUMBER_INT),
        );

        // Define controller, fill up main variables
        $pagesController = new PagesController($this->db, $request->getAttribute('user'));

        $pages = $pagesController->getPages($attributes);

        $data = array(
            'pages' => $pages
        );
    }

    return $response->withJson($data, null, JSON_NUMERIC_CHECK);
})->add($auth);

$app->get('/pages/{id}', function(Request $request, Response $response) {
    if (!$request->getAttribute('isLogged')) {
        $response = $response->withStatus(401);
        $data = array('message' => 'Authorization required');
    } else {
        $attributes = array(
            'site_id'   => filter_var($request->getAttribute('siteId'), FILTER_SANITIZE_NUMBER_INT),
            'id'        => filter_var($request->getAttribute('id'), FILTER_SANITIZE_NUMBER_INT),
        );

        // Define controller, fill up main variables
        $pagesController = new PagesController($this->db, $request->getAttribute('user'));
        
        $data = array(
            'page' => $pagesController->getPage($attributes)
        );
    }

    return $response->withJson($data, null, JSON_NUMERIC_CHECK);
})->add($auth);

$app->post('/pages/add', function(Request $request, Response $response) {
    if (!$request->getAttribute('isLogged')) {
        $response = $response->withStatus(401);
        $data = array('message' => 'Authorization required');
    } else {
        // Fetching post parameters
        $body = $request->getParsedBody();

        $user = $request->getAttribute('user');

        $attributes = array(
            'site_id'           => filter_var($request->getAttribute('siteId'), FILTER_SANITIZE_NUMBER_INT),
            'title'             => filter_var($body['title'], FILTER_SANITIZE_STRING),
            'meta_title'        => filter_var($body['metaTitle'], FILTER_SANITIZE_STRING),
            'meta_description'  => filter_var($body['metaDescription'], FILTER_SANITIZE_STRING),
            'link'              => filter_var($body['link'], FILTER_SANITIZE_STRING),
            'text'              => filter_var($body['text'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'enabled'           => filter_var($body['enabled'], FILTER_SANITIZE_NUMBER_INT),
        );
        
        // Define controller, fill up main variables
        $pagesController = new PagesController($this->db, $user);

        // Trying to register user
        $checkPage = $pagesController->addPage($attributes);

        if (!$checkPage) {
            $response = $response->withStatus(400);
            $data = array(
                'message' => $pagesController->getMessage(),
                'fields' => $pagesController->getFields(),
            );

            Log::save($this->db, [
                'module'    => 'pages',
                'type'      => 'add',
                'user_id'   => $user->id,
                'info'      => 'Page creation failed <b>' . $pagesController->getMessage() . '</b>'
            ]);
        } else {
            // Passing success message
            $data = array(
                'state' => 'success',
                'message' => 'Success! New page added.',
            );

            Log::save($this->db, [
                'module'    => 'pages',
                'type'      => 'add',
                'user_id'   => $user->id,
                'info'      => 'Page added <b>' . $attributes['title'] . '</b>'
            ]);
        }
    }

    return $response->withJson($data, null, JSON_NUMERIC_CHECK);
})->add($auth);

$app->post('/pages/edit', function(Request $request, Response $response) {
    if (!$request->getAttribute('isLogged')) {
        $response = $response->withStatus(401);
        $data = array('message' => 'Authorization required');
    } else {
        // Fetching post parameters
        $body = $request->getParsedBody();

        $user = $request->getAttribute('user');

        $attributes = array(
            'id'                => filter_var($body['id'], FILTER_SANITIZE_NUMBER_INT),
            'title'             => filter_var($body['title'], FILTER_SANITIZE_STRING),
            'meta_title'        => filter_var($body['metaTitle'], FILTER_SANITIZE_STRING),
            'meta_description'  => filter_var($body['metaDescription'], FILTER_SANITIZE_STRING),
            'link'              => filter_var($body['link'], FILTER_SANITIZE_STRING),
            'text'              => filter_var($body['text'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'enabled'           => filter_var($body['enabled'], FILTER_SANITIZE_NUMBER_INT),
        );

        // Define controller, fill up main variables
        $pagesController = new PagesController($this->db, $user);

        // Trying to register user
        $checkPage = $pagesController->editPage($attributes);

        if (!$checkPage) {
            $response = $response->withStatus(400);
            $data = array(
                'message' => $pagesController->getMessage(),
                'fields' => $pagesController->getFields(),
            );

            Log::save($this->db, [
                'module'    => 'pages',
                'type'      => 'edit',
                'user_id'   => $user->id,
                'info'      => 'Page update failed <b>' . $pagesController->getMessage() . '</b>'
            ]);
        } else {
            // Passing success message
            $data = array(
                'state' => 'success',
                'message' => 'Page updated.',
            );

            Log::save($this->db, [
                'module'    => 'pages',
                'type'      => 'edit',
                'user_id'   => $user->id,
                'info'      => 'Page updated [<b>' . $attributes['id'] . '</b>]'
            ]);
        }
    }

    return $response->withJson($data, null, JSON_NUMERIC_CHECK);
})->add($auth);

$app->delete('/pages/delete/{id}', function(Request $request, Response $response) {
    if (!$request->getAttribute('isLogged')) {
        $response = $response->withStatus(401);
        $data = array('message' => 'Authorization required');
    } else {
        $user = $request->getAttribute('user');

        $attributes = array(
            'id' => $request->getAttribute('id'),
        );

        // Define controller, fill up main variables
        $pagesController = new PagesController($this->db, $user);

        $checkPage = $pagesController->deletePage($attributes['id']);
        
        if (!$checkPage) {
            $response = $response->withStatus(400);
            $data = array(
                'message' => $pagesController->getMessage(),
            );

            Log::save($this->db, [
                'module'    => 'pages',
                'type'      => 'delete',
                'user_id'   => $user->id,
                'info'      => 'Page deletion failed [<b>' . $attributes['id'] . '</b>] <b>' . $pagesController->getMessage() . '</b>'
            ]);
        } else {
            // Passing success message
            $data = array(
                'state' => 'success',
                'message' => 'Page removed',
            );

            Log::save($this->db, [
                'module'    => 'pages',
                'type'      => 'delete',
                'user_id'   => $user->id,
                'info'      => 'Page removed [<b>' . $attributes['id'] . '</b>]'
            ]);
        }
    }

    return $response->withJson($data, null, JSON_NUMERIC_CHECK);
})->add($auth);

class PagesController
{
    private $db;
    private $user;
    public $fields;
    public $message;

    public function __construct($db, $user = null) {
        $this->db = $db;
        $this->user = $user;
    }

    public function getMessage() {
        // Return all messages and strip <br /> at the end of the line
        return rtrim($this->message, '<br />');
    }

    public function getFields() {
        // Return unique fields
        return array_unique($this->fields);
    }

    public function getPublicPages($attributes) {
        $q = $this->db->prepare(
            'SELECT `title`, `meta_title`, `meta_description`, `link`, `text` '.
            'FROM `mo_pages` '.
            'WHERE `deleted` = 0 '.
            'AND `enabled` = 1 '.
            'AND `site_id` = :site_id '
        );

        $q->bindParam(':site_id', $attributes['site_id'], PDO::PARAM_INT);

        $q->execute();

        $pages = $q->fetchAll();

        foreach($pages as &$v) {
            $v['text'] = html_entity_decode($v['text'], ENT_QUOTES);

            if (!$v['meta_title']) {
                $v['meta_title'] = $v['title'];
            }
        }

        return $pages;
    }

    public function getPages($attributes) {
        $q = $this->db->prepare(
            'SELECT `id`, `title`, `link`, `enabled` '.
            'FROM `mo_pages` '.
            'WHERE `deleted` = 0 '.
            'AND `site_id` = :site_id '
        );

        $q->bindParam(':site_id', $attributes['site_id'], PDO::PARAM_INT);

        $q->execute();

        return $q->fetchAll();
    }

    public function getPage($attributes) {
        $q = $this->db->prepare(
            'SELECT `id`, `title`, `meta_title`, `meta_description`, `link`, `text`, `enabled` '.
            'FROM `mo_pages` '.
            'WHERE `id` = :id '.
            'AND `deleted` = 0 '.
            'AND `site_id` = :site_id '.
            'LIMIT 1'
        );

        $q->bindParam(':site_id', $attributes['site_id'], PDO::PARAM_INT);
        $q->bindParam(':id', $attributes['id'], PDO::PARAM_INT);

        $q->execute();

        $page = $q->fetch();

        $page['text'] = html_entity_decode($page['text'], ENT_QUOTES);
        
        return $page;
    }

    public function addPage($attributes) {
        $formData = $this->checkForm($attributes, 'add');

        // In case check failed, $message should have the error
        if (!$formData) {
            return false;
        }

        $q = $this->db->prepare(
            'INSERT INTO `mo_pages` SET '.
            '`site_id` = :site_id, '.
            '`title` = :title, '.
            '`meta_title` = :meta_title, '.
            '`meta_description` = :meta_description, '.
            '`link` = :link, '.
            '`text` = :text, '.
            '`enabled` = :enabled '
        );

        $enabled = $attributes['enabled'] ? true : false;

        $q->bindParam(':site_id', $attributes['site_id'], PDO::PARAM_INT);
        $q->bindParam(':title', $attributes['title'], PDO::PARAM_STR);
        $q->bindParam(':meta_title', $attributes['meta_title'], PDO::PARAM_STR);
        $q->bindParam(':meta_description', $attributes['meta_description'], PDO::PARAM_STR);
        $q->bindParam(':link', $attributes['link'], PDO::PARAM_STR);
        $q->bindParam(':text', $attributes['text'], PDO::PARAM_STR);
        $q->bindParam(':enabled', $enabled, PDO::PARAM_BOOL);
        
        try {
            $q->execute();
        } catch(Exception $e) {
            ddump($e->getMessage());
        }

        return true;
    }

    public function editPage($attributes) {
        $formData = $this->checkForm($attributes, 'edit');

        // In case check failed, $message should have the error
        if (!$formData) {
            return false;
        }

        $q = $this->db->prepare(
            'UPDATE `mo_pages` SET '.
            '`title` = :title, '.
            '`meta_title` = :meta_title, '.
            '`meta_description` = :meta_description, '.
            '`link` = :link, '.
            '`text` = :text, '.
            '`enabled` = :enabled '.
            'WHERE `id` = :id '
        );

        $enabled = $attributes['enabled'] ? true : false;

        $q->bindParam(':title', $attributes['title'], PDO::PARAM_STR);
        $q->bindParam(':meta_title', $attributes['meta_title'], PDO::PARAM_STR);
        $q->bindParam(':meta_description', $attributes['meta_description'], PDO::PARAM_STR);
        $q->bindParam(':link', $attributes['link'], PDO::PARAM_STR);
        $q->bindParam(':text', $attributes['text'], PDO::PARAM_STR);
        $q->bindParam(':enabled', $enabled, PDO::PARAM_BOOL);
        $q->bindParam(':id', $attributes['id'], PDO::PARAM_INT);

        $q->execute();

        return true;
    }

    private function checkForm($attributes, $type) {
        if (!$attributes['title']) {
            $this->message .= 'Title is empty<br />';
            $this->fields[] = 'title';
        } else if (strlen($attributes['title']) > 100) {
            $this->message .= 'Title is too long<br />';
            $this->fields[] = 'title';
        }

        if ($attributes['meta_title'] && strlen($attributes['meta_title']) > 70) {
            $this->message .= 'Meta title is too long<br />';
            $this->fields[] = 'meta_title';
        }

        if ($attributes['meta_description'] && strlen($attributes['meta_description']) > 230) {
            $this->message .= 'Meta description is too long<br />';
            $this->fields[] = 'meta_description';
        }

        if (!$attributes['link']) {
            $this->message .= 'Link is empty<br />';
            $this->fields[] = 'link';
        } else if (strlen($attributes['link']) > 300) {
            $this->message .= 'Link is too long<br />';
            $this->fields[] = 'link';
        } else if (preg_match('/\s/', $attributes['link'])) {
            $this->message .= 'Link must not have spaces<br />';
            $this->fields[] = 'link';
        }

        if ($this->message) {
            return false;
        }

        return true;
    }

    public function deletePage($id) {
        $this->db->query('UPDATE `mo_pages` SET `deleted` = 1 WHERE `id` = ' . (int)$id . ' LIMIT 1');
        
        return true;
    }
}