<?php

class EpisodeController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function isAdmin()
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function index()
    {
        try {
            // Publicly list all approved episodes
            $stmt = $this->db->query("SELECT e.*, u.username as author_name FROM episodes e JOIN users u ON e.author_id = u.id WHERE e.status = 'approved' ORDER BY e.release_date DESC");
            $episodes = $stmt->fetchAll();
            $this->jsonResponse($episodes);
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT e.*, u.username as author_name FROM episodes e JOIN users u ON e.author_id = u.id WHERE e.id = :id");
            $stmt->execute(['id' => $id]);
            $episode = $stmt->fetch();

            if ($episode && ($episode['status'] === 'approved' || $this->isAdmin())) {
                $this->jsonResponse($episode);
            } else {
                $this->jsonResponse(['error' => 'Episode not found or not approved'], 404);
            }
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $data = $this->getRequestBody();
        if (!isset($data['title']) || !isset($data['file_path']) || !isset($data['duration'])) {
            $this->jsonResponse(['error' => 'Missing required fields: title, file_path, duration'], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO episodes (title, description, file_path, duration, author_id) VALUES (:title, :description, :file_path, :duration, :author_id)");
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'] ?? null,
                ':file_path' => $data['file_path'],
                ':duration' => $data['duration'],
                ':author_id' => $_SESSION['user_id']
            ]);
            $this->jsonResponse(['message' => 'Episode created successfully', 'id' => $this->db->lastInsertId()], 201);
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $data = $this->getRequestBody();
        // For simplicity, this example only updates title and description.
        // A more robust implementation would handle updating any field.
        if (!isset($data['title']) && !isset($data['description'])) {
             $this->jsonResponse(['error' => 'No fields to update'], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE episodes SET title = :title, description = :description WHERE id = :id");
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':id' => $id
            ]);
            $this->jsonResponse(['message' => "Episode with id {$id} updated successfully"]);
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM episodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() > 0) {
                $this->jsonResponse(['message' => "Episode with id {$id} deleted successfully"]);
            } else {
                $this->jsonResponse(['error' => 'Episode not found'], 404);
            }
        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
