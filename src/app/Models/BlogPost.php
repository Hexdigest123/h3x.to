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

    public function getAllPosts()
    {
        $sql = <<<SQL
            SELECT
                id,
                title,
                slug,
                category,
                is_public,
                short_description,
                description,
                html,
                created_at,
                published_at
            FROM blog_posts
            ORDER BY created_at DESC
        SQL;

        $this->db->query($sql);

        return $this->db->fetchAll();
    }

    public function createPost(array $data)
    {
        $sql = <<<SQL
            INSERT INTO blog_posts (
                title,
                slug,
                category,
                short_description,
                description,
                html,
                is_public,
                author_id,
                published_at
            )
            VALUES (
                :title,
                :slug,
                :category,
                :short_description,
                :description,
                :html,
                :is_public,
                :author_id,
                :published_at
            )
            RETURNING id
        SQL;

        $this->db->query($sql)
            ->bind(':title', $data['title'])
            ->bind(':slug', $data['slug'])
            ->bind(':category', $data['category'])
            ->bind(':short_description', $data['short_description'])
            ->bind(':description', $data['description'])
            ->bind(':html', $data['html'])
            ->bind(':is_public', $data['is_public'], \PDO::PARAM_BOOL)
            ->bind(':author_id', $data['author_id'])
            ->bind(':published_at', $data['published_at']);

        $created = $this->db->fetch();
        return $created?->id ?? false;
    }

    public function updatePost(int $id, array $data): bool
    {
        $sql = <<<SQL
            UPDATE blog_posts
            SET
                title = :title,
                slug = :slug,
                category = :category,
                short_description = :short_description,
                description = :description,
                html = :html,
                is_public = :is_public,
                updated_at = CURRENT_TIMESTAMP,
                published_at = :published_at
            WHERE id = :id
        SQL;

        $this->db->query($sql)
            ->bind(':id', $id)
            ->bind(':title', $data['title'])
            ->bind(':slug', $data['slug'])
            ->bind(':category', $data['category'])
            ->bind(':short_description', $data['short_description'])
            ->bind(':description', $data['description'])
            ->bind(':html', $data['html'])
            ->bind(':is_public', $data['is_public'], \PDO::PARAM_BOOL)
            ->bind(':published_at', $data['published_at']);

        return $this->db->execute();
    }

    public function deletePost(int $id): bool
    {
        $sql = 'DELETE FROM blog_posts WHERE id = :id';
        $this->db->query($sql)->bind(':id', $id);

        return $this->db->execute();
    }
}
