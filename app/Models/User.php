<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function create(string $name, string $email, string $password, string $role = 'admin'): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :hash, :role)'
        );
        $stmt->execute([
            ':name'  => trim($name),
            ':email' => strtolower(trim($email)),
            ':hash'  => password_hash($password, PASSWORD_DEFAULT),
            ':role'  => $role,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function updatePassword(int $id, string $password): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id'   => $id,
        ]);
    }

    public static function touchLogin(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    public static function count(): int
    {
        $count = Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        return (int) $count;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name, email, role, last_login_at, created_at
             FROM users
             ORDER BY role DESC, name ASC'
        );
        return $stmt->fetchAll();
    }

    public static function updateBasics(int $id, string $name, string $email, string $role): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users
             SET name = :name, email = :email, role = :role, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':id'    => $id,
            ':name'  => trim($name),
            ':email' => strtolower(trim($email)),
            ':role'  => $role,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM users WHERE email = :email';
        $params = [':email' => strtolower(trim($email))];
        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $exceptId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    public static function isValidRole(string $role): bool
    {
        return in_array($role, ['admin', 'viewer'], true);
    }
}
