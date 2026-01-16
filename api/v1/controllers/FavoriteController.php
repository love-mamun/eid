<?php

class FavoriteController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function index()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT e.*, u.username as author_name
                FROM favorites f
                JOIN episodes e ON f.episode_id = e.id
                JOIN users u ON e.author_id = u.id
                WHERE f.user_id = :user_id
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $favorites = $stmt->fetchAll();
            $this->jsonResponse($favorites);
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function toggle($episode_id)
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $user_id = $_SESSION['user_id'];

        try {
            // Check if the episode is already favorited
            $stmt = $this->db->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND episode_id = :episode_id");
            $stmt->execute([':user_id' => $user_id, ':episode_id' => $episode_id]);

            if ($stmt->fetch()) {
                // It is favorited, so remove it
                $deleteStmt = $this->db->prepare("DELETE FROM favorites WHERE user_id = :user_id AND episode_id = :episode_id");
                $deleteStmt->execute([':user_id' => $user_id, ':episode_id' => $episode_id]);
                $this->jsonResponse(['message' => 'Episode removed from favorites', 'status' => 'removed']);
            } else {
                // It is not favorited, so add it
                $insertStmt = $this->db->prepare("INSERT INTO favorites (user_id, episode_id) VALUES (:user_id, :episode_id)");
                $insertStmt->execute([':user_id' => $user_id, ':episode_id' => $episode_id]);
                $this->jsonResponse(['message' => 'Episode added to favorites', 'status' => 'added'], 201);
            }
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
