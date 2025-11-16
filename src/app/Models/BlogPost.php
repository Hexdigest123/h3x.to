<?php

namespace App\Models;

use App\Core\Model;

class BlogPost extends Model
{
    public function getPublicPosts()
    {
        $sql = <<<SQL
            SELECT
                id,
                title,
                slug,
                short_description,
                description,
                html,
                category,
                created_at,
                published_at
            FROM blog_posts
            WHERE is_public = TRUE
            ORDER BY COALESCE(published_at, created_at) DESC
        SQL;

        $this->db->query($sql);

        return $this->db->fetchAll();
    }
}
