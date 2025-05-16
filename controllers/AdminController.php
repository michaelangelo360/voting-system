<?php
/**
 * AdminController.php
 * Handles admin-related API requests
 */
namespace Controllers;

use Models\Admin;
use Models\Event;
use Services\AuthService;
use Services\FileService;
use Utils\Response;
use Utils\Validator;

class AdminController {
    private $adminModel;
    private $eventModel;
    private $authService;
    private $fileService;

    public function __construct() {
        $this->adminModel = new Admin();
        $this->eventModel = new Event();
        $this->authService = new AuthService();
        $this->fileService = new FileService();
    }

    /**
     * Admin login
     * 
     * @param array $request
     * @return void
     */
    public function login($request) {
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Check if admin exists
        $email = $request['body']['email'];
        $password = $request['body']['password'];
        
        // Super admin login
        if ($email === 'codingbakelvin@gmail.com') {
            $admin = $this->adminModel->getByEmail($email);
            
            if (!$admin || !password_verify($password, $admin['password'])) {
                Response::unauthorized('Invalid email or password');
            }
            
            $token = $this->authService->generateToken([
                'id' => $admin['id'],
                'email' => $admin['email'],
                'is_super_admin' => true
            ]);
            
            Response::success([
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'email' => $admin['email'],
                    'is_super_admin' => true
                ]
            ], 'Admin authenticated successfully');
        } else {
            // Event organizer login
            $organizer = $this->eventModel->verifyOrganizer($email, $password);
            
            if (!$organizer) {
                Response::unauthorized('Invalid email or password');
            }
            
            $token = $this->authService->generateToken([
                'id' => $organizer['id'],
                'email' => $organizer['owner'],
                'is_super_admin' => false
            ]);
            
            Response::success([
                'token' => $token,
                'admin' => [
                    'id' => $organizer['id'],
                    'name' => $organizer['name'],
                    'email' => $organizer['owner'],
                    'is_super_admin' => false
                ]
            ], 'Admin authenticated successfully');
        }
    }

    /**
     * Get all events
     * 
     * @param array $request
     * @return void
     */
    public function getEvents($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Get events
        $events = $this->eventModel->getAll();
        
        // Get event images
        foreach ($events as &$event) {
            if (!empty($event['image_url'])) {
                $event['image_url'] = $this->fileService->getFileUrl($event['image_url']);
            }
        }
        
        Response::success(['events' => $events]);
    }

    /**
     * Create a new event
     * 
     * @param array $request
     * @return void
     */
    public function createEvent($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if super admin
        if (!isset($tokenData['is_super_admin']) || !$tokenData['is_super_admin']) {
            Response::forbidden('Only super admin can create events');
        }
        
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'name' => 'required|min:3',
            'date' => 'required|date',
            'cost' => 'required|numeric',
            'owner' => 'required|email',
            'owner_password' => 'required|min:6'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Prepare data
        $data = [
            'name' => $request['body']['name'],
            'date' => $request['body']['date'],
            'expired' => isset($request['body']['expired']) ? (int)$request['body']['expired'] : 0,
            'cost' => $request['body']['cost'],
            'owner' => $request['body']['owner'],
            'owner_password' => $request['body']['owner_password']
        ];
        
        // Create event
        $eventId = $this->eventModel->create($data);
        
        Response::success(['id' => $eventId], 'Event created successfully', 201);
    }

    /**
     * Update an event
     * 
     * @param array $request
     * @param int $id
     * @return void
     */
    public function updateEvent($request, $id) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if event exists
        if (!$this->eventModel->exists($id)) {
            Response::notFound('Event not found');
        }
        
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'name' => 'string|min:3',
            'date' => 'date',
            'cost' => 'numeric',
            'owner' => 'email',
            'owner_password' => 'string|min:6'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Prepare data
        $data = [];
        if (isset($request['body']['name'])) $data['name'] = $request['body']['name'];
        if (isset($request['body']['date'])) $data['date'] = $request['body']['date'];
        if (isset($request['body']['expired'])) $data['expired'] = (int)$request['body']['expired'];
        if (isset($request['body']['cost'])) $data['cost'] = $request['body']['cost'];
        if (isset($request['body']['owner'])) $data['owner'] = $request['body']['owner'];
        if (isset($request['body']['owner_password'])) $data['owner_password'] = $request['body']['owner_password'];
        
        // Update event
        $this->eventModel->update($id, $data);
        
        Response::success(null, 'Event updated successfully');
    }

    /**
     * Delete an event
     * 
     * @param array $request
     * @param int $id
     * @return void
     */
    public function deleteEvent($request, $id) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if super admin
        if (!isset($tokenData['is_super_admin']) || !$tokenData['is_super_admin']) {
            Response::forbidden('Only super admin can delete events');
        }
        
        // Check if event exists
        if (!$this->eventModel->exists($id)) {
            Response::notFound('Event not found');
        }
        
        // Delete event
        $this->eventModel->delete($id);
        
        Response::success(null, 'Event deleted successfully');
    }

    /**
     * Upload event image
     * 
     * @param array $request
     * @return void
     */
    public function uploadEventImage($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Validate input
        if (!isset($request['files']['image'])) {
            Response::validationError(['image' => 'Image file is required']);
        }
        
        if (!isset($request['body']['event_id'])) {
            Response::validationError(['event_id' => 'Event ID is required']);
        }
        
        $eventId = $request['body']['event_id'];
        
        // Check if event exists
        if (!$this->eventModel->exists($eventId)) {
            Response::notFound('Event not found');
        }
        
        // Upload file
        $file = $request['files']['image'];
        $uploadedFile = $this->fileService->uploadEventImage($file, $eventId);
        
        if (!$uploadedFile) {
            Response::error('Failed to upload image');
        }
        
        // Update event with image URL
        $this->eventModel->updateImage($eventId, $uploadedFile['url']);
        
        Response::success([
            'image_url' => $this->fileService->getFileUrl($uploadedFile['url'])
        ], 'Image uploaded successfully');
    }
}