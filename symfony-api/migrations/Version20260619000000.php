<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create breed_favorite, breed_rating and breed_view tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE breed_favorite (
                id SERIAL NOT NULL,
                breed_id VARCHAR(255) NOT NULL,
                session_id VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_breed_favorite_breed_session ON breed_favorite (breed_id, session_id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE breed_rating (
                id SERIAL NOT NULL,
                breed_id VARCHAR(255) NOT NULL,
                session_id VARCHAR(255) NOT NULL,
                score SMALLINT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_breed_rating_breed_session ON breed_rating (breed_id, session_id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE breed_view (
                breed_id VARCHAR(255) NOT NULL,
                view_count INT DEFAULT 0 NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(breed_id)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE breed_favorite');
        $this->addSql('DROP TABLE breed_rating');
        $this->addSql('DROP TABLE breed_view');
    }
}