<?php

namespace App\Models;

use App\Core\Model;

class SocialLink extends Model
{
    public function activeLinks()
    {
        $sql = <<<SQL
            SELECT
                name,
                url,
                icon_path,
                display_order
            FROM social_links
            WHERE is_active = TRUE
            ORDER BY display_order ASC, id ASC
        SQL;

        $this->db->query($sql);

        return $this->db->fetchAll();
    }

    public function getAllLinks()
    {
        $sql = <<<SQL
            SELECT
                id,
                name,
                url,
                icon_path,
                is_active,
                display_order
            FROM social_links
            ORDER BY display_order ASC, id ASC
        SQL;

        $this->db->query($sql);

        return $this->db->fetchAll();
    }

    public function createLink(array $data)
    {
        $sql = <<<SQL
            INSERT INTO social_links (
                name,
                url,
                icon_path,
                display_order,
                is_active
            )
            VALUES (
                :name,
                :url,
                :icon_path,
                :display_order,
                :is_active
            )
            RETURNING id
        SQL;

        $this->db->query($sql)
            ->bind(':name', $data['name'])
            ->bind(':url', $data['url'])
            ->bind(':icon_path', $data['icon_path'])
            ->bind(':display_order', $data['display_order'])
            ->bind(':is_active', $data['is_active'], \PDO::PARAM_BOOL);

        $created = $this->db->fetch();
        return $created?->id ?? false;
    }

    public function updateLink(int $id, array $data): bool
    {
        $sql = <<<SQL
            UPDATE social_links
            SET
                name = :name,
                url = :url,
                icon_path = :icon_path,
                display_order = :display_order,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        SQL;

        $this->db->query($sql)
            ->bind(':id', $id)
            ->bind(':name', $data['name'])
            ->bind(':url', $data['url'])
            ->bind(':icon_path', $data['icon_path'])
            ->bind(':display_order', $data['display_order'])
            ->bind(':is_active', $data['is_active'], \PDO::PARAM_BOOL);

        return $this->db->execute();
    }

    public function deleteLink(int $id): bool
    {
        $sql = 'DELETE FROM social_links WHERE id = :id';
        $this->db->query($sql)->bind(':id', $id);

        return $this->db->execute();
    }
}
