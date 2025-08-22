<?php

class UserController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index()
    {
        $this->jsonResponse(['message' => 'User controller index']);
    }

    public function register()
    {
        $data = $this->getRequestBody();

        // 1. Validate input
        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
            $this->jsonResponse(['error' => 'Missing required fields'], 400);
            return;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Invalid email format'], 400);
            return;
        }

        try {
            // 2. Check if user or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $data['username'], 'email' => $data['email']]);
            if ($stmt->fetch()) {
                $this->jsonResponse(['error' => 'Username or email already exists'], 409); // 409 Conflict
                return;
            }

            // 3. Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // 4. Insert new user
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword
            ]);

            // 5. Return success response
            $this->jsonResponse(['message' => 'User registered successfully'], 201);

        } catch (PDOException $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function login()
    {
        $data = $this->getRequestBody();

        if (!isset($data['login']) || !isset($data['password'])) {
            $this->jsonResponse(['error' => 'Missing login or password field'], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :login OR email = :login");
            $stmt->execute(['login' => $data['login']]);
            $user = $stmt->fetch();

            if ($user && password_verify($data['password'], $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                $this->jsonResponse([
                    'message' => 'Login successful',
                    'user' => [
                        'username' => $user['username'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                // Bad credentials
                $this->jsonResponse(['error' => 'Invalid credentials'], 401);
            }
        } catch (PDOException $e) {
            $this.jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    public function logout()
    {
        // Unset all of the session variables
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finally, destroy the session
        session_destroy();

        $this->jsonResponse(['message' => 'Logout successful']);
    }
}
