<?php

class EpisodeController extends Controller
{
    public function index()
    {
        $this->jsonResponse(['message' => 'Episode controller index']);
    }

    public function show($id)
    {
        $this->jsonResponse(['message' => "Show episode with id {$id}"]);
    }

    public function create()
    {
        $data = $this->getRequestBody();
        $this->jsonResponse(['message' => 'Episode created successfully', 'data' => $data], 201);
    }

    public function update($id)
    {
        $data = $this->getRequestBody();
        $this->jsonResponse(['message' => "Episode with id {$id} updated successfully", 'data' => $data]);
    }

    public function delete($id)
    {
        $this->jsonResponse(['message' => "Episode with id {$id} deleted successfully"]);
    }
}
