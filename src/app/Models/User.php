<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function getAllUsers()
    {
        $this->db->query('SELECT * FROM users ORDER BY id DESC');
        return $this->db->fetchAll();
    }

    public function getUserById($id)
    {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->fetch();
    }

    public function deleteUser($id)
    {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }

    public function findByName($name)
    {
        $this->db->query('SELECT * FROM users WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $this->db->bind(':name', $name);
        return $this->db->fetch();
    }

    public function upsertAdmin(string $name, string $passwordHash)
    {
        $sql = <<<SQL
            INSERT INTO users (name, password, role, is_active)
            VALUES (:name, :password, 'Admin', TRUE)
            ON CONFLICT (name) DO UPDATE
            SET
                password = EXCLUDED.password,
                role = 'Admin',
                is_active = TRUE,
                updated_at = CURRENT_TIMESTAMP
            RETURNING *
        SQL;

        $this->db->query($sql)
            ->bind(':name', $name)
            ->bind(':password', $passwordHash);

        return $this->db->fetch();
    }
}
