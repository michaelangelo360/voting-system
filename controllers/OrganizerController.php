<?php
/**
 * OrganizerController.php
 * Handles organizer-related API requests
 */
namespace Controllers;

use Models\Category;
use Models\Nominee;
use Models\Vote;
use Services\AuthService;
use Services\FileService;
use Utils\Response;
use Utils\Validator;

class OrganizerController {
    private $categoryModel;
    private $nomineeModel;
    private $voteModel;
    private $authService;
    private $fileService;

    public function __construct() {
        $this->categoryModel = new Category();
        $this->nomineeModel = new Nominee();
        $this->voteModel = new Vote();
        $this->authService = new AuthService();
        $this->fileService = new FileService();
    }

    /**
     * Get categories
     * 
     * @param array $request
     * @return void
     */
    public function getCategories($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Get organizer ID
        $organizerId = isset($request['query']['organizer_id']) 
            ? $request['query']['organizer_id'] 
            : $tokenData['sub'];
        
        // Get categories
        $categories = $this->categoryModel->getByOrganizerId($organizerId);
        
        // Get category images
        foreach ($categories as &$category) {
            if (!empty($category['image_url'])) {
                $category['image_url'] = $this->fileService->getFileUrl($category['image_url']);
            }
        }
        
        Response::success(['categories' => $categories]);
    }

    /**
     * Create a new category
     * 
     * @param array $request
     * @return void
     */
    public function createCategory($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'name' => 'required|min:3',
            'organizer_id' => 'required|integer'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Prepare data
        $data = [
            'name' => $request['body']['name'],
            'organizer_id' => $request['body']['organizer_id']
        ];
        
        // Create category
        $categoryId = $this->categoryModel->create($data);
        
        Response::success(['id' => $categoryId], 'Category created successfully', 201);
    }

    /**
     * Update a category
     * 
     * @param array $request
     * @param int $id
     * @return void
     */
    public function updateCategory($request, $id) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if category exists
        if (!$this->categoryModel->exists($id)) {
            Response::notFound('Category not found');
        }
        
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'name' => 'string|min:3'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Prepare data
        $data = [];
        if (isset($request['body']['name'])) $data['name'] = $request['body']['name'];
        
        // Update category
        $this->categoryModel->update($id, $data);
        
        Response::success(null, 'Category updated successfully');
    }

    /**
     * Delete a category
     * 
     * @param array $request
     * @param int $id
     * @return void
     */
    public function deleteCategory($request, $id) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if category exists
        if (!$this->categoryModel->exists($id)) {
            Response::notFound('Category not found');
        }
        
        // Get category to check organizer
        $category = $this->categoryModel->getById($id);
        
        // Check if user is the organizer
        if ($category['organizer_id'] != $tokenData['sub'] && !isset($tokenData['is_super_admin'])) {
            Response::forbidden('You do not have permission to delete this category');
        }
        
        // Delete category image
        $this->fileService->deleteCategoryImage($id);
        
        // Delete category
        $this->categoryModel->delete($id);
        
        Response::success(null, 'Category deleted successfully');
    }

    /**
     * Upload category image
     * 
     * @param array $request
     * @return void
     */
    public function uploadCategoryImage($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Validate input
        if (!isset($request['files']['image'])) {
            Response::validationError(['image' => 'Image file is required']);
        }
        
        if (!isset($request['body']['category_id'])) {
            Response::validationError(['category_id' => 'Category ID is required']);
        }
        
        $categoryId = $request['body']['category_id'];
        
        // Check if category exists
        if (!$this->categoryModel->exists($categoryId)) {
            Response::notFound('Category not found');
        }
        
        // Upload file
        $file = $request['files']['image'];
        $uploadedFile = $this->fileService->uploadCategoryImage($file, $categoryId);
        
        if (!$uploadedFile) {
            Response::error('Failed to upload image');
        }
        
        // Update category with image URL
        $this->categoryModel->updateImage($categoryId, $uploadedFile['url']);
        
        Response::success([
            'image_url' => $this->fileService->getFileUrl($uploadedFile['url'])
        ], 'Image uploaded successfully');
    }

    /**
     * Get nominees
     * 
     * @param array $request
     * @return void
     */
    public function getNominees($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Get parameters
        $organizerId = isset($request['query']['organizer_id']) 
            ? $request['query']['organizer_id'] 
            : $tokenData['sub'];
        
        $categoryId = isset($request['query']['category_id']) 
            ? $request['query']['category_id'] 
            : null;
        
        // Get nominees
        $nominees = $categoryId 
            ? $this->nomineeModel->getByCategoryId($categoryId)
            : $this->nomineeModel->getByOrganizerId($organizerId);
        
        // Get nominee images
        foreach ($nominees as &$nominee) {
            if (!empty($nominee['image_url'])) {
                $nominee['image_url'] = $this->fileService->getFileUrl($nominee['image_url']);
            }
        }
        
        Response::success(['nominees' => $nominees]);
    }

    /**
     * Create a new nominee
     * 
     * @param array $request
     * @return void
     */
    public function createNominee($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'name' => 'required|min:3',
            'category_id' => 'required|integer',
            'organizer_id' => 'required|integer'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Generate unique code
        $code = $this->nomineeModel->generateUniqueCode();
        
        // Prepare data
        $data = [
            'name' => $request['body']['name'],
            'category_id' => $request['body']['category_id'],
            'organizer_id' => $request['body']['organizer_id'],
            'code' => $code,
            'votes' => 0
        ];
        
        // Create nominee
        $nomineeId = $this->nomineeModel->create($data);
        
        Response::success([
            'id' => $nomineeId,
            'code' => $code
        ], 'Nominee created successfully', 201);
    }

    /**
     * Update a nominee
     * 
     * @param array $request
     * @param int $id
     * @return void
     */
    public function updateNominee($request, $id) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if nominee exists
        if (!$this->nomineeModel->exists($id)) {
            Response::notFound('Nominee not found');
        }
        
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'name' => 'string|min:3',
            'category_id' => 'integer'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        // Prepare data
        $data = [];
        if (isset($request['body']['name'])) $data['name'] = $request['body']['name'];
        if (isset($request['body']['category_id'])) $data['category_id'] = $request['body']['category_id'];
        
        // Update nominee
        $this->nomineeModel->update($id, $data);
        
        Response::success(null, 'Nominee updated successfully');
    }

    /**
     * Delete a nominee
     * 
     * @param array $request
     * @param int $id
     * @return void
     */
    public function deleteNominee($request, $id) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Check if nominee exists
        if (!$this->nomineeModel->exists($id)) {
            Response::notFound('Nominee not found');
        }
        
        // Get nominee to check organizer
        $nominee = $this->nomineeModel->getById($id);
        
        // Check if user is the organizer
        if ($nominee['organizer_id'] != $tokenData['sub'] && !isset($tokenData['is_super_admin'])) {
            Response::forbidden('You do not have permission to delete this nominee');
        }
        
        // Delete nominee image
        $this->fileService->deleteNomineeImage($id);
        
        // Delete nominee
        $this->nomineeModel->delete($id);
        
        Response::success(null, 'Nominee deleted successfully');
    }

    /**
     * Upload nominee image
     * 
     * @param array $request
     * @return void
     */
    public function uploadNomineeImage($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Validate input
        if (!isset($request['files']['image'])) {
            Response::validationError(['image' => 'Image file is required']);
        }
        
        if (!isset($request['body']['nominee_id'])) {
            Response::validationError(['nominee_id' => 'Nominee ID is required']);
        }
        
        $nomineeId = $request['body']['nominee_id'];
        
        // Check if nominee exists
        if (!$this->nomineeModel->exists($nomineeId)) {
            Response::notFound('Nominee not found');
        }
        
        // Upload file
        $file = $request['files']['image'];
        $uploadedFile = $this->fileService->uploadNomineeImage($file, $nomineeId);
        
        if (!$uploadedFile) {
            Response::error('Failed to upload image');
        }
        
        // Update nominee with image URL
        $this->nomineeModel->updateImage($nomineeId, $uploadedFile['url']);
        
        Response::success([
            'image_url' => $this->fileService->getFileUrl($uploadedFile['url'])
        ], 'Image uploaded successfully');
    }

    /**
     * Record a vote
     * 
     * @param array $request
     * @return void
     */
    public function recordVote($request) {
        // Validate input
        $validator = new Validator();
        $validator->validate($request['body'], [
            'nominee_id' => 'required|integer',
            'votes' => 'required|integer|min:1',
            'phone_number' => 'string',
            'email' => 'email'
        ]);
        
        if ($validator->hasErrors()) {
            Response::validationError($validator->getErrors());
        }
        
        $nomineeId = $request['body']['nominee_id'];
        $votes = $request['body']['votes'];
        $phoneNumber = $request['body']['phone_number'] ?? null;
        $email = $request['body']['email'] ?? null;
        
        // Check if nominee exists
        if (!$this->nomineeModel->exists($nomineeId)) {
            Response::notFound('Nominee not found');
        }
        
        // Start transaction
        $db = \Config\Database::getInstance();
        $db->beginTransaction();
        
        try {
            // Update nominee votes
            $this->nomineeModel->updateVotes($nomineeId, $votes);
            
            // Record vote
            $voteData = [
                'nominee_id' => $nomineeId,
                'vote_count' => $votes,
                'phone_number' => $phoneNumber,
                'email' => $email
            ];
            
            $voteId = $this->voteModel->create($voteData);
            
            // Commit transaction
            $db->commit();
            
            Response::success(['vote_id' => $voteId], 'Vote recorded successfully');
        } catch (\Exception $e) {
            // Rollback transaction
            $db->rollBack();
            
            Response::error('Failed to record vote: ' . $e->getMessage());
        }
    }

    /**
     * Get vote records
     * 
     * @param array $request
     * @return void
     */
    public function getVoteRecords($request) {
        // Verify token
        $tokenData = $this->authService->verifyRequest($request);
        
        // Get parameters
        $organizerId = isset($request['query']['organizer_id']) 
            ? $request['query']['organizer_id'] 
            : $tokenData['sub'];
        
        // Get vote records
        $voteRecords = $this->voteModel->getByOrganizerId($organizerId);
        
        Response::success(['vote_records' => $voteRecords]);
    }
}