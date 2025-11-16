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
}
