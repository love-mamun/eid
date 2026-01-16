<?php

class PlaylistController extends Controller
{
    private $db;
    private $user_id;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    private function isLoggedIn()
    {
        return $this->user_id !== null;
    }

    private function isPlaylistOwner($playlist_id)
    {
        $stmt = $this->db->prepare("SELECT id FROM playlists WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $playlist_id, ':user_id' => $this->user_id]);
        return $stmt->fetch() !== false;
    }

    public function index()
    {
        if (!$this->isLoggedIn()) return $this->jsonResponse(['error' => 'Unauthorized'], 401);

        $stmt = $this->db->prepare("SELECT * FROM playlists WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $this->user_id]);
        $this->jsonResponse($stmt->fetchAll());
    }

    public function show($id)
    {
        if (!$this->isLoggedIn()) return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        if (!$this->isPlaylistOwner($id)) return $this->jsonResponse(['error' => 'Forbidden'], 403);

        $stmt = $this->db->prepare("SELECT * FROM playlists WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $playlist = $stmt->fetch();

        $episodesStmt = $this->db->prepare("
            SELECT e.* FROM playlist_episodes pe
            JOIN episodes e ON pe.episode_id = e.id
            WHERE pe.playlist_id = :playlist_id ORDER BY pe.added_at
        ");
        $episodesStmt->execute([':playlist_id' => $id]);
        $playlist['episodes'] = $episodesStmt->fetchAll();

        $this->jsonResponse($playlist);
    }

    public function create()
    {
        if (!$this->isLoggedIn()) return $this->jsonResponse(['error' => 'Unauthorized'], 401);

        $data = $this->getRequestBody();
        if (empty($data['name'])) {
            return $this->jsonResponse(['error' => 'Playlist name is required'], 400);
        }

        $stmt = $this->db->prepare("INSERT INTO playlists (user_id, name, description) VALUES (:user_id, :name, :description)");
        $stmt->execute([
            ':user_id' => $this->user_id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null
        ]);
        $this->jsonResponse(['message' => 'Playlist created', 'id' => $this->db->lastInsertId()], 201);
    }

    public function delete($id)
    {
        if (!$this->isLoggedIn()) return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        if (!$this->isPlaylistOwner($id)) return $this->jsonResponse(['error' => 'Forbidden'], 403);

        $stmt = $this->db->prepare("DELETE FROM playlists WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $this->jsonResponse(['message' => 'Playlist deleted']);
    }

    public function addEpisode($playlist_id)
    {
        if (!$this->isLoggedIn()) return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        if (!$this->isPlaylistOwner($playlist_id)) return $this->jsonResponse(['error' => 'Forbidden'], 403);

        $data = $this->getRequestBody();
        if (empty($data['episode_id'])) {
            return $this->jsonResponse(['error' => 'Episode ID is required'], 400);
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO playlist_episodes (playlist_id, episode_id) VALUES (:playlist_id, :episode_id)");
            $stmt->execute([
                ':playlist_id' => $playlist_id,
                ':episode_id' => $data['episode_id']
            ]);
            $this->jsonResponse(['message' => 'Episode added to playlist'], 201);
        } catch (PDOException $e) {
            // Catch unique constraint violation
            if ($e->getCode() == '23000') {
                return $this->jsonResponse(['error' => 'Episode is already in this playlist'], 409);
            }
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function removeEpisode($playlist_id, $episode_id)
    {
        if (!$this->isLoggedIn()) return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        if (!$this->isPlaylistOwner($playlist_id)) return $this->jsonResponse(['error' => 'Forbidden'], 403);

        $stmt = $this->db->prepare("DELETE FROM playlist_episodes WHERE playlist_id = :playlist_id AND episode_id = :episode_id");
        $stmt->execute([':playlist_id' => $playlist_id, ':episode_id' => $episode_id]);
        $this->jsonResponse(['message' => 'Episode removed from playlist']);
    }
}
