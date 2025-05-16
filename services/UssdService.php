<?php
/**
 * UssdService.php
 * Service for USSD session management
 */
namespace Services;

use Models\UssdSession;
use Models\Nominee;
use Models\Category;
use Models\Event;

class UssdService {
    private $ussdSessionModel;
    private $nomineeModel;
    private $categoryModel;
    private $eventModel;
    
    public function __construct() {
        $this->ussdSessionModel = new UssdSession();
        $this->nomineeModel = new Nominee();
        $this->categoryModel = new Category();
        $this->eventModel = new Event();
    }
    
    /**
     * Initialize a new USSD session
     * 
     * @param string $sessionId
     * @param string $msisdn
     * @param string $network
     * @return array
     */
    public function initializeSession($sessionId, $msisdn, $network) {
        // Create new session in the database
        $sessionData = [
            'session_id' => $sessionId,
            'msisdn' => $msisdn,
            'network' => $network,
            'level' => 1
        ];
        
        $sessionId = $this->ussdSessionModel->create($sessionData);
        
        // Get the created session
        $session = $this->ussdSessionModel->getById($sessionId);
        
        // Return the welcome message
        return [
            'session' => $session,
            'message' => 'Hello Welcome to AllVotesGh.' . PHP_EOL . 'Please Enter Nominee\'s code',
            'continueSession' => true
        ];
    }
    
    /**
     * Get session by session ID
     * 
     * @param string $sessionId
     * @return array|null
     */
    public function getSession($sessionId) {
        return $this->ussdSessionModel->getBySessionId($sessionId);
    }
    
    /**
     * Update session
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSession($id, $data) {
        return $this->ussdSessionModel->update($id, $data);
    }
    
    /**
     * Process level 1 - Nominee code entry
     * 
     * @param array $session
     * @param string $userData
     * @return array
     */
    public function processLevel1($session, $userData) {
        // Try to find nominee by code
        $nominee = $this->nomineeModel->getByCode($userData);
        
        if (!$nominee) {
            // Nominee not found
            return [
                'message' => 'Nominee not found. Please enter a valid nominee code.',
                'continueSession' => true,
                'level' => 1
            ];
        }
        
        // Nominee found, update session
        $this->updateSession($session['id'], [
            'level' => 2,
            'nominee_id' => $nominee['id']
        ]);
        
        // Get category and event
        $category = $this->categoryModel->getById($nominee['category_id']);
        $event = $this->eventModel->getById($nominee['organizer_id']);
        
        // Next screen - ask for number of votes
        return [
            'message' => "Nominee {$nominee['name']} found." . PHP_EOL . 
                       "Please Enter Number of Votes." . PHP_EOL . 
                       "Cost per Vote: Ghc {$event['cost']}",
            'continueSession' => true,
            'level' => 2
        ];
    }
    
    /**
     * Process level 2 - Number of votes entry
     * 
     * @param array $session
     * @param string $userData
     * @return array
     */
    public function processLevel2($session, $userData) {
        // Validate votes input
        $votes = intval($userData);
        
        if ($votes <= 0) {
            // Invalid number of votes
            return [
                'message' => 'Invalid number of votes. Please enter a valid number of votes:',
                'continueSession' => true,
                'level' => 2
            ];
        }
        
        // Get nominee, category and event details
        $nominee = $this->nomineeModel->getById($session['nominee_id']);
        $category = $this->categoryModel->getById($nominee['category_id']);
        $event = $this->eventModel->getById($nominee['organizer_id']);
        
        // Calculate total cost
        $totalCost = $votes * $event['cost'];
        
        // Update session
        $this->updateSession($session['id'], [
            'level' => 3,
            'votes' => $votes
        ]);
        
        // Next screen - confirmation
        return [
            'message' => "Approve {$votes} Votes at Ghc {$totalCost} for" . PHP_EOL . 
                       "{$nominee['name']}" . PHP_EOL . 
                       "{$category['name']}" . PHP_EOL . 
                       "{$event['name']}" . PHP_EOL . 
                       "1: Confirm" . PHP_EOL . 
                       "2: Cancel",
            'continueSession' => true,
            'level' => 3
        ];
    }
    
    /**
     * Process level 3 - Confirmation
     * 
     * @param array $session
     * @param string $userData
     * @return array
     */
    public function processLevel3($session, $userData) {
        if ($userData === '2') {
            // Cancel
            return [
                'message' => 'Vote cancelled.',
                'continueSession' => false,
                'level' => 3
            ];
        } else if ($userData === '1') {
            // Return confirmation, actual payment will be handled by the controller
            return [
                'message' => 'Processing payment...',
                'continueSession' => true,
                'level' => 4,
                'action' => 'payment'
            ];
        } else {
            // Invalid option
            return [
                'message' => 'Invalid option. Please enter 1 to confirm or 2 to cancel:',
                'continueSession' => true,
                'level' => 3
            ];
        }
    }
    
    /**
     * Determine mobile money provider by phone number
     * 
     * @param string $phoneNumber
     * @return string
     */
    public function determineProvider($phoneNumber) {
        // Map of prefixes to providers
        $providerMappings = [
            '23350' => 'mtn',
            '23354' => 'mtn',
            '23355' => 'mtn',
            '23356' => 'mtn',
            '23357' => 'mtn',
            '23359' => 'mtn',
            '23320' => 'vod',
            '23324' => 'mtn',
            '23327' => 'vod',
            '23326' => 'tgo',
            '23323' => 'tgo',
            '23328' => 'tgo',
        ];
        
        // Clean phone number
        $cleanedNumber = $phoneNumber;
        
        // Check prefixes
        foreach ($providerMappings as $prefix => $provider) {
            if (strpos($cleanedNumber, $prefix) === 0) {
                return $provider;
            }
        }
        
        // Default to MTN if no match
        return 'mtn';
    }
    
    /**
     * Clean up old sessions
     * 
     * @param int $minutes
     * @return int
     */
    public function cleanupOldSessions($minutes = 60) {
        return $this->ussdSessionModel->cleanupOldSessions($minutes);
    }
    
    /**
     * Get session statistics
     * 
     * @param int $days
     * @return array
     */
    public function getSessionStatistics($days = 7) {
        $stats = [];
        
        // Get total sessions
        $totalSessions = $this->ussdSessionModel->getActiveSessionsCount($days * 24 * 60);
        $stats['total_sessions'] = $totalSessions;
        
        // Get sessions by level
        $level1 = $this->ussdSessionModel->getByLevel(1);
        $level2 = $this->ussdSessionModel->getByLevel(2);
        $level3 = $this->ussdSessionModel->getByLevel(3);
        
        $stats['level_stats'] = [
            'level_1' => count($level1),
            'level_2' => count($level2),
            'level_3' => count($level3)
        ];
        
        return $stats;
    }
}